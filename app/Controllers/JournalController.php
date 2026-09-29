<?php

declare(strict_types=1);

/**
 * The Journal — magazine index and individual stories.
 */
class JournalController
{
    public static function index(): string
    {
        $stories  = Journal::all();
        $featured = Journal::featured();
        $rest     = $featured
            ? array_values(array_filter($stories, fn(array $s): bool => $s['slug'] !== $featured['slug']))
            : $stories;

        return render('journal/index', [
            'featured' => $featured,
            'stories'  => $rest,
        ], [
            'title'             => 'The Journal',
            'transparentHeader' => true,
        ]);
    }

    public static function show(string $slug): string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Page not found']);
        }

        $story = Journal::bySlug($slug);
        if ($story === null) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Page not found']);
        }

        return render('journal/story', [
            'story'    => $story,
            'products' => Product::cardsBySlugs($story['product_slugs'] ?? []),
            'more'     => Journal::except($slug, 3),
        ], [
            'title'             => $story['title'],
            'transparentHeader' => true,
        ]);
    }
}
