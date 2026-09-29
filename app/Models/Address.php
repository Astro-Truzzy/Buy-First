<?php

declare(strict_types=1);

/**
 * Address model — saved delivery addresses for logged-in customers.
 * Guest checkout never touches this table; those details live only on the
 * order row (a snapshot), so they don't need an account.
 */
class Address
{
    public const COUNTRIES = ['NG' => 'Nigeria'];

    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM addresses WHERE user_id = ?
             ORDER BY is_default_shipping DESC, id ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /** Only returns the address if it belongs to this user — ownership check. */
    public static function findOwned(int $id, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM addresses WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO addresses
                (user_id, label, first_name, last_name, line1, line2,
                 city, postcode, country, phone, is_default_shipping, is_default_billing)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)'
        );
        $stmt->execute([
            $userId,
            $data['label']      ?? 'Home',
            $data['first_name'],
            $data['last_name'],
            $data['line1'],
            $data['line2']      !== '' ? $data['line2'] : null,
            $data['city'],
            $data['postcode'],
            $data['country'],
            $data['phone']      !== '' ? $data['phone'] : null,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): void
    {
        Database::pdo()->prepare(
            'UPDATE addresses
             SET label = ?, first_name = ?, last_name = ?, line1 = ?, line2 = ?,
                 city = ?, postcode = ?, country = ?, phone = ?
             WHERE id = ? AND user_id = ?'
        )->execute([
            $data['label']      ?? 'Home',
            $data['first_name'],
            $data['last_name'],
            $data['line1'],
            $data['line2']      !== '' ? $data['line2'] : null,
            $data['city'],
            $data['postcode'],
            $data['country'],
            $data['phone']      !== '' ? $data['phone'] : null,
            $id,
            $userId,
        ]);
    }

    /** Ownership-checked delete — a guessed id for someone else's address is a no-op. */
    public static function delete(int $id, int $userId): void
    {
        Database::pdo()
            ->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')
            ->execute([$id, $userId]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        if (self::findOwned($id, $userId) === null) {
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $pdo->prepare(
            'UPDATE addresses SET is_default_shipping = 0, is_default_billing = 0 WHERE user_id = ?'
        )->execute([$userId]);
        $pdo->prepare(
            'UPDATE addresses SET is_default_shipping = 1, is_default_billing = 1
             WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
        $pdo->commit();
    }

    /** Shared field rules for checkout and the account address book. */
    public static function validate(array $data): array
    {
        $errors = [];
        foreach (['first_name' => 'first name', 'last_name' => 'last name',
                  'line1' => 'address', 'city' => 'city'] as $key => $label) {
            if (trim($data[$key] ?? '') === '' || mb_strlen($data[$key]) > 120) {
                $errors[] = "Please enter your {$label}.";
            }
        }
        $postcode = str_replace(' ', '', $data['postcode'] ?? '');
        if (!preg_match('/^\d{6}$/', $postcode)) {
            $errors[] = 'Please enter a 6-digit Nigerian postal code.';
        }
        if (!isset(self::COUNTRIES[$data['country'] ?? ''])) {
            $errors[] = 'Please choose a country.';
        }

        return $errors;
    }

    public static function fromPost(): array
    {
        return [
            'label'      => trim($_POST['label'] ?? 'Home') ?: 'Home',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name'] ?? ''),
            'line1'      => trim($_POST['line1'] ?? ''),
            'line2'      => trim($_POST['line2'] ?? ''),
            'city'       => trim($_POST['city'] ?? ''),
            'postcode'   => preg_replace('/\s+/', '', trim($_POST['postcode'] ?? '')) ?? '',
            'country'    => isset(self::COUNTRIES[$_POST['country'] ?? '']) ? $_POST['country'] : 'NG',
            'phone'      => trim($_POST['phone'] ?? ''),
        ];
    }
}
