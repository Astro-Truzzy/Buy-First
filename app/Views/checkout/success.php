<?php
/**
 * Order confirmation. Receives: $order, $lines
 */
$methodLabel = $order['delivery_method'] === 'express' ? 'Express (1–2 working days)' : 'Standard (3–7 working days)';
?>

<div class="container checkout-success">
    <p class="checkout-success__kicker">Thank you</p>
    <h1 class="display">Order Confirmed.</h1>
    <p class="checkout-success__lede">
        We’ve sent a receipt to <strong><?= e($order['email']) ?></strong>.
        Your order number is <strong><?= e($order['order_number']) ?></strong> —
        keep it handy if you need to get in touch.
    </p>

    <div class="checkout-success__grid">
        <section class="account-panel">
            <h2>Items</h2>
            <ul class="checkout-mini checkout-mini--rich">
                <?php foreach ($lines as $line): ?>
                    <li>
                        <?php if (!empty($line['image'])): ?>
                            <img src="<?= e($line['image']) ?>" alt="">
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
            <h2>Delivering to</h2>
            <address class="checkout-success__addr">
                <?= e($order['ship_first_name'] . ' ' . $order['ship_last_name']) ?><br>
                <?= e($order['ship_line1']) ?><br>
                <?php if ($order['ship_line2']): ?><?= e($order['ship_line2']) ?><br><?php endif; ?>
                <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?><br>
                <?= e(country_name($order['ship_country'])) ?>
            </address>
            <p><?= e($methodLabel) ?></p>
            <?php if (($order['payment_method'] ?? 'card') === 'bank_transfer'): ?>
                <?php $bankAccount = BankAccount::get(); ?>
                <p class="field__hint">
                    Your order is pending until we receive your bank transfer. Transfer
                    <strong><?= money($order['total']) ?></strong> to:
                </p>
                <dl class="bag-summary__rows">
                    <div><dt>Bank</dt><dd><?= e($bankAccount['bank_name']) ?></dd></div>
                    <div><dt>Account name</dt><dd><?= e($bankAccount['account_name']) ?></dd></div>
                    <div><dt>Account number</dt><dd><?= e($bankAccount['account_number']) ?></dd></div>
                </dl>
                <?php if ($order['status'] === 'pending' && !empty($order['payment_claimed_at'])): ?>
                    <p class="field__hint field__hint--success">
                        Thanks — you told us on <?= e(date('j F, H:i', strtotime($order['payment_claimed_at']))) ?>.
                        We're verifying it and will update your order shortly.
                    </p>
                <?php elseif ($order['status'] === 'pending'): ?>
                    <form method="post" action="/checkout/success/<?= e($order['order_number']) ?>/claim-payment" class="claim-payment">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary" type="submit">I've successfully transferred the money</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="field__hint">Payment received.</p>
            <?php endif; ?>
        </section>
    </div>

    <p class="checkout-success__next">
        <a class="btn btn--primary" href="/new">Continue Shopping</a>
        <?php if (Auth::check()): ?>
            <a class="btn btn--outline" href="/account/orders/<?= e($order['order_number']) ?>">View Order</a>
        <?php endif; ?>
    </p>
</div>
