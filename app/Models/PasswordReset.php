<?php

declare(strict_types=1);

/**
 * Password-reset tokens.
 *
 * We store a HASH of the token, not the token itself — same reason we hash
 * passwords. The difference: password_hash() uses a random salt, so you
 * cannot look a row up by hash. Reset tokens are 32 random bytes (plenty of
 * entropy), so a fast SHA-256 hash is safe AND queryable.
 *
 * The raw token is emailed (or, in debug, shown once). Anyone who only has
 * the database sees a useless hash.
 */
class PasswordReset
{
    public const LIFETIME_MINUTES = 60;

    /** Create a token for this user. Returns the RAW token to send. */
    public static function create(int $userId): string
    {
        $raw  = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);

        Database::pdo()->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL'
        )->execute([$userId]);

        Database::pdo()->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
        )->execute([$userId, $hash, self::LIFETIME_MINUTES]);

        return $raw;
    }

    /** Valid, unused, unexpired row for this raw token, or null. */
    public static function findValid(string $raw): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([hash('sha256', $raw)]);

        return $stmt->fetch() ?: null;
    }

    public static function markUsed(int $id): void
    {
        Database::pdo()
            ->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([$id]);
    }
}
