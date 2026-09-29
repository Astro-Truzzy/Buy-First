<?php
/**
 * Account sidebar. Highlights the current section from the URL.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/account';
$active = fn(string $prefix): string => str_starts_with($path, $prefix) ? ' is-active' : '';
$me = Auth::user();
$initials = strtoupper(mb_substr($me['first_name'] ?? 'B', 0, 1) . mb_substr($me['last_name'] ?? 'F', 0, 1));
?>
<aside class="account-side">
    <div class="account-side__who">
        <span class="account-side__initials" aria-hidden="true"><?= e($initials) ?></span>
        <div>
            <strong><?= e($me['first_name']) ?></strong>
            <small><?= !empty($me['is_member']) ? 'Club member' : 'Account' ?></small>
        </div>
    </div>
    <nav class="account-nav" aria-label="Account">
        <a class="account-nav__link<?= $path === '/account' ? ' is-active' : '' ?>" href="/account">Overview</a>
        <a class="account-nav__link<?= $active('/account/orders') ?>" href="/account/orders">Orders</a>
        <a class="account-nav__link<?= $path === '/wishlist' ? ' is-active' : '' ?>" href="/wishlist">Wishlist</a>
        <a class="account-nav__link<?= $active('/account/addresses') ?>" href="/account/addresses">Addresses</a>
        <a class="account-nav__link<?= $path === '/membership' ? ' is-active' : '' ?>" href="/membership">Club</a>
        <a class="account-nav__link<?= $active('/account/profile') ?>" href="/account/profile">Profile</a>
        <?php if (Auth::isAdmin()): ?>
            <a class="account-nav__link" href="/admin">Admin</a>
        <?php endif; ?>
    </nav>
    <form method="post" action="/logout" class="account-nav__logout">
        <?= csrf_field() ?>
        <button type="submit">Sign Out</button>
    </form>
</aside>
