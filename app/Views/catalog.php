<?php
/**
 * Catalog listing page (PLP). Receives from CatalogController:
 *   $title, $products, $total, $pages, $page, $filters, $options, $showSportFilter
 *
 * The whole page is ONE GET form: changing any filter re-submits and the
 * URL updates — so every filtered view is shareable and bookmarkable.
 */

// Build a link to page N, keeping every current filter in the URL.
$pageUrl = fn(int $n): string => '?' . http_build_query(array_merge($_GET, ['page' => $n]));

$sortOptions = [
    'featured'   => 'Featured',
    'newest'     => 'Newest',
    'price-low'  => 'Price: Low to High',
    'price-high' => 'Price: High to Low',
    'rating'     => 'Top Rated',
];
?>
<section class="container listing">
    <header class="listing__head">
        <h1 class="display"><?= e($title) ?></h1>
        <p class="listing__count"><?= e((string) $total) ?> <?= $total === 1 ? 'Result' : 'Results' ?></p>
    </header>

    <form method="get" action="" id="filter-form">
        <div class="listing__layout">

            <aside class="filters">
                <button type="button" class="btn btn--outline filters__toggle"
                        aria-expanded="false" aria-controls="filter-groups">Filters</button>

                <div class="filters__groups" id="filter-groups">
                    <?php if ($showSportFilter): ?>
                        <fieldset class="filter-group">
                            <legend>Sport</legend>
                            <label class="filter-option">
                                <input type="radio" name="sport" value="" <?= empty($filters['sport']) ? 'checked' : '' ?>>
                                All Sports
                            </label>
                            <?php foreach (Product::SPORTS as $sport): ?>
                                <label class="filter-option">
                                    <input type="radio" name="sport" value="<?= e($sport) ?>"
                                        <?= $filters['sport'] === $sport ? 'checked' : '' ?>>
                                    <?= e(ucfirst($sport)) ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <?php if ($options['sizes']): ?>
                        <fieldset class="filter-group">
                            <legend>Size</legend>
                            <div class="filter-group__chips">
                                <?php foreach ($options['sizes'] as $size): ?>
                                    <label class="chip">
                                        <input type="checkbox" name="size[]" value="<?= e($size) ?>"
                                            <?= in_array($size, $filters['size'], true) ? 'checked' : '' ?>>
                                        <span><?= e($size) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php endif; ?>

                    <?php if ($options['colors']): ?>
                        <fieldset class="filter-group">
                            <legend>Colour</legend>
                            <?php foreach ($options['colors'] as $color): ?>
                                <label class="filter-option">
                                    <input type="checkbox" name="color[]" value="<?= e($color) ?>"
                                        <?= in_array($color, $filters['color'], true) ? 'checked' : '' ?>>
                                    <?= e($color) ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <fieldset class="filter-group">
                        <legend>Price</legend>
                        <?php
                        $priceOptions = ['' => 'Any Price', 'under-50' => 'Under ₦50,000', '50-100' => '₦50,000 – ₦100,000', 'over-100' => 'Over ₦100,000'];
                        foreach ($priceOptions as $value => $label): ?>
                            <label class="filter-option">
                                <input type="radio" name="price" value="<?= e((string) $value) ?>"
                                    <?= ($filters['price'] ?? '') === $value ? 'checked' : '' ?>>
                                <?= e($label) ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <!-- Works without JavaScript; JS hides it and auto-submits instead. -->
                    <button type="submit" class="btn btn--primary filters__apply">Apply Filters</button>
                    <a class="filters__clear" href="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>">Clear all</a>
                </div>
            </aside>

            <div class="listing__main">
                <div class="listing__toolbar">
                    <span class="listing__toolbar-count"><?= e((string) $total) ?> <?= $total === 1 ? 'Result' : 'Results' ?></span>
                    <label class="listing__sort">
                        Sort by
                        <select name="sort">
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= ($filters['sort'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <?php if ($products): ?>
                    <div class="product-grid product-grid--listing">
                        <?php foreach ($products as $product): ?>
                            <?= view('partials/product-card', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="listing__empty">
                        <h2 class="display">Nothing matches — yet.</h2>
                        <p>Try removing a filter or two, or clear them all to see the full range.</p>
                        <a class="btn btn--primary" href="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>">Clear filters</a>
                    </div>
                <?php endif; ?>

                <?php if ($pages > 1): ?>
                    <nav class="pagination" aria-label="Pages">
                        <?php if ($page > 1): ?>
                            <a class="pagination__link" href="<?= e($pageUrl($page - 1)) ?>">&larr; Previous</a>
                        <?php endif; ?>

                        <?php for ($n = 1; $n <= $pages; $n++): ?>
                            <?php if ($n === $page): ?>
                                <span class="pagination__link is-current" aria-current="page"><?= $n ?></span>
                            <?php else: ?>
                                <a class="pagination__link" href="<?= e($pageUrl($n)) ?>"><?= $n ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $pages): ?>
                            <a class="pagination__link" href="<?= e($pageUrl($page + 1)) ?>">Next &rarr;</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </form>
</section>
