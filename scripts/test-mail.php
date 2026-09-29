<?php

/**
 * Send one test message through the configured mail driver.
 *
 *   C:\xampp\php\php.exe scripts\test-mail.php you@example.com
 *
 * With MAIL_DRIVER=log the HTML lands in storage/mail/.
 * With MAIL_DRIVER=smtp it goes out through the SMTP server in .env.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/lib/helpers.php';
require $root . '/lib/smtp.php';
require $root . '/lib/mailer.php';

load_env($root . '/.env');
require $root . '/config/config.php';

$to = strtolower(trim((string) ($argv[1] ?? '')));

if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/test-mail.php you@example.com\n");
    exit(1);
}

$driver = config()['mail']['driver'];
$host   = config()['mail']['host'] ?: '(none)';

echo "Driver: {$driver}\n";
if ($driver === 'smtp') {
    echo "SMTP:   {$host}:" . config()['mail']['port'] . ' (' . config()['mail']['encryption'] . ")\n";
}
echo "To:     {$to}\n";

try {
    Mailer::send(
        $to,
        'BuyFirst SMTP test',
        '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#0a0a0a">'
        . '<p style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#e01e2f;font-weight:700">BuyFirst</p>'
        . '<h1 style="font-size:24px;text-transform:uppercase;margin:0 0 12px">SMTP is working.</h1>'
        . '<p>This is a test from the BuyFirst mailer. If you can read this, the configured driver delivered the message.</p>'
        . '<p style="color:#8a8a84;font-size:13px">BuyFirst Ltd · Gear up. Move first.</p>'
        . '</div>'
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Send failed: ' . $e->getMessage() . "\n");
    if (config()['app']['debug'] && is_file($root . '/storage/mail/smtp.log')) {
        fwrite(STDERR, "See storage/mail/smtp.log for the SMTP transcript.\n");
    }
    exit(1);
}

if ($driver === 'log') {
    echo "Wrote an HTML file under storage/mail/. Open the newest .html — that is the inbox.\n";
} else {
    echo "Sent. Check the inbox for {$to} (and the spam folder once).\n";
    if (config()['app']['debug']) {
        echo "Transcript: storage/mail/smtp.log\n";
    }
}
