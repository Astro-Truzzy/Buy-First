<?php

declare(strict_types=1);

/**
 * Central app configuration.
 *
 * This file turns raw .env strings into one tidy array the app can use.
 * Nothing else in the codebase should read $_ENV directly — it should
 * ask config() so all settings live in one predictable place.
 */

function config(): array
{
    static $config = null;

    if ($config === null) {
        $config = [
            'app' => [
                'name'  => env('APP_NAME', 'BuyFirst'),
                'url'   => env('APP_URL', 'http://localhost:8000'),
                // .env values are strings, so convert "true"/"false" to a real boolean.
                'debug' => env('APP_DEBUG', 'false') === 'true',
            ],
            'db' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'name' => env('DB_NAME', 'buyfirst'),
                'user' => env('DB_USER', 'root'),
                'pass' => env('DB_PASS', ''),
            ],
            'mail' => [
                'driver'      => env('MAIL_DRIVER', 'log'),
                'from'        => env('MAIL_FROM', 'orders@buyfirst.test'),
                'from_name'   => env('MAIL_FROM_NAME', 'BuyFirst'),
                'contact'     => env('MAIL_CONTACT', env('MAIL_FROM', 'orders@buyfirst.test')),
                'host'        => env('MAIL_HOST', ''),
                'port'        => env('MAIL_PORT', '587'),
                'encryption'  => env('MAIL_ENCRYPTION', 'tls'),
                'username'    => env('MAIL_USERNAME', ''),
                'password'    => env('MAIL_PASSWORD', ''),
                'timeout'     => env('MAIL_TIMEOUT', '20'),
            ],
        ];
    }

    return $config;
}
