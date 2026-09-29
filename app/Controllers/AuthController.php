<?php

declare(strict_types=1);

/**
 * Auth controller — register, login, logout.
 *
 * Pattern used throughout:
 *   - validation fails  -> RE-RENDER the form with errors + the user's input
 *   - action succeeds   -> REDIRECT (POST-redirect-GET) with a flash message
 */
class AuthController
{
    // ---------------------------------------------------------------- register

    public static function showRegister(): string
    {
        if (Auth::check()) redirect('/account');

        if (isset($_GET['club'])) {
            $_SESSION['join_club'] = true;
        }

        return render('auth/register', [
            'errors' => [],
            'old'    => [],
            'club'   => !empty($_SESSION['join_club']),
        ], ['title' => 'Create Account']);
    }

    public static function register(): string
    {
        if (Auth::check()) redirect('/account');

        // trim() user text; NEVER trim passwords (spaces may be intentional).
        $first     = trim($_POST['first_name'] ?? '');
        $last      = trim($_POST['last_name'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['password_confirm'] ?? '';
        $newsletter = isset($_POST['newsletter']);
        $joinClub   = isset($_POST['club']) || !empty($_SESSION['join_club']);

        $errors = [];

        if ($first === '' || mb_strlen($first) > 60) {
            $errors[] = 'Please enter your first name (up to 60 characters).';
        }
        if ($last === '' || mb_strlen($last) > 60) {
            $errors[] = 'Please enter your last name (up to 60 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Your password must be at least 8 characters long.';
        }
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }
        if (!$errors && User::findByEmail($email) !== null) {
            $errors[] = 'An account with this email already exists. Try signing in instead.';
        }

        if ($errors) {
            // Re-render with errors + previous input. Passwords are never
            // echoed back — they would end up in the page source.
            return render('auth/register', [
                'errors' => $errors,
                'old'    => ['first_name' => $first, 'last_name' => $last,
                             'email' => $email, 'newsletter' => $newsletter],
                'club'   => $joinClub,
            ], ['title' => 'Create Account']);
        }

        if (!rate_allow('register', 8, 3600)) {
            return render('auth/register', [
                'errors' => ['Too many accounts created from this network. Try again later.'],
                'old'    => ['first_name' => $first, 'last_name' => $last,
                             'email' => $email, 'newsletter' => $newsletter],
                'club'   => $joinClub,
            ], ['title' => 'Create Account']);
        }

        $id = User::create($first, $last, $email, $password, $newsletter);
        if ($joinClub) {
            User::joinClub($id);
            unset($_SESSION['join_club']);
        }
        Auth::login(User::findById($id));
        Cart::mergeGuestIntoUser($id); // anything bagged as a guest comes along

        flash_set(
            'success',
            $joinClub
                ? "Welcome to BuyFirst Club, {$first}. Standard delivery is now free."
                : "Welcome to BuyFirst, {$first}. Your account is ready."
        );
        redirect($joinClub ? '/membership' : '/account');
    }

    // ---------------------------------------------------------------- login

    public static function showLogin(): string
    {
        if (Auth::check()) redirect('/account');

        if (isset($_GET['club'])) {
            $_SESSION['join_club'] = true;
        }

        return render('auth/login', [
            'errors' => [],
            'old'    => [],
            'club'   => !empty($_SESSION['join_club']),
        ], ['title' => 'Sign In']);
    }

    public static function login(): string
    {
        if (Auth::check()) redirect('/account');

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!rate_allow('login', 20, 900)) {
            return self::loginError('Too many attempts from this network. Try again in a few minutes.', $email);
        }

        $user = $email !== '' ? User::findByEmail($email) : null;

        // Locked out? Say so — this reveals nothing about the password.
        if ($user !== null && User::isLocked($user)) {
            return self::loginError(
                'Too many failed attempts. Try again in ' . User::lockedMinutesLeft($user) . ' minute(s).',
                $email
            );
        }

        // ONE message for "no such account" AND "wrong password" — telling
        // attackers which emails exist is called user enumeration.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            if ($user !== null) {
                User::recordFailedLogin($user);
            }
            return self::loginError('Email or password is incorrect.', $email);
        }

        User::clearFailedLogins((int) $user['id']);
        Auth::login($user);
        Cart::mergeGuestIntoUser((int) $user['id']); // guest bag follows them in

        if (!empty($_SESSION['join_club']) && empty($user['is_member'])) {
            User::joinClub((int) $user['id']);
            unset($_SESSION['join_club'], $_SESSION['intended']);
            flash_set('success', "You’re in the Club, {$user['first_name']}. Standard delivery is now free.");
            redirect('/membership');
        }

        // Send them where they were originally heading, or to their account.
        $intended = safe_internal_path($_SESSION['intended'] ?? '/account', '/account');
        unset($_SESSION['intended']);

        flash_set('success', "Welcome back, {$user['first_name']}.");
        redirect($intended);
    }

    private static function loginError(string $message, string $email): string
    {
        return render('auth/login', [
            'errors' => [$message],
            'old'    => ['email' => $email],
            'club'   => !empty($_SESSION['join_club']),
        ], ['title' => 'Sign In']);
    }

    // ---------------------------------------------------------------- logout

    /** POST only — a state change must never be triggerable by a mere link. */
    public static function logout(): never
    {
        Auth::logout();
        flash_set('success', 'You have been signed out.');
        redirect('/');
    }

    // ---------------------------------------------------------------- forgot / reset password

    public static function showForgot(): string
    {
        if (Auth::check()) redirect('/account');

        return render('auth/forgot', ['errors' => []], ['title' => 'Reset password']);
    }

    public static function sendForgot(): never
    {
        if (Auth::check()) redirect('/account');

        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!rate_allow('forgot', 5, 900)) {
            flash_set('success', 'If that email is on file, you will receive a reset link shortly.');
            redirect('/forgot-password');
        }

        $user  = $email !== '' ? User::findByEmail($email) : null;

        // Same message whether the email exists — no user enumeration.
        $message = 'If that email is on file, you will receive a reset link shortly.';

        if ($user !== null) {
            $token = PasswordReset::create((int) $user['id']);
            $url   = rtrim(config()['app']['url'], '/') . '/reset-password/' . $token;
            Mailer::passwordReset($user['email'], $url);

            if (config()['app']['debug']) {
                $message .= ' A copy was written to storage/mail/.';
            }
        }

        flash_set('success', $message);
        redirect('/forgot-password');
    }

    public static function showReset(string $token): string
    {
        if (PasswordReset::findValid($token) === null) {
            flash_set('error', 'That reset link is invalid or has expired. Request a new one.');
            redirect('/forgot-password');
        }

        return render('auth/reset', [
            'token'  => $token,
            'errors' => [],
        ], ['title' => 'Choose a new password']);
    }

    public static function reset(string $token): string
    {
        $row = PasswordReset::findValid($token);
        if ($row === null) {
            flash_set('error', 'That reset link is invalid or has expired. Request a new one.');
            redirect('/forgot-password');
        }

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $errors   = [];

        if (strlen($password) < 8) {
            $errors[] = 'Your password must be at least 8 characters long.';
        }
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }

        if ($errors) {
            return render('auth/reset', [
                'token'  => $token,
                'errors' => $errors,
            ], ['title' => 'Choose a new password']);
        }

        User::setPassword((int) $row['user_id'], $password);
        PasswordReset::markUsed((int) $row['id']);

        $user = User::findById((int) $row['user_id']);
        Auth::login($user);
        Cart::mergeGuestIntoUser((int) $user['id']);

        flash_set('success', 'Your password has been updated. You are signed in.');
        redirect('/account');
    }
}
