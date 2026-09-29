<?php

declare(strict_types=1);

/**
 * Search — the results page and the JSON typeahead.
 *
 * Two endpoints, one query engine (Product::search / Product::suggest).
 * The dropdown is a convenience; the shareable URL is always /search?q=.
 */
class SearchController
{
    public static function index(): string
    {
        $q      = trim((string) ($_GET['q'] ?? ''));
        $result = mb_strlen($q) >= 2
            ? Product::search($q, max(1, (int) ($_GET['page'] ?? 1)))
            : ['products' => [], 'total' => 0, 'pages' => 1, 'page' => 1];

        return render('search', [
            'q'        => $q,
            'products' => $result['products'],
            'total'    => $result['total'],
            'pages'    => $result['pages'],
            'page'     => $result['page'],
            'recent'   => recently_viewed_products(4),
        ], ['title' => $q !== '' ? 'Search: ' . $q : 'Search']);
    }

    /** GET /search/suggest?q= — JSON for the header dropdown. */
    public static function suggest(): never
    {
        $q     = trim((string) ($_GET['q'] ?? ''));
        $rows  = Product::suggest($q, 6);
        $items = [];

        foreach ($rows as $row) {
            $price = $row['sale_price'] ?? $row['price'];
            $items[] = [
                'name'  => $row['name'],
                'slug'  => $row['slug'],
                'url'   => '/product/' . $row['slug'],
                'image' => $row['image'],
                'price' => money($price),
            ];
        }

        json_response(['results' => $items]);
    }
}
