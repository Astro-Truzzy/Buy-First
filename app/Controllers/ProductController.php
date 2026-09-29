<?php

declare(strict_types=1);

/**
 * Product controller — the product detail page (PDP).
 */
class ProductController
{
    public static function show(string $slug): string
    {
        $product = Product::bySlug($slug);

        if ($product === null) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Product not found']);
        }

        $variants = Product::variantsFor((int) $product['id']);
        $userId   = Auth::id();

        remember_viewed((int) $product['id']);

        return render('product', [
            'product'    => $product,
            'images'     => Product::imagesFor((int) $product['id']),
            'variants'   => $variants,
            'totalStock' => array_sum(array_column($variants, 'stock')),
            'stats'      => Review::statsFor((int) $product['id']),
            'reviews'    => Review::approvedFor((int) $product['id']),
            'related'    => Product::related($product, 4),
            'ownReview'  => $userId ? Review::byUserFor((int) $product['id'], $userId) : null,
            'purchased'  => $userId ? Order::userBought((int) $product['id'], $userId) : false,
        ], ['title' => $product['name']]);
    }

    /** POST /product/{slug}/review — logged-in customers only; starts pending. */
    public static function review(string $slug): never
    {
        require_login();

        $product = Product::bySlug($slug);
        if ($product === null) {
            flash_set('error', 'That product is no longer available.');
            redirect('/new');
        }

        $userId = Auth::id();
        if ($userId === null) {
            redirect('/login');
        }
        if (Review::byUserFor((int) $product['id'], $userId) !== null) {
            flash_set('info', 'You have already reviewed this product.');
            redirect('/product/' . $product['slug'] . '#reviews');
        }

        $rating = (int) ($_POST['rating'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $body   = trim($_POST['body'] ?? '');

        if ($rating < 1 || $rating > 5) {
            flash_set('error', 'Please choose a rating from 1 to 5.');
            redirect('/product/' . $product['slug'] . '#reviews');
        }
        if ($title === '' || mb_strlen($title) > 120) {
            flash_set('error', 'Please add a short title (up to 120 characters).');
            redirect('/product/' . $product['slug'] . '#reviews');
        }
        if (mb_strlen($body) < 20 || mb_strlen($body) > 2000) {
            flash_set('error', 'Tell us a bit more — at least 20 characters, up to 2,000.');
            redirect('/product/' . $product['slug'] . '#reviews');
        }

        Review::create(
            (int) $product['id'],
            $userId,
            $rating,
            $title,
            $body,
            Order::userBought((int) $product['id'], $userId)
        );

        flash_set('success', 'Thanks — your review is in the moderation queue and will appear once approved.');
        redirect('/product/' . $product['slug'] . '#reviews');
    }
}
