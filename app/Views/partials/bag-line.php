<?php
/**
 * One bag line. Used on /cart and /checkout so the shopper can change
 * quantity or remove an item until the moment they pay.
 *
 * Expects: $item (hydrated cart row), optional $return ('/cart'|'/checkout'),
 * optional $compact (bool) for the checkout sidebar.
 */
$return  = $return ?? '/cart';
$compact = !empty($compact);
$qty     = (int) $item['quantity'];
$stock   = (int) $item['stock'];
$max     = min(10, max($stock, $qty));
$onSale  = (float) $item['unit_price'] < (float) $item['full_price'];
$lineId  = (string) $item['id'];
$name    = (string) $item['name'];
?>
<article class="bag-line<?= $compact ? ' bag-line--compact' : '' ?>">
    <a class="bag-line__media" href="/product/<?= e($item['slug']) ?>">
        <img src="<?= e($item['image'] ?? '') ?>" alt="<?= e($name) ?>" loading="lazy">
    </a>

    <div class="bag-line__info">
        <div class="bag-line__top">
            <h2 class="bag-line__name">
                <a href="/product/<?= e($item['slug']) ?>"><?= e($name) ?></a>
            </h2>
            <p class="bag-line__price">
                <?php if ($onSale): ?>
                    <s><?= money((float) $item['full_price'] * $qty) ?></s>
                <?php endif; ?>
                <strong><?= money((float) $item['unit_price'] * $qty) ?></strong>
            </p>
        </div>

        <p class="bag-line__meta"><?= e($item['color']) ?> · Size <?= e($item['size']) ?></p>

        <?php if ($qty >= $stock): ?>
            <p class="bag-line__stock">Max available — only <?= e((string) $stock) ?> in stock.</p>
        <?php endif; ?>

        <div class="bag-line__actions">
            <form method="post" action="/cart/update" class="qty-step">
                <?= csrf_field() ?>
                <input type="hidden" name="item_id" value="<?= e($lineId) ?>">
                <input type="hidden" name="return" value="<?= e($return) ?>">
                <button type="submit" name="quantity" value="<?= e((string) ($qty - 1)) ?>"
                        aria-label="<?= $qty <= 1 ? 'Remove ' . $name : 'Decrease quantity' ?>">−</button>
                <span class="qty-step__n" aria-live="polite"><?= e((string) $qty) ?></span>
                <button type="submit" name="quantity" value="<?= e((string) ($qty + 1)) ?>"
                        <?= $qty >= $max ? 'disabled' : '' ?>
                        aria-label="Increase quantity">+</button>
            </form>

            <form method="post" action="/cart/remove">
                <?= csrf_field() ?>
                <input type="hidden" name="item_id" value="<?= e($lineId) ?>">
                <input type="hidden" name="return" value="<?= e($return) ?>">
                <button class="bag-line__remove" type="submit" aria-label="Remove <?= e($name) ?> from bag">
                    Remove
                </button>
            </form>
        </div>
    </div>
</article>
