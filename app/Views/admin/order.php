<?php
/** Receives: $order, $lines, $payment */
$current   = $order['status'];
$next      = Order::nextAction($current);
$pipeline  = ['paid', 'packed', 'shipped', 'delivered'];
$halted    = in_array($current, ['cancelled', 'refunded'], true);
$methodLabel = $order['delivery_method'] === 'express' ? 'Express' : 'Standard';
$paymentMethodLabel = ($order['payment_method'] ?? 'card') === 'bank_transfer' ? 'Bank transfer' : 'Card';
?>
<div class="admin-head">
    <div>
        <p class="admin-crumb"><a href="/admin/orders">Orders</a> / <?= e($order['order_number']) ?></p>
        <h1 class="admin-h1"><?= e($order['order_number']) ?></h1>
        <p class="admin-muted">
            <?= e(date('j F Y, H:i', strtotime($order['placed_at']))) ?>
            · <?= e($order['email']) ?>
            · <span class="status status--<?= e($current) ?>"><?= e(Order::statusLabel($current)) ?></span>
        </p>
    </div>
    <?php if ($next): ?>
        <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="<?= e($next['status']) ?>">
            <button class="btn btn--primary" type="submit"><?= e($next['label']) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php if (!$halted): ?>
    <ol class="order-track" aria-label="Fulfilment">
        <?php foreach ($pipeline as $step): ?>
            <?php
            $pos     = array_search($current, $pipeline, true);
            $stepPos = array_search($step, $pipeline, true);
            $done    = $pos !== false && $stepPos !== false && $pos >= $stepPos;
            $isHere  = $current === $step;
            ?>
            <li class="<?= $done ? 'is-done' : '' ?><?= $isHere ? ' is-current' : '' ?>">
                <?= e(Order::statusLabel($step)) ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php if (!$halted): ?>
<section class="admin-card">
    <h2>Update status</h2>
    <p class="admin-hint">Move the order along fulfilment, or stop it below.</p>
    <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status" class="admin-status">
        <?= csrf_field() ?>
        <label>
            Status
            <select name="status">
                <?php foreach (Order::FULFILMENT_STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $s === $current ? 'selected' : '' ?>><?= e(Order::statusLabel($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn--outline" type="submit">Save status</button>
    </form>
</section>
<?php endif; ?>

<?php if (Order::canCancel($current) || Order::canRefund($current)): ?>
<section class="admin-card">
    <h2>Cancel or refund</h2>
    <p class="admin-hint">
        Cancel stops fulfilment. Refund marks the payment returned.
        Both put the items back into stock.
    </p>
    <div class="admin-halt">
        <?php if (Order::canCancel($current)): ?>
            <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status"
                  data-confirm="Cancel order <?= e($order['order_number']) ?>? Items go back into stock.">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="cancelled">
                <button class="btn btn--outline" type="submit">Cancel order</button>
            </form>
        <?php endif; ?>
        <?php if (Order::canRefund($current)): ?>
            <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status"
                  data-confirm="Refund order <?= e($order['order_number']) ?>? The payment is marked refunded and items go back into stock.">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="refunded">
                <button class="btn btn--accent" type="submit">Issue refund</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<div class="admin-grid">
    <section class="admin-card">
        <h2>Items</h2>
        <table class="admin-table">
            <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Line</th></tr></thead>
            <tbody>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= e($line['product_name']) ?><br><small><?= e($line['color']) ?> · <?= e($line['size']) ?></small></td>
                        <td><code><?= e($line['sku']) ?></code></td>
                        <td><?= e((string) $line['quantity']) ?></td>
                        <td><?= money($line['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <dl class="bag-summary__rows">
            <div><dt>Subtotal</dt><dd><?= money($order['subtotal']) ?></dd></div>
            <?php if ((float) $order['discount'] > 0): ?>
                <div><dt>Discount</dt><dd>−<?= money($order['discount']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Delivery</dt><dd><?= money($order['shipping_cost']) ?></dd></div>
            <div><dt>VAT</dt><dd><?= money($order['tax']) ?></dd></div>
            <div class="bag-summary__total"><dt>Total</dt><dd><?= money($order['total']) ?></dd></div>
        </dl>
    </section>
    <section class="admin-card">
        <h2>Ship to</h2>
        <address class="checkout-success__addr">
            <?= e($order['ship_first_name'] . ' ' . $order['ship_last_name']) ?><br>
            <?= e($order['ship_line1']) ?><br>
            <?php if ($order['ship_line2']): ?><?= e($order['ship_line2']) ?><br><?php endif; ?>
            <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?><br>
            <?= e(country_name($order['ship_country'])) ?>
        </address>
        <p><?= e($methodLabel) ?></p>
    </section>
    <section class="admin-card">
        <h2>Payment</h2>
        <dl class="bag-summary__rows">
            <div><dt>Method</dt><dd><?= e($paymentMethodLabel) ?></dd></div>
            <?php if ($payment): ?>
                <div><dt>Status</dt><dd><?= e(ucfirst($payment['status'])) ?></dd></div>
                <?php if ($payment['reference']): ?>
                    <div><dt>Reference</dt><dd><code><?= e($payment['reference']) ?></code></dd></div>
                <?php endif; ?>
            <?php endif; ?>
        </dl>
        <?php if (($order['payment_method'] ?? 'card') === 'bank_transfer' && $payment && $payment['status'] === 'pending'): ?>
            <?php if (!empty($order['payment_claimed_at'])): ?>
                <p class="admin-hint admin-hint--alert">
                    Customer says they paid on <?= e(date('j M Y, H:i', strtotime($order['payment_claimed_at']))) ?>.
                    Check the bank account, then use "Mark as paid" above.
                </p>
            <?php else: ?>
                <p class="admin-hint">Awaiting bank transfer. Use "Mark as paid" above once the funds arrive.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
