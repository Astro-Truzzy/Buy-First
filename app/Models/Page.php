<?php

declare(strict_types=1);

/**
 * Page model — CMS rows for help, company and legal copy.
 *
 * The PHP views stay dumb: they print title + sanitised HTML. Editors
 * (and later, admin) change the database, not the templates.
 */
class Page
{
    /** Footer / sidebar groupings. Keys are slugs. */
    public const GROUPS = [
        'Help' => [
            'help'        => 'Help Center',
            'shipping'    => 'Shipping & Delivery',
            'returns'     => 'Returns & Exchanges',
            'size-guides' => 'Size Guides',
            'contact'     => 'Contact Us',
        ],
        'Company' => [
            'about'          => 'About BuyFirst',
            'careers'        => 'Careers',
            'sustainability' => 'Sustainability',
            'journal'        => 'Journal',
            'stores'         => 'Store Locator',
        ],
        'Legal' => [
            'privacy'       => 'Privacy Policy',
            'terms'         => 'Terms & Conditions',
            'cookies'       => 'Cookie Policy',
            'accessibility' => 'Accessibility',
            'imprint'       => 'Company Information',
        ],
    ];

    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM pages ORDER BY slug');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function update(int $id, string $title, string $content): void
    {
        Database::pdo()->prepare('UPDATE pages SET title = ?, content = ? WHERE id = ?')
            ->execute([$title, $content, $id]);
    }

    public static function bySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM pages WHERE slug = ?');
        $stmt->execute([$slug]);

        return $stmt->fetch() ?: null;
    }

    /** Which group (Help / Company / Legal) a slug belongs to, if any. */
    public static function groupFor(string $slug): ?array
    {
        foreach (self::GROUPS as $name => $links) {
            if (isset($links[$slug])) {
                return ['name' => $name, 'links' => $links];
            }
        }

        return null;
    }
}
