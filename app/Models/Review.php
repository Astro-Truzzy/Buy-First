<?php

declare(strict_types=1);

/**
 * Review model — product reviews and their aggregate ratings.
 */
class Review
{
    /**
     * Approved reviews for a product, newest first.
     * A JOIN in its natural habitat: each review pairs with exactly ONE
     * user, so no row duplication — we get the reviewer's name in one query.
     */
    public static function approvedFor(int $productId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT r.rating, r.title, r.body, r.is_verified_purchase, r.created_at,
                    u.first_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    /** Average rating + count, for the stars under the product name. */
    public static function statsFor(int $productId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count
             FROM reviews WHERE product_id = ? AND status = 'approved'"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch();

        return [
            'avg'   => round((float) $row['avg_rating'], 1),
            'count' => (int) $row['review_count'],
        ];
    }

    public static function pending(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT r.*, p.name AS product_name, p.slug AS product_slug,
                    u.first_name, u.last_name
             FROM reviews r
             JOIN products p ON p.id = r.product_id
             JOIN users u ON u.id = r.user_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC"
        );

        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            return;
        }
        Database::pdo()->prepare('UPDATE reviews SET status = ? WHERE id = ?')
            ->execute([$status, $id]);
    }

    public static function pendingCount(): int
    {
        return (int) Database::pdo()
            ->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")
            ->fetchColumn();
    }

    /** One review per customer per product — used to hide the form. */
    public static function byUserFor(int $productId, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, status FROM reviews WHERE product_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$productId, $userId]);

        return $stmt->fetch() ?: null;
    }

    public static function create(
        int $productId,
        int $userId,
        int $rating,
        string $title,
        string $body,
        bool $verified
    ): void {
        Database::pdo()->prepare(
            "INSERT INTO reviews
                (product_id, user_id, rating, title, body, is_verified_purchase, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([$productId, $userId, $rating, $title, $body, (int) $verified]);
    }
}
