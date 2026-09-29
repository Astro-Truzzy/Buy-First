<?php
/** Receives: $user, $order, $lines */
$methodLabel = $order['delivery_method'] === 'express' ? 'Express (1–2 working days)' : 'Standard (3–7 working days)';
$pipeline = ['paid', 'packed', 'shipped', 'delivered'];
$current  = $order['status'];
$halted   = in_array($current, ['cancelled', 'refunded', 'pending'], true);
?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__crumb"><a href="/account/orders">Orders</a> / <?= e($order['order_number']) ?></p>
        <h1 class="display"><?= e($order['order_number']) ?></h1>
        <p class="account__meta">
            Placed <?= e(date('j F Y, H:i', strtotime($order['placed_at']))) ?>
            · <span class="status status--<?= e($current) ?>"><?= e(Order::statusLabel($current)) ?></span>
        </p>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <?php if (!$halted): ?>
                <ol class="order-track" aria-label="Order progress">
                    <?php foreach ($pipeline as $step): ?>
                        <?php
                        $done    = array_search($current, $pipeline, true) >= array_search($step, $pipeline, true);
                        $isHere  = $current === $step;
                        ?>
                        <li class="<?= $done ? 'is-done' : '' ?><?= $isHere ? ' is-current' : '' ?>">
                            <?= e(Order::statusLabel($step)) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <div class="account-split">
                <section class="account-panel">
                    <h2>Items</h2>
                    <ul class="checkout-mini checkout-mini--rich">
                        <?php foreach ($lines as $line): ?>
                            <li>
                                <?php if (!empty($line['image'])): ?>
                                    <?php if (!empty($line['slug'])): ?>
                                        <a href="/product/<?= e($line['slug']) ?>">
                                            <img src="<?= e($line['image']) ?>" alt="<?= e($line['product_name']) ?>">
                                        </a>
                                    <?php else: ?>
                                        <img src="<?= e($line['image']) ?>" alt="">
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span>
                                    <?= e($line['product_name']) ?>
                                    <small><?= e($line['color']) ?> · <?= e($line['size']) ?> · Qty <?= e((string) $line['quantity']) ?></small>
                                </span>
                                <strong><?= money($line['line_total']) ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="bag-summary__rows">
                        <div><dt>Subtotal</dt><dd><?= money($order['subtotal']) ?></dd></div>
                        <?php if ((float) $order['discount'] > 0): ?>
                            <div><dt>Discount</dt><dd>−<?= money($order['discount']) ?></dd></div>
                        <?php endif; ?>
                        <div>
                            <dt>Delivery</dt>
                            <dd><?= (float) $order['shipping_cost'] === 0.0 ? 'Free' : money($order['shipping_cost']) ?></dd>
                        </div>
                        <div><dt>VAT</dt><dd><?= money($order['tax']) ?></dd></div>
                        <div class="bag-summary__total"><dt>Total</dt><dd><?= money($order['total']) ?></dd></div>
                    </dl>
                </section>

                <section class="account-panel">
                    <h2>Delivered to</h2>
                    <address class="checkout-success__addr">
                        <?= e($order['ship_first_name'] . ' ' . $order['ship_last_name']) ?><br>
                        <?= e($order['ship_line1']) ?><br>
                        <?php if ($order['ship_line2']): ?><?= e($order['ship_line2']) ?><br><?php endif; ?>
                        <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?><br>
                        <?= e(country_name($order['ship_country'])) ?>
                    </address>
                    <p><?= e($methodLabel) ?></p>

                    <?php if (($order['payment_method'] ?? 'card') === 'bank_transfer' && $current === 'pending'): ?>
                        <?php if (!empty($order['payment_claimed_at'])): ?>
                            <p class="field__hint field__hint--success">
                                Thanks — you told us on <?= e(date('j F, H:i', strtotime($order['payment_claimed_at']))) ?>.
                                We're verifying it and will update your order shortly.
                            </p>
                        <?php else: ?>
                            <?php $bankAccount = BankAccount::get(); ?>
                            <p class="field__hint">
                                This order is pending until we receive your bank transfer. Transfer
                                <strong><?= money($order['total']) ?></strong> to:
                            </p>
                            <dl class="bag-summary__rows">
                                <div><dt>Bank</dt><dd><?= e($bankAccount['bank_name']) ?></dd></div>
                                <div><dt>Account name</dt><dd><?= e($bankAccount['account_name']) ?></dd></div>
                                <div><dt>Account number</dt><dd><?= e($bankAccount['account_number']) ?></dd></div>
                            </dl>
                            <form method="post" action="/checkout/success/<?= e($order['order_number']) ?>/claim-payment" class="claim-payment">
                                <?= csrf_field() ?>
                                <button class="btn btn--primary" type="submit">I've successfully transferred the money</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post" action="/account/orders/<?= e($order['order_number']) ?>/buy-again" class="account-buyagain">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary" type="submit">Buy again</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
