<?php

declare(strict_types=1);

/**
 * CSRF protection.
 *
 * One random token per session. Every form we render embeds it as a hidden
 * field; every POST request must send it back. A malicious site cannot read
 * our pages, so it can never know the token — its forged POSTs die here.
 */

/** The session's CSRF token, created on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes = cryptographically secure randomness (not rand()!).
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** The hidden input to drop inside every <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Called for EVERY POST request by the front controller.
 * hash_equals() compares in constant time — a normal === comparison leaks
 * timing information that can theoretically help an attacker guess the token.
 */
function csrf_verify(): void
{
    $sent = $_POST['_token'] ?? '';

    if (is_string($sent) && hash_equals(csrf_token(), $sent)) {
        return;
    }

    if (is_ajax()) {
        json_response(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    http_response_code(403);
    echo render('errors/403', [], ['title' => 'Request blocked']);
    exit;
}
