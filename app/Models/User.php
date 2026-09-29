<?php

declare(strict_types=1);

/**
 * User model — accounts, credentials and login rate-limiting.
 */
class User
{
    /** After this many wrong passwords, the account locks... */
    public const MAX_ATTEMPTS = 5;

    /** ...for this many minutes. */
    public const LOCK_MINUTES = 15;

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Create an account. The password is hashed HERE, at the last moment
     * before storage — plain passwords never touch the database.
     */
    public static function create(string $firstName, string $lastName, string $email,
                                  string $password, bool $newsletter): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (role_id, first_name, last_name, email, password_hash, newsletter_opt_in)
             VALUES (2, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            (int) $newsletter,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** Free BuyFirst Club — flips the flag that unlocks free standard delivery. */
    public static function joinClub(int $id): void
    {
        Database::pdo()->prepare('UPDATE users SET is_member = 1 WHERE id = ?')->execute([$id]);
    }

    /** Is this account currently locked out from failed attempts? */
    public static function isLocked(array $user): bool
    {
        return $user['locked_until'] !== null
            && strtotime($user['locked_until']) > time();
    }

    /** Minutes until a locked account unlocks (for the error message). */
    public static function lockedMinutesLeft(array $user): int
    {
        return max(1, (int) ceil((strtotime($user['locked_until']) - time()) / 60));
    }

    /**
     * Record one wrong password. On the Nth failure, lock the account —
     * this turns a bot's 10,000 guesses into 5 guesses per 15 minutes.
     */
    public static function recordFailedLogin(array $user): void
    {
        $failed = (int) $user['failed_logins'] + 1;

        if ($failed >= self::MAX_ATTEMPTS) {
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET failed_logins = 0,
                        locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                 WHERE id = ?'
            );
            $stmt->execute([self::LOCK_MINUTES, $user['id']]);
        } else {
            $stmt = Database::pdo()->prepare('UPDATE users SET failed_logins = ? WHERE id = ?');
            $stmt->execute([$failed, $user['id']]);
        }
    }

    /** Successful login wipes the failure count. */
    public static function clearFailedLogins(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function updateProfile(int $id, string $first, string $last, string $email, bool $newsletter): void
    {
        Database::pdo()->prepare(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, newsletter_opt_in = ?
             WHERE id = ?'
        )->execute([$first, $last, $email, (int) $newsletter, $id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::pdo()->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        )->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /** True if this email belongs to a *different* account. */
    public static function emailTakenByOther(string $email, int $exceptId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM users WHERE email = ? AND id != ?'
        );
        $stmt->execute([$email, $exceptId]);

        return $stmt->fetch() !== false;
    }
}
