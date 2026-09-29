<?php

declare(strict_types=1);

/**
 * Campaign model — the homepage hero banners, managed from admin later.
 */
class Campaign
{
    /** Active campaigns in display order, for the rotating hero. */
    public static function active(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, title, subtitle, cta_text, cta_url, image_url
             FROM campaigns
             WHERE is_active = 1
             ORDER BY sort_order'
        );

        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM campaigns ORDER BY sort_order, id')
            ->fetchAll();
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::pdo()->prepare('UPDATE campaigns SET is_active = ? WHERE id = ?')
            ->execute([(int) $active, $id]);
    }
}
