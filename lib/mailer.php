<?php

declare(strict_types=1);

/**
 * Transactional mailer.
 *
 * Callers never care how the message leaves the building. They call
 * Mailer::orderConfirmation() (or send()) and we pick a driver from .env:
 *
 *   MAIL_DRIVER=log   write an HTML file under storage/mail/ (local inbox)
 *   MAIL_DRIVER=smtp  talk SMTP to Hostinger / Gmail / Mailtrap / …
 *
 * Swapping the driver does not change any controller.
 */
class Mailer
{
    /**
     * @param array{reply_to?:string,reply_to_name?:string} $extra
     */
    public static function send(string $to, string $subject, string $html, array $extra = []): void
    {
        $to      = str_replace(["\r", "\n"], '', $to);
        $subject = str_replace(["\r", "\n"], '', $subject);

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Refusing to send mail to an invalid address.');
        }

        $driver = strtolower(trim((string) (config()['mail']['driver'] ?? 'log')));

        if ($driver === 'smtp') {
            SmtpClient::send($to, $subject, $html, $extra);

            return;
        }

        self::writeLog($to, $subject, $html, $extra);
    }

    public static function orderConfirmation(array $order, array $lines): void
    {
        self::send(
            $order['email'],
            'Order ' . $order['order_number'] . ' confirmed',
            view('emails/order-confirmation', ['order' => $order, 'lines' => $lines])
        );
    }

    public static function orderShipped(array $order, array $lines): void
    {
        self::send(
            $order['email'],
            'Order ' . $order['order_number'] . ' has shipped',
            view('emails/order-shipped', ['order' => $order, 'lines' => $lines])
        );
    }

    /** Tells staff a customer claims they've sent a bank transfer. */
    public static function bankTransferClaimed(array $order): void
    {
        $inbox = config()['mail']['contact'] ?: config()['mail']['from'];

        self::send(
            $inbox,
            'Verify bank transfer for order ' . $order['order_number'],
            view('emails/bank-transfer-claimed', ['order' => $order])
        );
    }

    public static function passwordReset(string $to, string $url): void
    {
        self::send($to, 'Reset your BuyFirst password', view('emails/password-reset', [
            'url' => $url,
        ]));
    }

    public static function contact(string $name, string $email, string $message): void
    {
        $inbox = config()['mail']['contact'] ?: config()['mail']['from'];

        self::send(
            $inbox,
            'Contact form: ' . $name,
            view('emails/contact', [
                'name'    => $name,
                'email'   => $email,
                'message' => $message,
            ]),
            [
                'reply_to'      => $email,
                'reply_to_name' => $name,
            ]
        );
    }

    /** @param array{reply_to?:string,reply_to_name?:string} $extra */
    private static function writeLog(string $to, string $subject, string $html, array $extra): void
    {
        $dir = dirname(__DIR__) . '/storage/mail';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create storage/mail.');
        }

        $stamp = date('Ymd-His');
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $subject) ?? 'mail');
        $slug  = trim($slug, '-') ?: 'mail';
        $file  = $dir . '/' . $stamp . '-' . $slug . '.html';

        $from = config()['mail']['from_name'] . ' <' . config()['mail']['from'] . '>';
        $meta = 'To: ' . e($to) . "\n"
            . 'From: ' . e($from) . "\n"
            . 'Subject: ' . e($subject) . "\n"
            . 'Date: ' . e(date('r'));

        $replyTo = $extra['reply_to'] ?? '';
        if ($replyTo !== '') {
            $label = $extra['reply_to_name'] ?? '';
            $meta .= "\nReply-To: " . e($label !== '' ? "{$label} <{$replyTo}>" : $replyTo);
        }

        $document = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<title>' . e($subject) . '</title></head><body>'
            . '<pre style="font:12px/1.4 monospace;color:#555;border-bottom:1px solid #ddd;padding-bottom:12px">'
            . $meta
            . '</pre>'
            . $html
            . '</body></html>';

        file_put_contents($file, $document);
    }
}
