<?php /** Receives: $user, $orders */ ?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__kicker">Your account</p>
        <h1 class="display">Orders</h1>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <section class="account-panel">
                <?php if ($orders === []): ?>
                    <p class="account__note">You haven’t placed an order yet.</p>
                    <a class="btn btn--primary" href="/new">Start shopping</a>
                <?php else: ?>
                    <ul class="account-orders">
                        <?php foreach ($orders as $order): ?>
                            <li>
                                <a class="account-order" href="/account/orders/<?= e($order['order_number']) ?>">
                                    <span class="account-order__media">
                                        <?php if (!empty($order['thumb'])): ?>
                                            <img src="<?= e($order['thumb']) ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                    </span>
                                    <span class="account-order__body">
                                        <strong><?= e($order['order_number']) ?></strong>
                                        <small>
                                            <?= e(date('j M Y', strtotime($order['placed_at']))) ?>
                                            · <?= e((string) $order['line_count']) ?>
                                            <?= (int) $order['line_count'] === 1 ? 'item' : 'items' ?>
                                        </small>
                                    </span>
                                    <span class="status status--<?= e($order['status']) ?>"><?= e(Order::statusLabel($order['status'])) ?></span>
                                    <span class="account-order__total"><?= money($order['total']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
