<?php
/**
 * The bag page. Receives from CartController::index():
 *   $items, $subtotal, $discount, $delivery, $total, $freeFrom, $coupon
 */
$count = 0;
foreach ($items as $item) {
    $count += (int) $item['quantity'];
}
?>

<div class="container bag-page">
    <header class="flow-head">
        <p class="flow-head__kicker">Shopping bag</p>
        <h1 class="display page-title">Your Bag<?= $items !== [] ? ' <span>(' . e((string) $count) . ')</span>' : '' ?></h1>
        <?php if ($items !== []): ?>
            <div class="flow-head__note">
                <a href="/new">Continue shopping</a>
                <span aria-hidden="true">·</span>
                <a href="/checkout">Checkout</a>
                <span aria-hidden="true">·</span>
                <form method="post" action="/cart/clear"
                      data-confirm="Clear your bag? This removes all items.">
                    <?= csrf_field() ?>
                    <button type="submit" class="bag-line__remove">Clear all items</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($items === []): ?>

        <div class="bag-empty">
            <p>Your bag is empty — but it doesn't have to stay that way.</p>
            <a class="btn btn--primary" href="/new">Shop New Arrivals</a>
        </div>

    <?php else: ?>

        <div class="bag">
            <div class="bag__items">
                <?php foreach ($items as $item): ?>
                    <?= view('partials/bag-line', ['item' => $item, 'return' => '/cart']) ?>
                <?php endforeach; ?>
            </div>

            <aside class="bag-summary" aria-label="Order summary">
                <h2>Summary</h2>

                <form method="post" action="/cart/coupon" class="coupon-form">
                    <?= csrf_field() ?>
                    <?php if ($coupon): ?>
                        <p class="coupon-form__applied">
                            <strong><?= e($coupon['code']) ?></strong> applied
                            <button type="submit" name="remove" value="1" class="bag-line__remove">Remove</button>
                        </p>
                    <?php else: ?>
                        <label class="visually-hidden" for="cart-coupon">Promo code</label>
                        <input type="text" id="cart-coupon" name="code" placeholder="Promo code" maxlength="30">
                        <button class="btn btn--outline" type="submit">Apply</button>
                    <?php endif; ?>
                </form>

                <dl class="bag-summary__rows">
                    <div><dt>Subtotal</dt><dd><?= money($subtotal) ?></dd></div>
                    <?php if ($discount > 0): ?>
                        <div><dt>Discount</dt><dd>−<?= money($discount) ?></dd></div>
                    <?php endif; ?>
                    <div>
                        <dt>Estimated Delivery</dt>
                        <dd><?= $delivery === 0.0 ? 'Free' : money($delivery) ?></dd>
                    </div>
                    <div class="bag-summary__total"><dt>Total</dt><dd><?= money($total) ?></dd></div>
                </dl>

                <?php if ($delivery > 0): ?>
                    <p class="bag-summary__note">
                        Spend <?= money($freeFrom - $subtotal) ?> more for free standard delivery.
                    </p>
                <?php endif; ?>

                <a class="btn btn--primary bag-summary__cta" href="/checkout">Checkout</a>

                <?php if (!Auth::check()): ?>
                    <p class="bag-summary__note">
                        <a href="/login">Sign in</a> to keep your bag across devices.
                    </p>
                <?php endif; ?>
            </aside>
        </div>

    <?php endif; ?>
</div>
