<?php
$recent = recently_viewed_products(4);
?>
<section class="error-page container">
    <p class="error-page__code">404</p>
    <h1 class="display">That page has left the pitch.</h1>
    <p class="error-page__lede">
        It may have moved, sold out, or never existed. Search the store or go home.
    </p>

    <form class="search-page__form" method="get" action="/search" role="search">
        <label class="visually-hidden" for="error-search-q">Search products</label>
        <input type="search" id="error-search-q" name="q" placeholder="Search gear" minlength="2" required>
        <button class="btn btn--primary" type="submit">Search</button>
    </form>

    <p class="error-page__actions">
        <a class="btn btn--outline" href="/">Back to the store</a>
        <a class="btn btn--outline" href="/new">New arrivals</a>
    </p>
</section>

<?php if ($recent): ?>
    <section class="container section">
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
