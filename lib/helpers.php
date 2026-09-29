<?php

/**
 * Small global helper functions used everywhere in BuyFirst.
 * Kept deliberately tiny — each one does exactly one job.
 */

declare(strict_types=1);

/**
 * Read the .env file once and load every KEY=value line into $_ENV.
 *
 * $_ENV is a "superglobal": an array PHP makes available in every file
 * without you having to pass it around.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException(
            "Missing .env file at {$path}. Copy .env.example to .env first."
        );
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        // Skip comment lines and anything without an equals sign.
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);

        // MAIL_PASSWORD="p@ss=word" keeps the equals sign and drops the quotes.
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[trim($key)] = $value;
    }
}

/**
 * Read one setting from the environment, with an optional fallback.
 * Usage: env('DB_NAME', 'buyfirst')
 */
function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $default;
}

/**
 * Escape a value for safe output inside HTML.
 *
 * This is our defence against XSS (cross-site scripting): if a user saves
 * their name as <script>stealCookies()</script> and we print it raw, that
 * script runs in every visitor's browser. htmlspecialchars() converts the
 * dangerous characters (< > " ' &) into harmless text, so the browser
 * DISPLAYS the code instead of RUNNING it.
 *
 * Rule for this whole project: any dynamic value printed into HTML goes
 * through e(), except trusted CMS HTML which goes through safe_html().
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Allow a tight set of tags through for CMS pages. Everything else
 * (including <script>) is stripped. This is NOT a full HTML sanitizer —
 * a real CMS would use something like HTMLPurifier — but it stops the
 * obvious stored-XSS footgun of echoing database HTML raw.
 */
function safe_html(string $html): string
{
    $html = strip_tags($html, '<p><h2><h3><ul><ol><li><a><strong><em><br><table><thead><tbody><tr><th><td><code>');
    $html = preg_replace('/\s(?:style|on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/href\s*=\s*([\'"]?)\s*(?:javascript|data|vbscript):/i', 'href=$1#', $html) ?? $html;

    return $html;
}

/**
 * Render a view file from app/Views and return the finished HTML.
 *
 * $data is an array like ['product' => $product]. extract() turns each
 * array key into a local variable ($product) that the view can use, so
 * views stay clean: just HTML with small PHP echoes.
 */
function view(string $name, array $data = []): string
{
    if (str_contains($name, '..') || !preg_match('/^[a-z0-9\/_-]+$/i', $name)) {
        throw new RuntimeException("Invalid view name: {$name}");
    }

    $file = dirname(__DIR__) . '/app/Views/' . $name . '.php';

    if (!is_file($file)) {
        throw new RuntimeException("View not found: {$name}");
    }

    extract($data, EXTR_SKIP);

    // Output buffering: ob_start() tells PHP "don't send output to the
    // browser yet — collect it". That lets us capture the view's HTML
    // into a string and decide when to send it.
    ob_start();
    require $file;
    return ob_get_clean();
}

/**
 * Send the browser to another URL and stop the script.
 * Used after form submissions (e.g. after adding to cart, go to /cart).
 */
function redirect(string $path): never
{
    header('Location: ' . safe_internal_path($path, '/'));
    exit;
}

/**
 * Flash messages: a note stored in the session, displayed exactly ONCE on
 * the next page view, then deleted. Used with the POST-redirect-GET pattern
 * ("Account created!" survives the redirect, then disappears).
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']); // read once, then gone

    return $flash;
}

/**
 * Format an amount of money for display: money('129990') => "₦129,990".
 * Prices come out of MySQL DECIMAL columns as strings, so we accept both.
 * Naira retail drops kobo — shoppers expect whole naira.
 */
function money(string|float $amount): string
{
    return '₦' . number_format((float) $amount, 0);
}

/** ISO country code → label. The shop ships inside Nigeria only. */
function country_name(?string $code): string
{
    return match ($code) {
        'NG' => 'Nigeria',
        default => $code ?? '',
    };
}

/**
 * Render a page view and wrap it in the site layout (header + footer).
 *
 * $page options:
 *   'title'             — the browser tab title
 *   'transparentHeader' — true on pages with a full-bleed hero, so the
 *                         header starts see-through and turns solid on scroll
 */
function render(string $viewName, array $data = [], array $page = []): string
{
    $content = view($viewName, $data);

    return view('layouts/main', [
        'content'           => $content,
        'title'             => $page['title'] ?? config()['app']['name'],
        'transparentHeader' => $page['transparentHeader'] ?? false,
    ]);
}

/** Same idea as render(), but wraps the admin chrome instead of the shop. */
function render_admin(string $viewName, array $data = [], array $page = []): string
{
    $content = view($viewName, $data);

    return view('layouts/admin', [
        'content' => $content,
        'title'   => $page['title'] ?? 'Admin',
    ]);
}

/**
 * Send a JSON response and stop. Used by the search typeahead — that
 * request wants data, not a full HTML page.
 */
function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * True when the request came from our own fetch() calls (the cart drawer,
 * the search typeahead) rather than a normal form submit or link click.
 */
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

/**
 * Cookie consent. GDPR splits cookies into:
 *   essential — the site cannot work without them (session, CSRF)
 *   all       — essential + optional (recently viewed, any future analytics)
 *
 * An empty string means the visitor has not chosen yet — show the banner.
 */
function consent(): string
{
    $value = $_COOKIE['bf_consent'] ?? '';

    return in_array($value, ['essential', 'all'], true) ? $value : '';
}

function clear_consent(): void
{
    setcookie('bf_consent', '', cookie_options(time() - 3600));
    unset($_COOKIE['bf_consent']);
}

function set_consent(string $choice): void
{
    $choice = $choice === 'all' ? 'all' : 'essential';

    setcookie('bf_consent', $choice, cookie_options(time() + 365 * 24 * 60 * 60));
    $_COOKIE['bf_consent'] = $choice;

    // Rejecting optional cookies means we must drop the ones we already set.
    if ($choice !== 'all') {
        setcookie('bf_viewed', '', cookie_options(time() - 3600));
        unset($_COOKIE['bf_viewed']);
    }
}

/**
 * Recently viewed product ids, stored in a first-party cookie ONLY when
 * the visitor accepted optional cookies. This is the concrete reason the
 * cookie banner is not theatre — Reject actually changes behaviour.
 */
function remember_viewed(int $productId): void
{
    if (consent() !== 'all' || $productId < 1) {
        return;
    }

    $ids = array_values(array_unique(array_filter(
        array_map('intval', explode(',', $_COOKIE['bf_viewed'] ?? ''))
    )));
    array_unshift($ids, $productId);
    $ids   = array_slice(array_values(array_unique($ids)), 0, 8);
    $value = implode(',', $ids);

    setcookie('bf_viewed', $value, cookie_options(time() + 90 * 24 * 60 * 60));
    $_COOKIE['bf_viewed'] = $value;
}

function recently_viewed_products(int $limit = 4, int $exceptId = 0): array
{
    if (consent() !== 'all') {
        return [];
    }

    $ids = array_values(array_filter(
        array_map('intval', explode(',', $_COOKIE['bf_viewed'] ?? '')),
        fn(int $id): bool => $id > 0 && $id !== $exceptId
    ));

    return $ids === [] ? [] : Product::byIds(array_slice($ids, 0, $limit));
}

/**
 * Redirect to the previous page, but only if that URL is on THIS site.
 * Blindly trusting HTTP_REFERER is an open-redirect: an attacker could
 * send someone to /consent and bounce them to evil.example afterwards.
 */
function redirect_back(string $fallback = '/'): never
{
    $candidate = (string) ($_POST['return'] ?? $_SERVER['HTTP_REFERER'] ?? $fallback);
    redirect(safe_internal_path($candidate, $fallback));
}
