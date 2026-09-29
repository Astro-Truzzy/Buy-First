<?php

declare(strict_types=1);

/**
 * Product model — all database reads/writes for products live here.
 * Models return plain arrays; they never echo HTML (that is the view's job).
 */
class Product
{
    public const PER_PAGE = 12;

    /** The only sports that exist — used to validate user input. */
    public const SPORTS = ['running', 'training', 'basketball', 'football', 'lifestyle'];

    public const BADGES = ['just-in', 'best-seller', 'member-exclusive', 'limited-drop'];

    public const GENDERS = ['men', 'women', 'kids', 'unisex'];

    /** Size rows the admin can stamp in when creating a product. */
    public const SIZE_PRESETS = [
        'footwear' => ['EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'],
        'kids'     => ['EU 32', 'EU 33', 'EU 34', 'EU 35', 'EU 37'],
        'apparel'  => ['XS', 'S', 'M', 'L', 'XL'],
        'one'      => ['One Size'],
    ];

    /**
     * Admin catalog sorts. Keys come from ?sort= — values are our SQL, never
     * the user's string, so ORDER BY stays injection-safe.
     */
    private const ADMIN_SORTS = [
        'category' => 'c.sort_order ASC, p.name ASC',
        'name'     => 'p.name ASC',
        'newest'   => 'p.id DESC',
        'price'    => 'COALESCE(p.sale_price, p.price) ASC',
        'stock'    => 'stock ASC, p.name ASC',
    ];

    /**
     * Sort options WHITELIST. The user sends a key like "price-low"; we look
     * it up here and use OUR SQL fragment. User text never enters the SQL —
     * you cannot use a ? placeholder in ORDER BY, so this is the safe pattern.
     */
    private const SORTS = [
        'featured'   => 'p.is_featured DESC, (p.badge IS NOT NULL) DESC, p.id ASC',
        'newest'     => 'p.id DESC',
        'price-low'  => 'COALESCE(p.sale_price, p.price) ASC',
        'price-high' => 'COALESCE(p.sale_price, p.price) DESC',
        'rating'     => "COALESCE((SELECT AVG(r.rating) FROM reviews r
                          WHERE r.product_id = p.id AND r.status = 'approved'), 0) DESC",
    ];

    /**
     * Columns every product CARD needs (grid/rail tiles). image/image_alt is
     * the lowest sort_order (the main shot); image_hover/image_hover_alt is
     * the next one, shown when the shopper hovers the card.
     */
    private const CARD_COLUMNS = "p.id, p.name, p.slug, p.gender, p.sport, p.price, p.sale_price, p.badge,
        (SELECT url FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1) AS image,
        (SELECT alt FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1) AS image_alt,
        (SELECT url FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1 OFFSET 1) AS image_hover,
        (SELECT alt FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1 OFFSET 1) AS image_hover_alt";

    // -------------------------------------------------------------------
    // Home page rails
    // -------------------------------------------------------------------

    private static function cards(string $where, array $params, string $orderBy, int $limit): array
    {
        $limit = (int) $limit;

        $sql = 'SELECT ' . self::CARD_COLUMNS . "
                FROM products p
                WHERE p.is_active = 1 AND {$where}
                ORDER BY {$orderBy}
                LIMIT {$limit}";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return self::attachImages($stmt->fetchAll());
    }

    /**
     * Attach the full image list to each card row in ONE query, so the card's
     * thumbnail row can drive the main image on hover. Grouped in PHP to avoid
     * the row duplication a JOIN to product_images would cause.
     *
     * @param list<array<string, mixed>> $rows card rows, each carrying an 'id'
     * @return list<array<string, mixed>> same rows, each with images => list<{url, alt}>
     */
    private static function attachImages(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $ids = array_column($rows, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::pdo()->prepare(
            "SELECT product_id, url, alt FROM product_images
             WHERE product_id IN ({$in}) ORDER BY sort_order, id"
        );
        $stmt->execute($ids);

        $byProduct = [];
        foreach ($stmt->fetchAll() as $img) {
            $byProduct[(int) $img['product_id']][] = ['url' => $img['url'], 'alt' => $img['alt']];
        }

        foreach ($rows as &$row) {
            $row['images'] = $byProduct[(int) $row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }

    public static function justIn(int $limit = 8): array
    {
        return self::cards('1=1', [], "(p.badge = 'just-in') DESC, p.id DESC", $limit);
    }

    public static function bestSellers(int $limit = 4): array
    {
        return self::cards("p.badge = 'best-seller'", [], 'p.id ASC', $limit);
    }

    public static function memberExclusives(int $limit = 4): array
    {
        return self::cards("p.badge = 'member-exclusive'", [], 'p.id ASC', $limit);
    }

    /**
     * Featured products for the homepage Trending tiles. Unlike other rails,
     * the image here can be overridden per-product via trending_image —
     * falling back to the normal product_images row when not set — so a
     * custom Trending photo never touches the product page, cart, or any
     * other place the product's real photography is shown.
     */
    public static function featured(int $limit = 6): array
    {
        $sql = "SELECT p.id, p.name, p.slug,
                COALESCE(p.trending_image,
                    (SELECT url FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1)) AS image,
                COALESCE(p.trending_image_alt,
                    (SELECT alt FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1),
                    p.name) AS image_alt
                FROM products p
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.id ASC
                LIMIT " . (int) $limit;

        return Database::pdo()->query($sql)->fetchAll();
    }

    // -------------------------------------------------------------------
    // Listing pages (PLP): base scope + user filters + sort + pagination
    // -------------------------------------------------------------------

    /**
     * Run a listing query. $baseWhere/$baseParams define the PAGE's scope
     * (e.g. "p.gender = ?" for /men) and come from our controllers.
     * $filters holds USER input from the URL — every value is validated
     * against a whitelist or passed as a bound parameter, never trusted.
     *
     * Returns: products for this page, total count, page count, current page.
     */
    public static function listing(string $baseWhere, array $baseParams, array $filters, int $page): array
    {
        [$where, $params] = self::applyFilters($baseWhere, $baseParams, $filters);

        $orderBy = self::SORTS[$filters['sort'] ?? ''] ?? self::SORTS['featured'];

        // Query 1: how many products match in total (for the count + pages).
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        // Query 2: the actual page of products.
        $sql = 'SELECT ' . self::CARD_COLUMNS . "
                FROM products p
                WHERE {$where}
                ORDER BY {$orderBy}
                LIMIT " . self::PER_PAGE . " OFFSET {$offset}";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return [
            'products' => self::attachImages($stmt->fetchAll()),
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
        ];
    }

    /**
     * Translate user filters into SQL conditions. Note the pattern for every
     * filter: validate against a known list, or bind through placeholders.
     */
    private static function applyFilters(string $baseWhere, array $baseParams, array $filters): array
    {
        $where  = "p.is_active = 1 AND {$baseWhere}";
        $params = $baseParams;

        if (!empty($filters['sport']) && in_array($filters['sport'], self::SPORTS, true)) {
            $where .= ' AND p.sport = ?';
            $params[] = $filters['sport'];
        }

        if (!empty($filters['badge']) && in_array($filters['badge'], self::BADGES, true)) {
            $where .= ' AND p.badge = ?';
            $params[] = $filters['badge'];
        }

        // Size/color live on VARIANTS, not products. EXISTS asks: "is there
        // at least one variant of this product matching?" — without joining
        // (which would duplicate product rows per matching variant).
        if (!empty($filters['size'])) {
            $sizes = array_slice((array) $filters['size'], 0, 20);
            $in    = implode(',', array_fill(0, count($sizes), '?')); // "?,?,?"
            $where .= " AND EXISTS (SELECT 1 FROM product_variants v
                        WHERE v.product_id = p.id AND v.size IN ({$in}))";
            $params = array_merge($params, $sizes);
        }

        if (!empty($filters['color'])) {
            $colors = array_slice((array) $filters['color'], 0, 20);
            $in     = implode(',', array_fill(0, count($colors), '?'));
            $where .= " AND EXISTS (SELECT 1 FROM product_variants v
                        WHERE v.product_id = p.id AND v.color IN ({$in}))";
            $params = array_merge($params, $colors);
        }

        // Price buckets compare the EFFECTIVE price (sale price when set).
        $where .= match ($filters['price'] ?? null) {
            'under-50' => ' AND COALESCE(p.sale_price, p.price) < 50000',
            '50-100'   => ' AND COALESCE(p.sale_price, p.price) BETWEEN 50000 AND 100000',
            'over-100' => ' AND COALESCE(p.sale_price, p.price) > 100000',
            default    => '',
        };

        return [$where, $params];
    }

    /**
     * Distinct sizes and colors available within the current page scope,
     * to build the filter sidebar. A JOIN works cleanly here because we
     * only SELECT the variant column — no duplicated product data comes back.
     */
    public static function filterOptions(string $baseWhere, array $baseParams): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT DISTINCT v.size FROM product_variants v
                               JOIN products p ON p.id = v.product_id
                               WHERE p.is_active = 1 AND {$baseWhere}");
        $stmt->execute($baseParams);
        $sizes = self::sortSizes($stmt->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $pdo->prepare("SELECT DISTINCT v.color FROM product_variants v
                               JOIN products p ON p.id = v.product_id
                               WHERE p.is_active = 1 AND {$baseWhere}
                               ORDER BY v.color");
        $stmt->execute($baseParams);
        $colors = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return ['sizes' => $sizes, 'colors' => $colors];
    }

    /** Sort mixed sizes sensibly: numbered (EU 40, 8-9yr) first, then XS-XL. */
    private static function sortSizes(array $sizes): array
    {
        $letterOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'One Size' => 6];

        usort($sizes, function (string $a, string $b) use ($letterOrder): int {
            $aNum = preg_match('/(\d+)/', $a, $m) ? (int) $m[1] : null;
            $bNum = preg_match('/(\d+)/', $b, $m) ? (int) $m[1] : null;

            if ($aNum !== null && $bNum !== null) return $aNum <=> $bNum;
            if ($aNum !== null) return -1;
            if ($bNum !== null) return 1;

            return ($letterOrder[$a] ?? 99) <=> ($letterOrder[$b] ?? 99);
        });

        return $sizes;
    }

    // -------------------------------------------------------------------
    // Product detail page (PDP)
    // -------------------------------------------------------------------

    /** One product by its URL slug, with category info JOINed on. */
    public static function bySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1'
        );
        $stmt->execute([$slug]);

        return $stmt->fetch() ?: null;
    }

    /** All images for the gallery — second query on purpose (no JOIN dupes). */
    public static function imagesFor(int $productId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT url, alt, color, sort_order FROM product_images
             WHERE product_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    /** All variants (sizes) with stock, for the size picker. */
    public static function variantsFor(int $productId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, sku, color, size, stock, price_override
             FROM product_variants WHERE product_id = ? ORDER BY id'
        );
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    /** "You may also like": same sport preferred, then same gender. */
    public static function related(array $product, int $limit = 4): array
    {
        return self::cards(
            '(p.sport = ? OR p.gender = ?) AND p.id != ?',
            [$product['sport'], $product['gender'], $product['id']],
            '(p.sport = ' . Database::pdo()->quote($product['sport']) . ') DESC, p.id ASC',
            $limit
        );
    }

    /** Admin: every product, including inactive ones. Filter + sort from the toolbar. */
    public static function adminList(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . self::escapeLike($q) . '%';
            $where[] = "(p.name LIKE ? ESCAPE '\\\\' OR p.slug LIKE ? ESCAPE '\\\\' OR CAST(p.id AS CHAR) = ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $q;
        }

        $status = $filters['status'] ?? 'all';
        if ($status === 'live') {
            $where[] = 'p.is_active = 1';
        } elseif ($status === 'hidden') {
            $where[] = 'p.is_active = 0';
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $sport = (string) ($filters['sport'] ?? '');
        if (in_array($sport, self::SPORTS, true)) {
            $where[] = 'p.sport = ?';
            $params[] = $sport;
        }

        $gender = (string) ($filters['gender'] ?? '');
        if (in_array($gender, self::GENDERS, true)) {
            $where[] = 'p.gender = ?';
            $params[] = $gender;
        }

        $sortKey = (string) ($filters['sort'] ?? 'category');
        $orderBy = self::ADMIN_SORTS[$sortKey] ?? self::ADMIN_SORTS['category'];
        $sqlWhere = implode(' AND ', $where);

        $stmt = Database::pdo()->prepare(
            "SELECT p.id, p.name, p.slug, p.gender, p.sport, p.price, p.sale_price,
                    p.is_active, p.is_featured, p.badge, c.name AS category_name,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS stock,
                    (SELECT url FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1) AS image
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE {$sqlWhere}
             ORDER BY {$orderBy}"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function slugify(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return substr(trim($slug, '-'), 0, 140);
    }

    /**
     * Curated product shots the admin can attach without hunting Unsplash.
     *
     * @return list<array{url: string, label: string, group: string}>
     */
    public static function imageLibrary(): array
    {
        return [
            ['group' => 'Shoes', 'label' => 'Crimson runner', 'url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'White trainer', 'url' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Classic white', 'url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Trail pair', 'url' => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Gym trainer', 'url' => 'https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Lifestyle pair', 'url' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Football boot', 'url' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=900&q=80'],
            ['group' => 'Shoes', 'label' => 'Kids runner', 'url' => 'https://images.unsplash.com/photo-1595341888016-a392ef81b7de?w=900&q=80'],
            ['group' => 'On body', 'label' => 'Road stride', 'url' => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80'],
            ['group' => 'On body', 'label' => 'Track session', 'url' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80'],
            ['group' => 'On body', 'label' => 'Gym floor', 'url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80'],
            ['group' => 'On body', 'label' => 'Basketball court', 'url' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=900&q=80'],
            ['group' => 'Apparel', 'label' => 'White tee', 'url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=900&q=80'],
            ['group' => 'Apparel', 'label' => 'Hoodie', 'url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80'],
            ['group' => 'Apparel', 'label' => 'Leggings', 'url' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=900&q=80'],
            ['group' => 'Apparel', 'label' => 'Jacket', 'url' => 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=900&q=80'],
            ['group' => 'Kit', 'label' => 'Cap', 'url' => 'https://images.unsplash.com/photo-1556306535-0f09a537f0a3?w=900&q=80'],
            ['group' => 'Kit', 'label' => 'Duffel', 'url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&q=80'],
        ];
    }

    public static function isUsableImageUrl(string $url): bool
    {
        if (strlen($url) > 500 || str_contains($url, '..')) {
            return false;
        }
        if (str_starts_with($url, '/assets/')) {
            return (bool) preg_match('#^/assets/[a-zA-Z0-9._/-]+$#', $url);
        }

        return (bool) preg_match('#^https://[a-zA-Z0-9.-]+(?:/[^\s]*)?$#', $url);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function categories(): array
    {
        return Database::pdo()
            ->query('SELECT id, name, slug FROM categories ORDER BY sort_order')
            ->fetchAll();
    }

    public static function slugTakenByOther(string $slug, int $exceptId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $exceptId]);

        return $stmt->fetch() !== false;
    }

    public static function update(int $id, array $data): void
    {
        Database::pdo()->prepare(
            'UPDATE products SET
                category_id = ?, name = ?, slug = ?, description = ?, details = ?,
                gender = ?, sport = ?, price = ?, sale_price = ?, badge = ?,
                is_featured = ?, is_active = ?
             WHERE id = ?'
        )->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['details'] !== '' ? $data['details'] : null,
            $data['gender'],
            $data['sport'],
            $data['price'],
            $data['sale_price'],
            $data['badge'],
            $data['is_featured'],
            $data['is_active'],
            $id,
        ]);
    }

    public static function create(array $data): int
    {
        Database::pdo()->prepare(
            'INSERT INTO products
                (category_id, name, slug, description, details, gender, sport,
                 price, sale_price, badge, is_featured, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['details'] !== '' ? $data['details'] : null,
            $data['gender'],
            $data['sport'],
            $data['price'],
            $data['sale_price'],
            $data['badge'],
            $data['is_featured'],
            $data['is_active'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Replace the gallery. Admin posts the full set; we wipe and rewrite so
     * sort order and removals stay honest.
     *
     * @param list<array{url: string, alt: string, color: ?string, sort_order: int}> $images
     */
    public static function replaceImages(int $productId, array $images): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);

        $insert = $pdo->prepare(
            'INSERT INTO product_images (product_id, url, alt, color, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($images as $i => $image) {
            $insert->execute([
                $productId,
                $image['url'],
                $image['alt'],
                $image['color'] !== '' ? $image['color'] : null,
                $image['sort_order'] ?? $i,
            ]);
        }
    }

    /**
     * Update posted variants, insert new ones, delete ones the admin removed.
     *
     * @param list<array{id?: int, sku: string, color: string, size: string, stock: int}> $rows
     */
    public static function syncVariants(int $productId, array $rows): void
    {
        $pdo = Database::pdo();
        $keep = [];

        $update = $pdo->prepare(
            'UPDATE product_variants SET sku = ?, color = ?, size = ?, stock = ?
             WHERE id = ? AND product_id = ?'
        );
        $insert = $pdo->prepare(
            'INSERT INTO product_variants (product_id, sku, color, size, stock)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $update->execute([$row['sku'], $row['color'], $row['size'], $row['stock'], $id, $productId]);
                $keep[] = $id;
            } else {
                $insert->execute([$productId, $row['sku'], $row['color'], $row['size'], $row['stock']]);
                $keep[] = (int) $pdo->lastInsertId();
            }
        }

        if ($keep === []) {
            $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$productId]);
            return;
        }

        $in = implode(',', array_fill(0, count($keep), '?'));
        $pdo->prepare(
            "DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ({$in})"
        )->execute([$productId, ...$keep]);
    }

    public static function skuTaken(string $sku, int $exceptVariantId = 0): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM product_variants WHERE sku = ? AND id != ?');
        $stmt->execute([$sku, $exceptVariantId]);

        return $stmt->fetch() !== false;
    }

    public static function uniqueSku(string $slug, string $color, string $size, int $exceptVariantId = 0): string
    {
        $base = 'BF-' . strtoupper(substr(preg_replace('/[^a-z0-9]/', '', $slug) ?: 'PROD', 0, 4));
        $col  = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $color) ?: 'CLR', 0, 3));
        $sz   = strtoupper(preg_replace('/[^a-z0-9]/i', '', $size) ?: 'OS');
        $sku  = $base . '-' . $col . '-' . $sz;
        $n    = 2;
        while (self::skuTaken($sku, $exceptVariantId)) {
            $sku = $base . '-' . $col . '-' . $sz . '-' . $n;
            $n++;
        }

        return $sku;
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::pdo()->prepare('UPDATE products SET is_active = ? WHERE id = ?')
            ->execute([(int) $active, $id]);
    }

    /** Ownership-checked stock edit: the variant must belong to this product. */
    public static function setVariantStock(int $productId, int $variantId, int $stock): void
    {
        Database::pdo()->prepare(
            'UPDATE product_variants SET stock = ? WHERE id = ? AND product_id = ?'
        )->execute([max(0, $stock), $variantId, $productId]);
    }

    // -------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------

    /**
     * Escape characters that mean something special in LIKE:
     *   %  = "any string"
     *   _  = "any single character"
     *   \  = the escape character itself
     *
     * Without this, searching for "100%" would match everything.
     */
    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @return array{sql: string, params: list<string>}
     */
    private static function searchClause(string $q): array
    {
        $like = '%' . self::escapeLike($q) . '%';

        // ESCAPE '\\' tells MySQL that \ is how we quote % and _.
        $sql = "p.is_active = 1 AND (
                    p.name LIKE ? ESCAPE '\\\\'
                    OR p.description LIKE ? ESCAPE '\\\\'
                    OR c.name LIKE ? ESCAPE '\\\\'
                    OR EXISTS (
                        SELECT 1 FROM product_variants v
                        WHERE v.product_id = p.id AND v.sku LIKE ? ESCAPE '\\\\'
                    )
                )";

        return ['sql' => $sql, 'params' => [$like, $like, $like, $like]];
    }

    /**
     * Full search results page. JOIN to categories is many-to-one so it
     * does not duplicate product rows — unlike a JOIN to images or variants.
     */
    public static function search(string $q, int $page = 1): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['products' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        }

        $clause = self::searchClause($q);

        $count = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE {$clause['sql']}"
        );
        $count->execute($clause['params']);
        $total = (int) $count->fetchColumn();

        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        // Name matches float to the top; everything else is newest first.
        $starts = self::escapeLike($q) . '%';
        $sql    = 'SELECT ' . self::CARD_COLUMNS . "
                   FROM products p
                   JOIN categories c ON c.id = p.category_id
                   WHERE {$clause['sql']}
                   ORDER BY (p.name LIKE ? ESCAPE '\\\\') DESC, p.id DESC
                   LIMIT " . self::PER_PAGE . " OFFSET {$offset}";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([...$clause['params'], $starts]);

        return [
            'products' => self::attachImages($stmt->fetchAll()),
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
        ];
    }

    /** Compact rows for the header typeahead dropdown. */
    public static function suggest(string $q, int $limit = 6): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $clause = self::searchClause($q);
        $limit  = max(1, min(8, $limit));
        $starts = self::escapeLike($q) . '%';

        $sql = 'SELECT ' . self::CARD_COLUMNS . "
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE {$clause['sql']}
                ORDER BY (p.name LIKE ? ESCAPE '\\\\') DESC, p.id DESC
                LIMIT {$limit}";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([...$clause['params'], $starts]);

        return $stmt->fetchAll();
    }

    /** Cards for a list of ids, keeping the given order (recently viewed). */
    public static function byIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $in    = implode(',', array_fill(0, count($ids), '?'));
        $order = implode(',', $ids); // safe: every value passed through intval
        $sql   = 'SELECT ' . self::CARD_COLUMNS . "
                  FROM products p
                  WHERE p.is_active = 1 AND p.id IN ({$in})
                  ORDER BY FIELD(p.id, {$order})";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($ids);

        return self::attachImages($stmt->fetchAll());
    }

    /** Cards for a list of slugs, keeping the given order (journal shop-the-story). */
    public static function cardsBySlugs(array $slugs): array
    {
        $slugs = array_values(array_filter(
            $slugs,
            fn($s): bool => is_string($s) && preg_match('/^[a-z0-9-]+$/', $s) === 1
        ));
        if ($slugs === []) {
            return [];
        }

        $in   = implode(',', array_fill(0, count($slugs), '?'));
        $sql  = 'SELECT ' . self::CARD_COLUMNS . "
                 FROM products p
                 WHERE p.is_active = 1 AND p.slug IN ({$in})";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($slugs);
        $bySlug = [];
        foreach (self::attachImages($stmt->fetchAll()) as $row) {
            $bySlug[$row['slug']] = $row;
        }

        $ordered = [];
        foreach ($slugs as $slug) {
            if (isset($bySlug[$slug])) {
                $ordered[] = $bySlug[$slug];
            }
        }

        return $ordered;
    }

    public static function lowStock(int $threshold = 3, int $limit = 12): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT v.id, v.sku, v.size, v.color, v.stock, p.id AS product_id, p.name, p.slug
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.stock <= ? AND p.is_active = 1
             ORDER BY v.stock ASC, p.name ASC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$threshold]);

        return $stmt->fetchAll();
    }
}
