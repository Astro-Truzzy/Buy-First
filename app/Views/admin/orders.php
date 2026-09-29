<?php
/** Receives: $orders, $status */
?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Orders</h1>
        <p class="admin-muted"><?= count($orders) ?> <?= $status ? e(Order::statusLabel($status)) : 'in this view' ?></p>
    </div>
</div>
<nav class="admin-filters" aria-label="Filter by status">
    <a href="/admin/orders" class="<?= $status === null ? 'is-active' : '' ?>">All</a>
    <?php foreach (Order::STATUSES as $s): ?>
        <?php
        $n = (int) ($counts[$s] ?? 0);
        $needsAttention = in_array($s, Order::ATTENTION_STATUSES, true) && $n > 0;
        ?>
        <a href="/admin/orders?status=<?= e($s) ?>" class="<?= $status === $s ? 'is-active' : '' ?>">
            <?= e(Order::statusLabel($s)) ?>
            <?php if ($needsAttention): ?>
                <span class="admin-filters__count"><?= $n > 9 ? '9+' : $n ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="admin-card admin-card--flush">
    <table class="admin-table">
        <thead>
            <tr><th>Order</th><th>When</th><th>Customer</th><th>Status</th><th>Items</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <?php $next = Order::nextAction($order['status']); ?>
                <tr>
                    <td><a href="/admin/orders/<?= e($order['order_number']) ?>"><?= e($order['order_number']) ?></a></td>
                    <td><?= e(date('j M Y H:i', strtotime($order['placed_at']))) ?></td>
                    <td><?= e($order['email']) ?></td>
                    <td>
                        <span class="status status--<?= e($order['status']) ?>"><?= e(Order::statusLabel($order['status'])) ?></span>
                        <?php if (!empty($order['payment_claimed_at'])): ?>
                            <span class="admin-tag admin-tag--alert" title="Customer says they've paid — needs verification">Verify payment</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $order['line_count']) ?></td>
                    <td><?= money($order['total']) ?></td>
                    <td class="admin-table__actions">
                        <?php if ($next): ?>
                            <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= e($next['status']) ?>">
                                <button class="btn btn--outline btn--small" type="submit"><?= e($next['label']) ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if (Order::canCancel($order['status'])): ?>
                            <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status"
                                  data-confirm="Cancel order <?= e($order['order_number']) ?>? Items go back into stock.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn btn--outline btn--small" type="submit">Cancel</button>
                            </form>
                        <?php elseif (Order::canRefund($order['status'])): ?>
                            <form method="post" action="/admin/orders/<?= e($order['order_number']) ?>/status"
                                  data-confirm="Refund order <?= e($order['order_number']) ?>? The payment is marked refunded and items go back into stock.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="refunded">
                                <button class="btn btn--outline btn--small" type="submit">Refund</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
