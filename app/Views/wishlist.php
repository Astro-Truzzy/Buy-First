<?php
/**
 * Wishlist. Receives: $products (card rows, newest save first), $user
 */
?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__kicker">Your account</p>
        <h1 class="display">Wishlist</h1>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <?php if ($products === []): ?>
                <div class="bag-empty bag-empty--flush">
                    <p>Nothing saved yet. Tap the heart on a product to keep it here.</p>
                    <a class="btn btn--primary" href="/new">Shop New Arrivals</a>
                </div>
            <?php else: ?>
                <p class="listing__count"><?= e((string) count($products)) ?> <?= count($products) === 1 ? 'item' : 'items' ?></p>
                <div class="product-grid product-grid--listing">
                    <?php foreach ($products as $product): ?>
                        <?= view('partials/product-card', ['product' => $product]) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
