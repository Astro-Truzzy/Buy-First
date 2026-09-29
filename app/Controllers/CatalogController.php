<?php

declare(strict_types=1);

/**
 * Catalog controller — every product LISTING page (PLP).
 * Each public method defines a page's base scope (which products belong
 * here at all); user filters from the URL are layered on top.
 */
class CatalogController
{
    private const CATEGORIES = ['shoes', 'clothing', 'accessories'];

    /** /men, /women, /kids — optionally /men/shoes etc. */
    public static function gender(string $gender, ?string $categorySlug = null): string
    {
        // Unisex products belong on the men's AND women's pages; kids is its own world.
        if ($gender === 'kids') {
            $where  = "p.gender = 'kids'";
            $params = [];
        } else {
            $where  = "(p.gender = ? OR p.gender = 'unisex')";
            $params = [$gender];
        }

        $title = ['men' => "Men", 'women' => "Women", 'kids' => "Kids"][$gender];

        if ($categorySlug !== null) {
            if (!in_array($categorySlug, self::CATEGORIES, true)) {
                return self::notFound();
            }
            $where .= ' AND p.category_id = (SELECT id FROM categories WHERE slug = ?)';
            $params[] = $categorySlug;
            $title .= "'s " . ucfirst($categorySlug);
        }

        return self::listing($title, $where, $params);
    }

    /** /sport/running etc. */
    public static function sport(string $sport): string
    {
        if (!in_array($sport, Product::SPORTS, true)) {
            return self::notFound();
        }

        $titles = [
            'running'    => 'Running',
            'training'   => 'Training & Gym',
            'basketball' => 'Basketball',
            'football'   => 'Football',
            'lifestyle'  => 'Lifestyle',
        ];

        // The whole page IS this sport, so hide the sport filter group.
        return self::listing($titles[$sport], 'p.sport = ?', [$sport], showSportFilter: false);
    }

    /** /sale — only products with a sale price. */
    public static function sale(): string
    {
        return self::listing('Sale', 'p.sale_price IS NOT NULL', []);
    }

    /** /new — everything, newest first by default. */
    public static function newIn(): string
    {
        return self::listing('New & Featured', '1=1', [], defaultSort: 'newest');
    }

    /**
     * Shared engine: read user filters from the URL ($_GET), run the
     * listing query, and render the catalog view.
     */
    private static function listing(
        string $title,
        string $where,
        array $params,
        string $defaultSort = 'featured',
        bool $showSportFilter = true,
    ): string {
        // Everything in $_GET is user input. It is validated/bound inside
        // the model, and escaped with e() in the view.
        $filters = [
            'sport' => $_GET['sport'] ?? null,
            'badge' => $_GET['badge'] ?? null,
            'size'  => (array) ($_GET['size'] ?? []),
            'color' => (array) ($_GET['color'] ?? []),
            'price' => $_GET['price'] ?? null,
            'sort'  => $_GET['sort'] ?? $defaultSort,
        ];

        $result  = Product::listing($where, $params, $filters, max(1, (int) ($_GET['page'] ?? 1)));
        $options = Product::filterOptions($where, $params);

        return render('catalog', [
            'title'           => $title,
            'products'        => $result['products'],
            'total'           => $result['total'],
            'pages'           => $result['pages'],
            'page'            => $result['page'],
            'filters'         => $filters,
            'options'         => $options,
            'showSportFilter' => $showSportFilter,
        ], ['title' => $title]);
    }

    private static function notFound(): string
    {
        http_response_code(404);
        return render('errors/404', [], ['title' => 'Page not found']);
    }
}
