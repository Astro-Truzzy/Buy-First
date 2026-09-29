<?php

declare(strict_types=1);

/**
 * Authentication: who is the current visitor?
 *
 * The entire concept of "being logged in" is one thing: the session holds
 * a user_id. Auth wraps that with caching and safety around login/logout.
 */
class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** Is anyone logged in? */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** The logged-in user's id, or null. */
    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    /**
     * The logged-in user's database row, or null. Fetched from the database
     * once per request (not stored in the session!) so changes like a role
     * downgrade or account lock apply immediately, not at next login.
     */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            if (!empty($_SESSION['user_id'])) {
                self::$user = User::findById((int) $_SESSION['user_id']);
            }
        }

        return self::$user;
    }

    public static function isAdmin(): bool
    {
        return (int) (self::user()['role_id'] ?? 0) === 1;
    }

    /**
     * Log a user in. session_regenerate_id() gives the browser a brand-new
     * session ID, defeating "session fixation" (an attacker planting a known
     * ID before you log in, then reusing it to become you).
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user  = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true); // new ID for the now-anonymous session
        self::$user  = null;
        self::$loaded = true;
    }
}

/**
 * Route guard (our simple "middleware"): call at the top of any controller
 * action that requires a logged-in user. Remembers where the visitor was
 * heading so we can send them there after login.
 */
function require_login(): void
{
    if (!Auth::check()) {
        $_SESSION['intended'] = safe_internal_path($_SERVER['REQUEST_URI'] ?? '/account', '/account');
        flash_set('info', 'Please sign in to continue.');
        redirect('/login');
    }
}

/**
 * Stronger guard for /admin: must be logged in AND role_id = 1.
 * A regular customer who types /admin gets a 403, not a dashboard.
 */
function require_admin(): void
{
    require_login();

    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo render('errors/forbidden', [], ['title' => 'Forbidden']);
        exit;
    }
}
