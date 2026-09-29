<?php
$base = rtrim(config()['app']['url'], '/');
?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#0a0a0a">
    <p style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#e01e2f;font-weight:700">BuyFirst</p>
    <h1 style="font-size:28px;text-transform:uppercase;margin:0 0 12px">It’s on the way.</h1>
    <p>Order <strong><?= e($order['order_number']) ?></strong> has shipped to <?= e($order['ship_city']) ?> <?= e($order['ship_postcode']) ?>.</p>

    <ul style="padding-left:18px;color:#555">
        <?php foreach ($lines as $line): ?>
            <li><?= e($line['product_name']) ?> — <?= e($line['size']) ?> × <?= e((string) $line['quantity']) ?></li>
        <?php endforeach; ?>
    </ul>

    <p>Standard parcels usually arrive in 3–7 working days nationwide; express in 1–2 days for Lagos and Abuja.</p>
    <p>
        <a href="<?= e($base . '/account/orders/' . $order['order_number']) ?>"
           style="display:inline-block;background:#0a0a0a;color:#fff;padding:12px 20px;text-decoration:none">Track order</a>
    </p>
    <p style="color:#8a8a84;font-size:13px">BuyFirst Ltd · Gear up. Move first.</p>
</div>
