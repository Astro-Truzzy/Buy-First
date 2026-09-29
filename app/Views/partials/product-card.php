<?php
/**
 * Product card — used in every grid and rail across the site.
 * Expects: $product with keys id, name, slug, gender, sport, price,
 * sale_price, badge, and images (a list of ['url' => , 'alt' => ] in
 * gallery order). Falls back to the legacy image/image_alt keys when the
 * images list is missing.
 *
 * The first image is the main shot; the rest render as a thumbnail row
 * under it. Hovering (or focusing) a thumbnail swaps the main image —
 * see the handler in public/assets/js/main.js.
 *
 * The outer element is an article (not a single link) so the heart button
 * and the thumbnail buttons are not nested inside an <a>.
 */

$badgeLabels = [
    'just-in'          => 'Just In',
    'best-seller'      => 'Best Seller',
    'member-exclusive' => 'Member Exclusive',
    'limited-drop'     => 'Limited Drop',
];

$genderLabel = ['men' => "Men's", 'women' => "Women's", 'kids' => "Kids'", 'unisex' => ''][$product['gender']] ?? '';
$subtitle    = trim($genderLabel . ' ' . ucfirst($product['sport']));

$onSale     = $product['sale_price'] !== null;
$percentOff = $onSale ? round((1 - (float)$product['sale_price'] / (float)$product['price']) * 100) : 0;
$href       = '/product/' . $product['slug'];

// Prefer the full images list; fall back to the single legacy image.
$images = $product['images'] ?? [];
if ($images === []) {
    $images = [['url' => $product['image'] ?? '', 'alt' => $product['image_alt'] ?? '']];
}
$main = $images[0];
?>
<article class="product-card">
    <div class="product-card__media">
        <a href="<?= e($href) ?>">
            <img class="product-card__img" data-card-main src="<?= e($main['url']) ?>" alt="<?= e($main['alt']) ?>" loading="lazy">
        </a>
        <?php if ($onSale): ?>
            <span class="product-card__flag product-card__flag--sale"><?= e((string) $percentOff) ?>% Off</span>
        <?php elseif ($product['badge']): ?>
            <span class="product-card__flag"><?= e($badgeLabels[$product['badge']] ?? $product['badge']) ?></span>
        <?php endif; ?>
        <?= view('partials/wish-button', ['productId' => (int) $product['id']]) ?>
    </div>
    <?php if (count($images) > 1): ?>
        <ul class="product-card__thumbs" aria-label="More views of <?= e($product['name']) ?>">
            <?php foreach ($images as $i => $img): ?>
                <li>
                    <button type="button"
                            class="product-card__thumb<?= $i === 0 ? ' is-active' : '' ?>"
                            data-swap-src="<?= e($img['url']) ?>"
                            data-swap-alt="<?= e($img['alt']) ?>"
                            aria-label="View image <?= (int) $i + 1 ?> of <?= count($images) ?>">
                        <img src="<?= e($img['url']) ?>" alt="" loading="lazy">
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a class="product-card__body" href="<?= e($href) ?>">
        <h3 class="product-card__name"><?= e($product['name']) ?></h3>
        <p class="product-card__sub"><?= e($subtitle) ?></p>
        <p class="product-card__price">
            <?php if ($onSale): ?>
                <span class="product-card__now"><?= money($product['sale_price']) ?></span>
                <s class="product-card__was"><?= money($product['price']) ?></s>
            <?php else: ?>
                <span class="product-card__now"><?= money($product['price']) ?></span>
            <?php endif; ?>
        </p>
    </a>
</article>
