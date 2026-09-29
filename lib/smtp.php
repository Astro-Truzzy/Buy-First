<?php

declare(strict_types=1);

/**
 * Tiny SMTP client. No Composer, no PHPMailer — just PHP streams talking
 * the protocol mail servers already speak.
 *
 * Conversation (Hostinger / Gmail / Mailtrap all look like this):
 *   1. Connect (plain TCP, or TLS from the first byte on port 465).
 *   2. EHLO — introduce ourselves; the server lists what it supports.
 *   3. STARTTLS on port 587, then EHLO again inside the encrypted tunnel.
 *   4. AUTH LOGIN — mailbox address + password, both Base64.
 *   5. MAIL FROM / RCPT TO / DATA — the actual message.
 *   6. QUIT.
 *
 * Callers never talk to this class. Mailer::send() is the public door.
 */
class SmtpClient
{
    /** @var resource|null */
    private $socket = null;

    private string $transcript = '';

    public function __construct(private array $mail)
    {
    }

    /**
     * Connect, authenticate, deliver one HTML email, disconnect.
     *
     * $extra may include:
     *   reply_to      — address for the Reply-To header
     *   reply_to_name — display name for that address
     */
    public static function send(string $to, string $subject, string $html, array $extra = []): void
    {
        $client = new self(config()['mail']);

        try {
            $client->connect();
            $client->deliver($to, $subject, $html, $extra);
        } finally {
            $client->quit();
            $client->persistTranscript();
        }
    }

    private function connect(): void
    {
        $host        = trim((string) ($this->mail['host'] ?? ''));
        $port        = (int) ($this->mail['port'] ?? 587);
        $encryption  = $this->encryption();
        $timeout     = max(5, (int) ($this->mail['timeout'] ?? 20));

        if ($host === '') {
            throw new RuntimeException('MAIL_HOST is empty. Set it in .env, or switch MAIL_DRIVER back to log.');
        }

        if ($encryption !== 'none' && !extension_loaded('openssl')) {
            throw new RuntimeException('The openssl PHP extension is required for SMTP TLS/SSL.');
        }

        $remote = $encryption === 'ssl'
            ? "ssl://{$host}:{$port}"
            : "tcp://{$host}:{$port}";

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                'crypto_method'     => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ],
        ]);

        $errno  = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            throw new RuntimeException("Could not connect to SMTP {$remote}: {$errstr} ({$errno})");
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, $timeout);
        stream_set_blocking($this->socket, true);

        $this->expect([220]);

        $features = $this->ehlo();

        if ($encryption === 'tls') {
            if (!in_array('STARTTLS', $features, true)) {
                throw new RuntimeException("SMTP server {$host} did not offer STARTTLS.");
            }
            $this->command('STARTTLS', [220]);
            $crypto = @stream_socket_enable_crypto(
                $this->socket,
                true,
                STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
            );
            if ($crypto !== true) {
                throw new RuntimeException('SMTP STARTTLS handshake failed.');
            }
            $features = $this->ehlo();
        }

        $user = (string) ($this->mail['username'] ?? '');
        $pass = (string) ($this->mail['password'] ?? '');

        if ($user !== '') {
            $this->authenticate($features, $user, $pass);
        }
    }

    private function deliver(string $to, string $subject, string $html, array $extra): void
    {
        $from      = $this->mail['from'];
        $fromName  = $this->mail['from_name'] ?? '';
        $message   = $this->buildMessage($to, $subject, $html, $from, $fromName, $extra);

        $this->command('MAIL FROM:<' . $from . '>', [250]);
        $this->command('RCPT TO:<' . $to . '>', [250, 251]);
        $this->command('DATA', [354]);
        $this->write($message . "\r\n.");
        $this->expect([250]);
    }

    private function ehlo(): array
    {
        $host = parse_url((string) (config()['app']['url'] ?? ''), PHP_URL_HOST);
        $name = is_string($host) && $host !== '' ? $host : 'localhost';

        $reply = $this->command('EHLO ' . $name, [250]);

        $features = [];
        foreach (preg_split('/\r\n|\n/', $reply) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^250[\s-](.+)$/', $line, $m)) {
                $features[] = strtoupper(trim($m[1]));
            }
        }

        return $features;
    }

    /** @param list<string> $features */
    private function authenticate(array $features, string $user, string $pass): void
    {
        $authLine = '';
        foreach ($features as $feature) {
            if (str_starts_with($feature, 'AUTH ')) {
                $authLine = $feature;
                break;
            }
        }

        $methods = $authLine === '' ? [] : (preg_split('/\s+/', substr($authLine, 5)) ?: []);

        if ($methods === [] || in_array('LOGIN', $methods, true)) {
            $this->authLogin($user, $pass);

            return;
        }

        if (in_array('PLAIN', $methods, true)) {
            $this->authPlain($user, $pass);

            return;
        }

        throw new RuntimeException('SMTP server offered no supported AUTH method (need LOGIN or PLAIN).');
    }

    private function authLogin(string $user, string $pass): void
    {
        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode($user), [334], true);
        $this->command(base64_encode($pass), [235], true);
    }

    private function authPlain(string $user, string $pass): void
    {
        $token = base64_encode("\0{$user}\0{$pass}");
        $this->command('AUTH PLAIN ' . $token, [235], true);
    }

    private function buildMessage(
        string $to,
        string $subject,
        string $html,
        string $from,
        string $fromName,
        array $extra
    ): string {
        $boundary = 'bf_' . bin2hex(random_bytes(12));
        $date     = date('r');
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . $this->messageIdHost() . '>';
        $plain    = $this->htmlToText($html);

        $headers = [
            'Date: ' . $date,
            'From: ' . $this->formatAddress($from, $fromName),
            'To: ' . $to,
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: ' . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $replyTo = $this->sanitizeHeader((string) ($extra['reply_to'] ?? ''));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $this->formatAddress(
                $replyTo,
                (string) ($extra['reply_to_name'] ?? '')
            );
        }

        $parts  = $this->mimePart('text/plain', $plain, $boundary);
        $parts .= $this->mimePart('text/html', $html, $boundary);
        $parts .= "--{$boundary}--\r\n";

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $parts;

        // SMTP ends DATA on a line that is only ".". A real line that starts
        // with a dot must be prefixed with another dot ("dot stuffing").
        $payload = preg_replace('/^\./m', '..', $payload) ?? $payload;

        return $payload;
    }

    private function mimePart(string $type, string $body, string $boundary): string
    {
        $encoded = trim(chunk_split(base64_encode($body), 76, "\r\n"));

        return "--{$boundary}\r\n"
            . "Content-Type: {$type}; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "\r\n"
            . $encoded . "\r\n";
    }

    private function htmlToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function formatAddress(string $email, string $name): string
    {
        $email = $this->sanitizeHeader($email);
        $name  = $this->sanitizeHeader($name);

        return $name === '' ? $email : $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        $value = $this->sanitizeHeader($value);
        if ($value === '' || preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function sanitizeHeader(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }

    private function messageIdHost(): string
    {
        $host = parse_url((string) (config()['app']['url'] ?? ''), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function encryption(): string
    {
        $value = strtolower(trim((string) ($this->mail['encryption'] ?? 'tls')));

        return match ($value) {
            'ssl', 'smtps' => 'ssl',
            'none', 'off', 'false' => 'none',
            default => 'tls',
        };
    }

    /** @param list<int> $expect */
    private function command(string $line, array $expect, bool $secret = false): string
    {
        $this->write($line, $secret);

        return $this->expect($expect);
    }

    private function write(string $line, bool $secret = false): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('SMTP socket is closed.');
        }

        $ok = fwrite($this->socket, $line . "\r\n");
        $this->transcript .= '> ' . ($secret ? '[redacted]' : $line) . "\n";

        if ($ok === false) {
            throw new RuntimeException('SMTP write failed.');
        }
    }

    /** @param list<int> $expect */
    private function expect(array $expect): string
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('SMTP socket is closed.');
        }

        $full = '';
        while (($line = fgets($this->socket, 2048)) !== false) {
            $this->transcript .= '< ' . $line;
            $full .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($full === '') {
            $meta = stream_get_meta_data($this->socket);
            $why  = !empty($meta['timed_out']) ? 'timed out' : 'closed the connection';
            throw new RuntimeException("SMTP server {$why}.");
        }

        $code = (int) substr($full, 0, 3);
        if (!in_array($code, $expect, true)) {
            throw new RuntimeException('SMTP unexpected reply ' . $code . ': ' . trim($full));
        }

        return $full;
    }

    private function quit(): void
    {
        if (!is_resource($this->socket)) {
            return;
        }

        try {
            $this->write('QUIT');
            @fgets($this->socket, 512);
        } catch (Throwable) {
            // Disconnecting is best-effort — the message may already be accepted.
        }

        fclose($this->socket);
        $this->socket = null;
    }

    private function persistTranscript(): void
    {
        if ($this->transcript === '' || empty(config()['app']['debug'])) {
            return;
        }

        $dir = dirname(__DIR__) . '/storage/mail';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        file_put_contents($dir . '/smtp.log', $this->transcript . "\n", FILE_APPEND | LOCK_EX);
    }
}
