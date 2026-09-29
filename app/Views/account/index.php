<?php /** Receives: $user, $orders, $orderCount, $wishCount, $bagCount, $defaultAddress */ ?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__kicker">Your account</p>
        <h1 class="display">Hi, <?= e($user['first_name']) ?>.</h1>
        <p class="account__meta">
            <?= e($user['email']) ?>
            <?php if ($user['is_member']): ?>
                · <span class="account__club">BuyFirst Club</span>
            <?php endif; ?>
        </p>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <div class="account-stats">
                <a class="account-stat" href="/account/orders">
                    <span>Orders</span>
                    <strong><?= e((string) $orderCount) ?></strong>
                </a>
                <a class="account-stat" href="/wishlist">
                    <span>Wishlist</span>
                    <strong><?= e((string) $wishCount) ?></strong>
                </a>
                <a class="account-stat" href="/cart">
                    <span>Bag</span>
                    <strong><?= e((string) $bagCount) ?></strong>
                </a>
                <a class="account-stat" href="/membership">
                    <span>Club</span>
                    <strong><?= !empty($user['is_member']) ? 'In' : 'Join' ?></strong>
                </a>
            </div>

            <?php if (empty($user['is_member'])): ?>
                <section class="account-banner">
                    <div>
                        <h2>BuyFirst Club</h2>
                        <p>Free to join. Free standard delivery on every order, 60-day returns, member-only drops.</p>
                    </div>
                    <form method="post" action="/membership/join">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary" type="submit">Join The Club</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="account-panel">
                <div class="account-panel__title">
                    <h2>Recent orders</h2>
                    <a href="/account/orders">View all</a>
                </div>

                <?php if ($orders === []): ?>
                    <p class="account__note">No orders yet. When you check out, they’ll land here.</p>
                    <a class="btn btn--primary" href="/new">Shop New Arrivals</a>
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

            <section class="account-panel">
                <div class="account-panel__title">
                    <h2>Delivery address</h2>
                    <a href="/account/addresses"><?= $defaultAddress ? 'Manage' : 'Add one' ?></a>
                </div>
                <?php if ($defaultAddress): ?>
                    <address class="account-address">
                        <strong><?= e($defaultAddress['label']) ?></strong>
                        <?= e($defaultAddress['first_name'] . ' ' . $defaultAddress['last_name']) ?><br>
                        <?= e($defaultAddress['line1']) ?><?php if ($defaultAddress['line2']): ?>, <?= e($defaultAddress['line2']) ?><?php endif; ?><br>
                        <?= e($defaultAddress['city']) ?> <?= e($defaultAddress['postcode']) ?>
                    </address>
                <?php else: ?>
                    <p class="account__note">Save an address to check out faster next time.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
