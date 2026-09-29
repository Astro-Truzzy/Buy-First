<?php

declare(strict_types=1);

/**
 * Account area — orders, addresses, profile.
 *
 * require_login() at the top of every action is our "middleware": guests
 * are sent to /login, and after signing in they return here.
 *
 * IDOR (Insecure Direct Object Reference): guessing /account/orders/BF-OTHER
 * or posting someone else's address_id. Every query is pinned to Auth::id().
 */
class AccountController
{
    public static function index(): string
    {
        require_login();
        $user = Auth::user();

        $addresses = Address::forUser((int) $user['id']);
        $default   = null;
        foreach ($addresses as $a) {
            if ((int) $a['is_default_shipping'] === 1) {
                $default = $a;
                break;
            }
        }

        return render('account/index', [
            'user'           => $user,
            'orders'         => Order::forUser((int) $user['id'], 3),
            'orderCount'     => Order::countForUser((int) $user['id']),
            'wishCount'      => Wishlist::count(),
            'bagCount'       => Cart::count(),
            'defaultAddress' => $default ?? ($addresses[0] ?? null),
        ], ['title' => 'My Account']);
    }

    public static function orders(): string
    {
        require_login();

        return render('account/orders', [
            'user'   => Auth::user(),
            'orders' => Order::forUser(Auth::id()),
        ], ['title' => 'Orders']);
    }

    public static function order(string $number): string
    {
        require_login();
        $order = self::ownedOrder($number);

        return render('account/order', [
            'user'  => Auth::user(),
            'order' => $order,
            'lines' => Order::itemsFor((int) $order['id']),
        ], ['title' => 'Order ' . $order['order_number']]);
    }

    /** POST — put this order's lines back in the bag (stock-capped). */
    public static function buyAgain(string $number): never
    {
        require_login();
        $order = self::ownedOrder($number);

        $added = 0;
        foreach (Order::itemsFor((int) $order['id']) as $line) {
            if ($line['variant_id'] && Cart::add((int) $line['variant_id'], (int) $line['quantity']) !== 'unavailable') {
                $added++;
            }
        }

        flash_set(
            $added ? 'success' : 'error',
            $added ? "{$added} item(s) added to your bag." : 'Nothing from that order is in stock.'
        );
        redirect($added ? '/cart' : '/account/orders/' . $order['order_number']);
    }

    public static function addresses(): string
    {
        require_login();

        return render('account/addresses', [
            'user'      => Auth::user(),
            'addresses' => Address::forUser(Auth::id()),
        ], ['title' => 'Addresses']);
    }

    public static function addressForm(?string $id = null): string
    {
        require_login();
        $address = null;
        if ($id !== null) {
            $address = Address::findOwned((int) $id, Auth::id());
            if ($address === null) {
                http_response_code(404);
                return render('errors/404', [], ['title' => 'Address not found']);
            }
        }

        return render('account/address-form', [
            'user'    => Auth::user(),
            'address' => $address,
            'errors'  => [],
            'old'     => $address ?? Address::fromPost(),
        ], ['title' => $address ? 'Edit address' : 'Add address']);
    }

    public static function addressSave(?string $id = null): string
    {
        require_login();
        $data   = Address::fromPost();
        $errors = Address::validate($data);

        if ($id !== null && Address::findOwned((int) $id, Auth::id()) === null) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Address not found']);
        }

        if ($errors) {
            return render('account/address-form', [
                'user'    => Auth::user(),
                'address' => $id ? Address::findOwned((int) $id, Auth::id()) : null,
                'errors'  => $errors,
                'old'     => $data,
            ], ['title' => $id ? 'Edit address' : 'Add address']);
        }

        if ($id !== null) {
            Address::update((int) $id, Auth::id(), $data);
            flash_set('success', 'Address updated.');
        } else {
            Address::create(Auth::id(), $data);
            flash_set('success', 'Address saved.');
        }

        redirect('/account/addresses');
    }

    public static function addressDelete(): never
    {
        require_login();
        Address::delete((int) ($_POST['address_id'] ?? 0), Auth::id());
        flash_set('info', 'Address removed.');
        redirect('/account/addresses');
    }

    public static function addressDefault(): never
    {
        require_login();
        Address::setDefault((int) ($_POST['address_id'] ?? 0), Auth::id());
        flash_set('success', 'Default delivery address updated.');
        redirect('/account/addresses');
    }

    public static function profile(): string
    {
        require_login();
        $user = Auth::user();

        return render('account/profile', [
            'user'   => $user,
            'errors' => [],
            'old'    => [
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'email'      => $user['email'],
                'newsletter' => (bool) $user['newsletter_opt_in'],
            ],
        ], ['title' => 'Profile']);
    }

    public static function profileSave(): string
    {
        require_login();
        $user = Auth::user();
        $id   = (int) $user['id'];

        $first      = trim($_POST['first_name'] ?? '');
        $last       = trim($_POST['last_name'] ?? '');
        $email      = strtolower(trim($_POST['email'] ?? ''));
        $newsletter = isset($_POST['newsletter']);

        $errors = [];
        if ($first === '' || mb_strlen($first) > 60) {
            $errors[] = 'Please enter your first name.';
        }
        if ($last === '' || mb_strlen($last) > 60) {
            $errors[] = 'Please enter your last name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (User::emailTakenByOther($email, $id)) {
            $errors[] = 'That email is already used by another account.';
        }

        if ($errors) {
            return render('account/profile', [
                'user'   => $user,
                'errors' => $errors,
                'old'    => [
                    'first_name' => $first,
                    'last_name'  => $last,
                    'email'      => $email,
                    'newsletter' => $newsletter,
                ],
            ], ['title' => 'Profile']);
        }

        User::updateProfile($id, $first, $last, $email, $newsletter);
        flash_set('success', 'Profile saved.');
        redirect('/account/profile');
    }

    public static function passwordSave(): never
    {
        require_login();
        $user = Auth::user();

        $current = $_POST['current_password'] ?? '';
        $next    = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            flash_set('error', 'Your current password is incorrect.');
            redirect('/account/profile');
        }
        if (strlen($next) < 8) {
            flash_set('error', 'The new password must be at least 8 characters.');
            redirect('/account/profile');
        }
        if ($next !== $confirm) {
            flash_set('error', 'The two new passwords do not match.');
            redirect('/account/profile');
        }

        User::setPassword((int) $user['id'], $next);
        session_regenerate_id(true); // password change = new session
        flash_set('success', 'Password updated.');
        redirect('/account/profile');
    }

    /** 404 unless this order belongs to the logged-in user. */
    private static function ownedOrder(string $number): array
    {
        $order = Order::findByNumber($number);
        if ($order === null || (int) $order['user_id'] !== Auth::id()) {
            http_response_code(404);
            echo render('errors/404', [], ['title' => 'Order not found']);
            exit;
        }

        return $order;
    }
}
