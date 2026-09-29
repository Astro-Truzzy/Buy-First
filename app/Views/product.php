<?php
/**
 * Product detail page (PDP). Receives from ProductController:
 *   $product, $images, $variants, $totalStock, $stats, $reviews, $related
 */

$onSale     = $product['sale_price'] !== null;
$percentOff = $onSale ? round((1 - (float)$product['sale_price'] / (float)$product['price']) * 100) : 0;

$genderLabel = ['men' => "Men's", 'women' => "Women's", 'kids' => "Kids'", 'unisex' => ''][$product['gender']] ?? '';
$subtitle    = trim($genderLabel . ' ' . ucfirst($product['sport']));

// Unicode stars for the average rating (5 characters, filled vs hollow).
$stars = fn(float $rating): string =>
    str_repeat('★', (int) round($rating)) . str_repeat('☆', 5 - (int) round($rating));

$isFootwear = $product['category_slug'] === 'shoes';
?>

<div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> /
        <a href="/<?= e($product['gender'] === 'unisex' ? 'new' : $product['gender']) ?>"><?= e(ucfirst($product['gender'] === 'unisex' ? 'New' : $product['gender'])) ?></a> /
        <span aria-current="page"><?= e($product['name']) ?></span>
    </nav>

    <article class="pdp">
        <!-- ============ Gallery ============ -->
        <div class="pdp__gallery">
            <img class="pdp__main-img" id="pdp-main-img"
                 src="<?= e($images[0]['url'] ?? '') ?>"
                 alt="<?= e($images[0]['alt'] ?? $product['name']) ?>">
            <?php if (count($images) > 1): ?>
                <div class="pdp__thumbs" role="group" aria-label="Product images">
                    <?php foreach ($images as $i => $img): ?>
                        <button type="button" class="pdp__thumb<?= $i === 0 ? ' is-active' : '' ?>"
                                data-src="<?= e($img['url']) ?>" data-alt="<?= e($img['alt']) ?>"
                                aria-label="Show image <?= $i + 1 ?>">
                            <img src="<?= e($img['url']) ?>" alt="" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ Buy panel ============ -->
        <div class="pdp__panel">
            <?php if ($product['badge']): ?>
                <p class="pdp__badge"><?= e(['just-in' => 'Just In', 'best-seller' => 'Best Seller', 'member-exclusive' => 'Member Exclusive', 'limited-drop' => 'Limited Drop'][$product['badge']]) ?></p>
            <?php endif; ?>

            <h1 class="pdp__name"><?= e($product['name']) ?></h1>
            <?php if (Auth::isAdmin()): ?>
                <p class="pdp__admin"><a href="/admin/products/<?= e((string) $product['id']) ?>/edit">Edit this product</a></p>
            <?php endif; ?>
            <p class="pdp__sub"><?= e($subtitle) ?></p>

            <p class="pdp__price">
                <?php if ($onSale): ?>
                    <span class="pdp__now"><?= money($product['sale_price']) ?></span>
                    <s class="pdp__was"><?= money($product['price']) ?></s>
                    <span class="pdp__off"><?= e((string) $percentOff) ?>% Off</span>
                <?php else: ?>
                    <span class="pdp__now"><?= money($product['price']) ?></span>
                <?php endif; ?>
            </p>

            <?php if ($stats['count'] > 0): ?>
                <a class="pdp__rating" href="#reviews">
                    <span aria-hidden="true"><?= $stars($stats['avg']) ?></span>
                    <span><?= e((string) $stats['avg']) ?> (<?= e((string) $stats['count']) ?> review<?= $stats['count'] === 1 ? '' : 's' ?>)</span>
                </a>
            <?php endif; ?>

            <p class="pdp__color">Colour: <strong><?= e($variants[0]['color'] ?? '—') ?></strong></p>

            <!-- ============ Size picker + add to bag ============ -->
            <form class="pdp__form" method="post" action="/cart/add">
                <?= csrf_field() ?>
                <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">

                <div class="pdp__size-head">
                    <span id="size-label">Select Size</span>
                    <button type="button" class="pdp__size-guide" data-open-dialog="size-guide">Size Guide</button>
                </div>

                <div class="size-grid" role="radiogroup" aria-labelledby="size-label">
                    <?php foreach ($variants as $variant): ?>
                        <?php $soldOut = (int) $variant['stock'] === 0; ?>
                        <label class="size-chip<?= $soldOut ? ' is-soldout' : '' ?>">
                            <input type="radio" name="variant_id" value="<?= e((string) $variant['id']) ?>"
                                   <?= $soldOut ? 'disabled' : 'required' ?>>
                            <span><?= e($variant['size']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalStock === 0): ?>
                    <p class="pdp__stock pdp__stock--out">Sold out — more on the way.</p>
                    <button class="btn btn--primary pdp__cta" type="button" disabled>Sold Out</button>
                <?php else: ?>
                    <?php if ($totalStock < 15): ?>
                        <p class="pdp__stock">Low stock — only <?= e((string) $totalStock) ?> left across all sizes.</p>
                    <?php endif; ?>
                    <button class="btn btn--primary pdp__cta" type="submit">Add To Bag</button>
                <?php endif; ?>
            </form>

            <?= view('partials/wish-button', [
                'productId' => (int) $product['id'],
                'showText'  => true,
                'label'     => Wishlist::has((int) $product['id']) ? 'Remove from wishlist' : 'Save to wishlist',
            ]) ?>

            <!-- ============ Info accordion (native details element) ============ -->
            <div class="pdp__accordion">
                <details open>
                    <summary>Description &amp; Details</summary>
                    <p><?= e($product['description']) ?></p>
                    <?php if ($product['details']): ?>
                        <ul>
                            <?php foreach (explode("\n", $product['details']) as $line): ?>
                                <li><?= e($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </details>
                <details>
                    <summary>Shipping &amp; Delivery</summary>
                    <p>Standard delivery (3–7 working days nationwide) is <?= money(Order::STANDARD_FEE) ?>,
                       or free on orders over <?= money(Order::FREE_STANDARD_FROM) ?>
                       and for all BuyFirst Club members. Express delivery (1–2 working days in Lagos
                       and Abuja, 2–3 elsewhere) is <?= money(Order::EXPRESS_FEE) ?>.
                       Full options are shown at checkout.</p>
                </details>
                <details>
                    <summary>Returns</summary>
                    <p>Unworn items can be returned free within 30 days of delivery, no questions
                       asked. Club members get 60 days. See our Returns &amp; Exchanges policy
                       for the full details.</p>
                </details>
            </div>
        </div>
    </article>

    <!-- ============ Reviews ============ -->
    <section class="pdp-reviews" id="reviews">
        <h2 class="display">Reviews<?= $stats['count'] ? ' (' . e((string) $stats['count']) . ')' : '' ?></h2>
        <?php if ($reviews): ?>
            <p class="pdp-reviews__avg">
                <span aria-hidden="true"><?= $stars($stats['avg']) ?></span>
                <?= e((string) $stats['avg']) ?> out of 5
            </p>
            <div class="pdp-reviews__list">
                <?php foreach ($reviews as $review): ?>
                    <article class="review">
                        <header class="review__head">
                            <span aria-label="Rated <?= e((string) $review['rating']) ?> out of 5"><?= $stars((float) $review['rating']) ?></span>
                            <h3><?= e($review['title']) ?></h3>
                        </header>
                        <p class="review__body"><?= e($review['body']) ?></p>
                        <footer class="review__meta">
                            <?= e($review['first_name']) ?> ·
                            <?= e(date('j M Y', strtotime($review['created_at']))) ?>
                            <?php if ($review['is_verified_purchase']): ?>
                                · <span class="review__verified">Verified Purchase</span>
                            <?php endif; ?>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No reviews yet. Be the first — once you have taken it for a spin.</p>
        <?php endif; ?>

        <div class="review-form-wrap">
            <?php if (!Auth::check()): ?>
                <p class="review-form__gate">
                    <a href="/login">Sign in</a> to write a review.
                </p>
            <?php elseif ($ownReview): ?>
                <p class="review-form__gate">
                    You have already reviewed this
                    <?php if ($ownReview['status'] === 'pending'): ?>
                        — it’s waiting for approval.
                    <?php elseif ($ownReview['status'] === 'rejected'): ?>
                        — it was not published.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <h3 class="review-form__title">Write a review</h3>
                <?php if ($purchased): ?>
                    <p class="field__hint">Verified purchase — your review will carry that badge once approved.</p>
                <?php endif; ?>
                <form class="review-form" method="post" action="/product/<?= e($product['slug']) ?>/review">
                    <?= csrf_field() ?>
                    <fieldset class="review-form__stars">
                        <legend>Rating</legend>
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                            <label>
                                <input type="radio" name="rating" value="<?= $n ?>" <?= $n === 5 ? 'required' : '' ?>>
                                <span><?= $n ?></span>
                            </label>
                        <?php endfor; ?>
                    </fieldset>
                    <div class="field">
                        <label for="review-title">Title</label>
                        <input type="text" id="review-title" name="title" maxlength="120" required
                               placeholder="Sum it up in a line">
                    </div>
                    <div class="field">
                        <label for="review-body">Your review</label>
                        <textarea id="review-body" name="body" rows="4" minlength="20" maxlength="2000" required
                                  placeholder="Fit, feel, where you wore it — 20 characters minimum."></textarea>
                    </div>
                    <button class="btn btn--primary" type="submit">Submit review</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============ Related ============ -->
    <?php if ($related): ?>
        <section class="section">
            <div class="section-head">
                <h2 class="display">You May Also Like</h2>
            </div>
            <div class="product-grid product-grid--4">
                <?php foreach ($related as $rel): ?>
                    <?= view('partials/product-card', ['product' => $rel]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- ============ Size guide modal ============ -->
<dialog class="dialog" id="size-guide" aria-labelledby="size-guide-title">
    <div class="dialog__head">
        <h2 id="size-guide-title" class="display"><?= $isFootwear ? 'Footwear' : 'Apparel' ?> Size Guide</h2>
        <button type="button" class="dialog__close" data-close-dialog aria-label="Close size guide">&times;</button>
    </div>
    <?php if ($isFootwear): ?>
        <table class="size-table">
            <thead><tr><th>UK</th><th>EU</th><th>US</th><th>Foot length (cm)</th></tr></thead>
            <tbody>
                <tr><td>6</td><td>39</td><td>7</td><td>24.5</td></tr>
                <tr><td>7</td><td>41</td><td>8</td><td>25.5</td></tr>
                <tr><td>8</td><td>42</td><td>9</td><td>26.5</td></tr>
                <tr><td>9</td><td>44</td><td>10</td><td>27.5</td></tr>
                <tr><td>10</td><td>45</td><td>11</td><td>28.5</td></tr>
                <tr><td>11</td><td>46</td><td>12</td><td>29.5</td></tr>
            </tbody>
        </table>
        <p>Measure your foot from heel to longest toe, standing, in the evening
           (feet swell during the day). Between sizes? Go up for running, down for lifestyle.</p>
    <?php else: ?>
        <table class="size-table">
            <thead><tr><th>Size</th><th>Chest (cm)</th><th>Waist (cm)</th><th>Hip (cm)</th></tr></thead>
            <tbody>
                <tr><td>XS</td><td>84–89</td><td>68–73</td><td>84–89</td></tr>
                <tr><td>S</td><td>90–97</td><td>74–81</td><td>90–97</td></tr>
                <tr><td>M</td><td>98–105</td><td>82–89</td><td>98–105</td></tr>
                <tr><td>L</td><td>106–113</td><td>90–98</td><td>106–113</td></tr>
                <tr><td>XL</td><td>114–121</td><td>99–108</td><td>114–121</td></tr>
            </tbody>
        </table>
        <p>Measure over light clothing, keeping the tape level. For a relaxed fit,
           size up; performance layers are designed to fit close.</p>
    <?php endif; ?>
</dialog>
