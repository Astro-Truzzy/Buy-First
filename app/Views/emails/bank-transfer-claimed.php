<?php
/** Receives: $order */
$base = rtrim(config()['app']['url'], '/');
?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#0a0a0a">
    <p style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#e01e2f;font-weight:700">BuyFirst Admin</p>
    <h1 style="font-size:24px;margin:0 0 12px">A customer says they've paid by bank transfer.</h1>
    <p>
        Order <strong><?= e($order['order_number']) ?></strong> (<?= e($order['email']) ?>) for
        <strong><?= money($order['total']) ?></strong> is marked as awaiting verification.
        Check the bank account, then mark the order as paid.
    </p>
    <p>
        <a href="<?= e($base . '/admin/orders/' . $order['order_number']) ?>"
           style="display:inline-block;background:#0a0a0a;color:#fff;padding:12px 20px;text-decoration:none">Review order</a>
    </p>
</div>
