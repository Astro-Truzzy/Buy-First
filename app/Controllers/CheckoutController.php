<?php

declare(strict_types=1);

/**
 * Checkout controller — the last mile: address, delivery, coupon, payment,
 * then an order.
 *
 * Money rule: every total is recomputed on the server from the cart, the
 * coupon row, and the delivery method. Posted "totals" are ignored.
 */
class CheckoutController
{
    /** A card number that always fails — useful for testing the error path. */
    private const FAIL_CARD = '4000000000000002';

    public static function show(): string
    {
        $items = Cart::items();
        if ($items === []) {
            flash_set('info', 'Your bag is empty.');
            redirect('/cart');
        }

        // So "Sign in" from this page sends them back here after login.
        if (!Auth::check()) {
            $_SESSION['intended'] = '/checkout';
        }

        self::ensureIdempotencyKey();

        $coupon = self::sessionCoupon($items);
        $method = self::methodFrom($_SESSION['delivery_method'] ?? 'standard');
        $quote  = Order::quote($items, $method, $coupon, Auth::user());
        $user   = Auth::user();

        return render('checkout', [
            'items'          => $items,
            'quote'          => $quote,
            'method'         => $method,
            'coupon'         => $coupon,
            'addresses'      => $user ? Address::forUser((int) $user['id']) : [],
            'errors'         => [],
            'old'            => self::defaults($user),
            'member'         => !empty($user['is_member']),
            'idempotency'    => $_SESSION['checkout_key'],
            'paymentMethod'  => 'card',
            'bankConfigured' => BankAccount::isConfigured(),
            'bankAccount'    => BankAccount::get(),
        ], ['title' => 'Checkout']);
    }

    /** POST /checkout/delivery — remember the chosen method, bounce back. */
    public static function delivery(): never
    {
        $_SESSION['delivery_method'] = self::methodFrom($_POST['delivery_method'] ?? 'standard');
        redirect('/checkout');
    }

    /** POST /checkout/coupon — apply or remove a code, then bounce back. */
    public static function coupon(): never
    {
        [$type, $message] = Coupon::handlePost(Cart::subtotal(Cart::items()), Auth::id());
        flash_set($type, $message);
        redirect('/checkout');
    }

    /** POST /checkout — validate, charge the card or record a bank transfer, then place the order. */
    public static function place(): string
    {
        $items = Cart::items();
        if ($items === []) {
            flash_set('info', 'Your bag is empty.');
            redirect('/cart');
        }

        $user   = Auth::user();
        $method = self::methodFrom($_POST['delivery_method'] ?? 'standard');
        $_SESSION['delivery_method'] = $method;

        $coupon = self::sessionCoupon($items);
        $ship   = self::shippingFromPost($user);
        $errors = self::validate($ship, $items, $coupon);

        $paymentMethod = self::paymentMethodFrom($_POST['payment_method'] ?? 'card');
        $bankConfigured = BankAccount::isConfigured();
        if ($paymentMethod === 'bank_transfer' && !$bankConfigured) {
            $paymentMethod = 'card';
        }

        $cardNumber = '';
        if ($paymentMethod === 'card') {
            $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '') ?? '';
            if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
                $errors[] = 'Enter a valid card number.';
            }
            if (!preg_match('/^\d{2}\s*\/\s*\d{2}$/', trim($_POST['card_exp'] ?? ''))) {
                $errors[] = 'Expiry must be MM / YY.';
            }
            if (!preg_match('/^\d{3,4}$/', trim($_POST['card_cvc'] ?? ''))) {
                $errors[] = 'Enter a 3 or 4 digit CVC.';
            }
            if (trim($_POST['card_name'] ?? '') === '') {
                $errors[] = 'Enter the name on the card.';
            }
        }

        if ($errors) {
            $quote = Order::quote($items, $method, $coupon, $user);
            return render('checkout', [
                'items'          => $items,
                'quote'          => $quote,
                'method'         => $method,
                'coupon'         => $coupon,
                'addresses'      => $user ? Address::forUser((int) $user['id']) : [],
                'errors'         => $errors,
                'old'            => $ship,
                'member'         => !empty($user['is_member']),
                'idempotency'    => $_POST['idempotency_key'] ?? $_SESSION['checkout_key'] ?? '',
                'paymentMethod'  => $paymentMethod,
                'bankConfigured' => $bankConfigured,
                'bankAccount'    => BankAccount::get(),
            ], ['title' => 'Checkout']);
        }

        $sessionKey = (string) ($_SESSION['checkout_key'] ?? '');
        $postedKey  = (string) ($_POST['idempotency_key'] ?? '');
        if ($sessionKey === '' || !hash_equals($sessionKey, $postedKey)) {
            flash_set('error', 'Your checkout session expired. Please try again.');
            redirect('/checkout');
        }

        if ($paymentMethod === 'card') {
            $paid = $cardNumber !== self::FAIL_CARD;

            if (!$paid) {
                flash_set('error', 'Payment declined. Try a different card — use any number except 4000 0000 0000 0002.');
                redirect('/checkout');
            }
        }

        try {
            $order = Order::place([
                'items'           => $items,
                'method'          => $method,
                'coupon'          => $coupon,
                'user'            => $user,
                'email'           => $ship['email'],
                'ship'            => $ship,
                'payment_method'  => $paymentMethod,
                'paid'            => $paymentMethod === 'card',
                'payment_ref'     => $paymentMethod === 'card' ? 'card_' . bin2hex(random_bytes(8)) : null,
                'idempotency_key' => $_POST['idempotency_key'] ?? $_SESSION['checkout_key'],
            ]);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/checkout');
        }

        if ($user && !empty($_POST['save_address'])) {
            Address::create((int) $user['id'], $ship);
        }

        $_SESSION['last_order'] = $order['order_number'];
        unset($_SESSION['checkout_key'], $_SESSION['coupon_code'], $_SESSION['delivery_method']);

        Mailer::orderConfirmation($order, Order::itemsFor((int) $order['id']));

        flash_set('success', 'Order ' . $order['order_number'] . ' is confirmed.');
        redirect('/checkout/success/' . $order['order_number']);
    }

    public static function success(string $number): string
    {
        $order = Order::findByNumber($number);

        if ($order === null || !Order::visibleTo($order)) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Order not found']);
        }

        return render('checkout/success', [
            'order' => $order,
            'lines' => Order::itemsFor((int) $order['id']),
        ], ['title' => 'Order confirmed']);
    }

    /**
     * POST /checkout/success/{number}/claim-payment — the customer clicks
     * "I've sent the transfer". This never marks the order paid by itself;
     * it just tells staff to go check the bank account. Works for the
     * guest who just checked out (session) or the logged-in owner, same
     * rule as viewing the order (Order::visibleTo()).
     */
    public static function claimPayment(string $number): never
    {
        $order = Order::findByNumber($number);

        if ($order === null || !Order::visibleTo($order)) {
            http_response_code(404);
            redirect('/');
        }

        if (Order::claimPayment((int) $order['id'])) {
            Audit::log('order.payment_claimed', 'order', (int) $order['id'], 'Customer marked bank transfer as sent');
            Mailer::bankTransferClaimed($order);
            flash_set('success', "Thanks — we've let our team know to check for your transfer.");
        } else {
            flash_set('info', 'That order is not awaiting a bank transfer confirmation.');
        }

        redirect_back('/checkout/success/' . $order['order_number']);
    }

    // ------------------------------------------------------------------ helpers

    private static function ensureIdempotencyKey(): void
    {
        if (empty($_SESSION['checkout_key'])) {
            $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
        }
    }

    private static function sessionCoupon(array $items): ?array
    {
        return Coupon::fromSession(Cart::subtotal($items), Auth::id());
    }

    private static function methodFrom(string $raw): string
    {
        return in_array($raw, Order::METHODS, true) ? $raw : 'standard';
    }

    private static function paymentMethodFrom(string $raw): string
    {
        return $raw === 'bank_transfer' ? 'bank_transfer' : 'card';
    }

    private static function defaults(?array $user): array
    {
        $old = [
            'email'      => $user['email'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name'  => $user['last_name'] ?? '',
            'line1'      => '',
            'line2'      => '',
            'city'       => '',
            'postcode'   => '',
            'country'    => 'NG',
            'phone'      => '',
            'address_id' => '',
        ];

        if ($user) {
            $addresses = Address::forUser((int) $user['id']);
            foreach ($addresses as $a) {
                if ((int) $a['is_default_shipping'] === 1) {
                    return array_merge($old, [
                        'first_name' => $a['first_name'],
                        'last_name'  => $a['last_name'],
                        'line1'      => $a['line1'],
                        'line2'      => $a['line2'] ?? '',
                        'city'       => $a['city'],
                        'postcode'   => $a['postcode'],
                        'country'    => $a['country'],
                        'phone'      => $a['phone'] ?? '',
                        'address_id' => (string) $a['id'],
                    ]);
                }
            }
        }

        return $old;
    }

    /**
     * Prefer a saved address the user actually owns; otherwise take the
     * typed fields. Ownership is checked here so a guessed address_id
     * cannot ship to someone else's house.
     */
    private static function shippingFromPost(?array $user): array
    {
        $addressId = (int) ($_POST['address_id'] ?? 0);

        if ($user && $addressId > 0) {
            $saved = Address::findOwned($addressId, (int) $user['id']);
            if ($saved) {
                return [
                    'email'      => $user['email'],
                    'first_name' => $saved['first_name'],
                    'last_name'  => $saved['last_name'],
                    'line1'      => $saved['line1'],
                    'line2'      => $saved['line2'] ?? '',
                    'city'       => $saved['city'],
                    'postcode'   => $saved['postcode'],
                    'country'    => $saved['country'],
                    'phone'      => $saved['phone'] ?? '',
                    'address_id' => (string) $saved['id'],
                ];
            }
        }

        return [
            'email'      => strtolower(trim($_POST['email'] ?? ($user['email'] ?? ''))),
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name'] ?? ''),
            'line1'      => trim($_POST['line1'] ?? ''),
            'line2'      => trim($_POST['line2'] ?? ''),
            'city'       => trim($_POST['city'] ?? ''),
            'postcode'   => preg_replace('/\s+/', '', trim($_POST['postcode'] ?? '')) ?? '',
            'country'    => isset(Address::COUNTRIES[$_POST['country'] ?? '']) ? $_POST['country'] : 'NG',
            'phone'      => trim($_POST['phone'] ?? ''),
            'address_id' => '',
        ];
    }

    private static function validate(array $ship, array $items, ?array $coupon): array
    {
        $errors = [];

        if (!filter_var($ship['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email for the receipt.';
        }
        foreach (['first_name' => 'first name', 'last_name' => 'last name',
                  'line1' => 'address', 'city' => 'city'] as $key => $label) {
            if ($ship[$key] === '' || mb_strlen($ship[$key]) > 120) {
                $errors[] = "Please enter your {$label}.";
            }
        }
        $postcode = str_replace(' ', '', $ship['postcode']);
        if (!preg_match('/^\d{6}$/', $postcode)) {
            $errors[] = 'Please enter a 6-digit Nigerian postal code.';
        }

        foreach ($items as $item) {
            if ((int) $item['quantity'] > (int) $item['stock']) {
                $errors[] = $item['name'] . ' does not have enough stock for that quantity.';
            }
        }

        if ($coupon !== null) {
            $rej = Coupon::rejection($coupon, Cart::subtotal($items), Auth::id());
            if ($rej) {
                $errors[] = $rej;
            }
        }

        return $errors;
    }
}
