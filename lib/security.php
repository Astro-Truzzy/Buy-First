<?php

declare(strict_types=1);

/**
 * Cross-cutting security helpers: HTTPS detection, cookie flags,
 * response headers, safe internal redirects, and IP rate limits.
 */

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/** Shared flags for every cookie we set (session, consent, recently viewed). */
function cookie_options(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => '/',
        'samesite' => 'Lax',
        'httponly' => true,
        'secure'   => is_https(),
    ];
}

/**
 * Tell the browser how this site may be framed, sniffed, and loaded.
 * CSP is a allow-list: if it is not listed, the browser blocks it.
 */
function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; "
        . "img-src 'self' https://images.unsplash.com data:; "
        . "form-action 'self'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'none'"
    );
}

/**
 * Only allow a same-site relative path. Blocks open redirects such as
 * //evil.example (protocol-relative) and https://evil.example.
 */
function safe_internal_path(string $candidate, string $fallback = '/'): string
{
    if ($candidate === '' || str_contains($candidate, "\0") || str_contains($candidate, '..')) {
        return $fallback;
    }

    $path  = parse_url($candidate, PHP_URL_PATH);
    $query = parse_url($candidate, PHP_URL_QUERY);

    if (!is_string($path) || $path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
        return $fallback;
    }

    return is_string($query) && $query !== '' ? $path . '?' . $query : $path;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Allow at most $max hits in $windowSeconds from this IP for $bucket.
 * Stored as a tiny JSON file under storage/rate/ — no extra table needed.
 * Returns true if the request may proceed.
 */
function rate_allow(string $bucket, int $max, int $windowSeconds): bool
{
    $dir = dirname(__DIR__) . '/storage/rate';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return true; // fail open so a disk issue does not lock the shop
    }

    $file = $dir . '/' . hash('sha256', $bucket . '|' . client_ip()) . '.json';
    $now  = time();
    $hits = [];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        $hits    = is_array($decoded) ? $decoded : [];
        $hits    = array_values(array_filter(
            $hits,
            fn($t): bool => is_int($t) && $t > $now - $windowSeconds
        ));
    }

    if (count($hits) >= $max) {
        return false;
    }

    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);

    return true;
}
