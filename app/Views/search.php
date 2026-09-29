<?php
/**
 * Search results. Receives: $q, $products, $total, $pages, $page, $recent
 */
$pageUrl = fn(int $n): string => '?' . http_build_query(['q' => $q, 'page' => $n]);
$tooShort = $q !== '' && mb_strlen($q) < 2;
?>
<section class="container listing search-page">
    <header class="listing__head">
        <h1 class="display">Search</h1>
        <?php if ($q === ''): ?>
            <p class="listing__count">Find shoes, kit, or a SKU.</p>
        <?php elseif ($tooShort): ?>
            <p class="listing__count">Type at least two characters.</p>
        <?php else: ?>
            <p class="listing__count"><?= e((string) $total) ?> <?= $total === 1 ? 'result' : 'results' ?> for “<?= e($q) ?>”</p>
        <?php endif; ?>
    </header>

    <form class="search-page__form" method="get" action="/search" role="search">
        <label class="visually-hidden" for="search-page-q">Search products</label>
        <input type="search" id="search-page-q" name="q" value="<?= e($q) ?>"
               placeholder="Try runner, hoodie, or BF-VELO" minlength="2" required>
        <button class="btn btn--primary" type="submit">Search</button>
    </form>

    <?php if ($products): ?>
        <div class="product-grid product-grid--listing">
            <?php foreach ($products as $product): ?>
                <?= view('partials/product-card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>

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

    <?php elseif ($q !== '' && !$tooShort): ?>
        <div class="listing__empty">
            <h2 class="display">Nothing for “<?= e($q) ?>”.</h2>
            <p>Try a broader word — runner, jacket, football — or a SKU like BF-VELO.</p>
            <a class="btn btn--primary" href="/new">Browse new arrivals</a>
        </div>
    <?php endif; ?>

    <?php if ($recent): ?>
        <section class="section">
            <div class="section-head">
                <h2 class="display">Recently Viewed</h2>
            </div>
            <div class="product-grid product-grid--4">
                <?php foreach ($recent as $product): ?>
                    <?= view('partials/product-card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
