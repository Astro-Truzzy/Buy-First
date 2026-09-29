<?php

declare(strict_types=1);

/**
 * Wishlist — saved products for a logged-in customer.
 *
 * The table is a user↔product link with a UNIQUE pair, so the same
 * product cannot be hearted twice. Guests have no row (no user_id);
 * they are sent to login, then back to the page they were on.
 *
 * Every query is pinned to Auth::id() — guessing another user's
 * product_id in a POST cannot touch their list.
 */
class Wishlist
{
    /** @var list<int>|null ids for this request, loaded once */
    private static ?array $ids = null;

    /** Product ids the current visitor has saved. Empty for guests. */
    public static function ids(): array
    {
        if (self::$ids !== null) {
            return self::$ids;
        }

        $userId = Auth::id();
        if ($userId === null) {
            return self::$ids = [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);

        return self::$ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function has(int $productId): bool
    {
        return in_array($productId, self::ids(), true);
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    public static function products(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        return $ids === [] ? [] : Product::byIds($ids);
    }

    /** @return 'added'|'removed' */
    public static function toggle(int $userId, int $productId): string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?'
        );
        $stmt->execute([$userId, $productId]);

        if ($stmt->fetchColumn()) {
            Database::pdo()->prepare(
                'DELETE FROM wishlists WHERE user_id = ? AND product_id = ?'
            )->execute([$userId, $productId]);
            self::$ids = null;

            return 'removed';
        }

        Database::pdo()->prepare(
            'INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)'
        )->execute([$userId, $productId]);
        self::$ids = null;

        return 'added';
    }
}
