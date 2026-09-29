<?php
$methodLabel = ($order['delivery_method'] ?? '') === 'express'
    ? 'Express (1–2 working days)'
    : 'Standard (3–7 working days)';
$base = rtrim(config()['app']['url'], '/');
?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#0a0a0a">
    <p style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#e01e2f;font-weight:700">BuyFirst</p>
    <h1 style="font-size:28px;text-transform:uppercase;margin:0 0 12px">Order confirmed.</h1>
    <p>Thanks <?= e($order['ship_first_name']) ?>. We’re getting <strong><?= e($order['order_number']) ?></strong> ready.</p>

    <table style="width:100%;border-collapse:collapse;margin:24px 0">
        <?php foreach ($lines as $line): ?>
            <tr>
                <td style="padding:8px 0;border-bottom:1px solid #e2e2dd">
                    <?= e($line['product_name']) ?><br>
                    <span style="color:#555"><?= e($line['color']) ?> · <?= e($line['size']) ?> · Qty <?= e((string) $line['quantity']) ?></span>
                </td>
                <td style="padding:8px 0;border-bottom:1px solid #e2e2dd;text-align:right"><?= money($line['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><td style="padding:8px 0">Subtotal</td><td style="text-align:right"><?= money($order['subtotal']) ?></td></tr>
        <?php if ((float) $order['discount'] > 0): ?>
            <tr><td style="padding:4px 0">Discount</td><td style="text-align:right">−<?= money($order['discount']) ?></td></tr>
        <?php endif; ?>
        <tr><td style="padding:4px 0">Delivery</td><td style="text-align:right"><?= (float) $order['shipping_cost'] === 0.0 ? 'Free' : money($order['shipping_cost']) ?></td></tr>
        <tr><td style="padding:4px 0">VAT</td><td style="text-align:right"><?= money($order['tax']) ?></td></tr>
        <tr><td style="padding:12px 0;font-weight:700">Total</td><td style="text-align:right;font-weight:700"><?= money($order['total']) ?></td></tr>
    </table>

    <p style="margin:0 0 4px"><strong>Delivering to</strong></p>
    <p style="margin:0;color:#555">
        <?= e($order['ship_first_name'] . ' ' . $order['ship_last_name']) ?><br>
        <?= e($order['ship_line1']) ?><br>
        <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?><br>
        <?= e($methodLabel) ?>
    </p>

    <?php if (($order['payment_method'] ?? 'card') === 'bank_transfer'): ?>
        <?php $bankAccount = BankAccount::get(); ?>
        <p style="margin:20px 0 4px"><strong>Bank transfer details</strong></p>
        <p style="margin:0;color:#555">
            Your order is pending until we receive your transfer of <?= money($order['total']) ?> to:<br>
            <?= e($bankAccount['bank_name']) ?><br>
            <?= e($bankAccount['account_name']) ?><br>
            <?= e($bankAccount['account_number']) ?>
        </p>
    <?php endif; ?>

    <p style="margin-top:28px">
        <a href="<?= e($base . '/account/orders/' . $order['order_number']) ?>"
           style="display:inline-block;background:#0a0a0a;color:#fff;padding:12px 20px;text-decoration:none">View order</a>
    </p>
    <p style="color:#8a8a84;font-size:13px">BuyFirst Ltd · Gear up. Move first.</p>
</div>
