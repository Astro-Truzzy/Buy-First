<?php

/**
 * Router script for PHP's built-in development server.
 *
 * Started with:  php -S localhost:8000 -t public public/router.php
 *
 * If the URL points at a real file INSIDE public/ (css, js, image),
 * return false so the server serves that file. Otherwise run index.php.
 *
 * The path is normalised so /assets/../../.env cannot escape public/.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (str_contains($path, '..') || str_contains($path, "\0")) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$public = realpath(__DIR__);
$target = realpath(__DIR__ . $path);

if (
    $path !== '/'
    && $public !== false
    && $target !== false
    && is_file($target)
    && str_starts_with($target, $public . DIRECTORY_SEPARATOR)
) {
    return false;
}

require __DIR__ . '/index.php';
