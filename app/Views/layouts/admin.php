<?php
/**
 * Admin chrome — no storefront header/footer, no marketing JS.
 * Variables: $content, $title
 */
$path    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/admin';
$on      = fn(string $prefix): string => str_starts_with($path, $prefix) ? ' is-active' : '';
$pending    = Review::pendingCount();
$openOrders = Order::attentionCount();
$claims     = Order::paymentClaimsCount();
$user       = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Applies a saved dark-mode preference before first paint, so the
         page never flashes the wrong theme. See main.js for the toggle. -->
    <script>(function(){try{var t=localStorage.getItem('bf-theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
    <title><?= e($title) ?> | BuyFirst Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=2">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="shortcut icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#e01e2f">
    <meta name="theme-color" content="#e01e2f">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">
    <a class="skip-link" href="#main">Skip to main content</a>
    <div class="admin-shell">
        <aside class="admin-side">
            <a class="admin-side__brand" href="/admin">BUYFIRST<span>admin</span></a>
            <nav aria-label="Admin">
                <div class="admin-side__group">
                    <p class="admin-side__label">Overview</p>
                    <a href="/admin" class="<?= $path === '/admin' ? 'is-active' : '' ?>">Dashboard</a>
                </div>
                <div class="admin-side__group">
                    <p class="admin-side__label">Commerce</p>
                    <a href="/admin/orders" class="<?= $on('/admin/orders') ?>">
                        Orders
                        <?php if ($openOrders): ?>
                            <span class="admin-side__count"><?= $openOrders > 9 ? '9+' : (int) $openOrders ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/products" class="<?= $on('/admin/products') ?>">Products</a>
                    <a href="/admin/payment-settings" class="<?= $on('/admin/payment-settings') ?>">Payment settings</a>
                </div>
                <div class="admin-side__group">
                    <p class="admin-side__label">Content</p>
                    <a href="/admin/pages" class="<?= $on('/admin/pages') ?>">Pages</a>
                    <a href="/admin/reviews" class="<?= $on('/admin/reviews') ?>">
                        Reviews
                        <?php if ($pending): ?>
                            <span class="admin-side__count"><?= $pending > 9 ? '9+' : (int) $pending ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/audit" class="<?= $on('/admin/audit') ?>">Audit log</a>
                </div>
            </nav>
            <div class="admin-side__foot">
                <strong><?= e($user['first_name'] ?? 'Admin') ?></strong>
                <a href="/">View store</a>
                <button type="button" data-theme-toggle aria-pressed="false">
                    <span class="theme-toggle__label theme-toggle__label--light">Dark mode</span>
                    <span class="theme-toggle__label theme-toggle__label--dark">Light mode</span>
                </button>
                <form method="post" action="/logout">
                    <?= csrf_field() ?>
                    <button type="submit">Sign out</button>
                </form>
            </div>
        </aside>
        <div class="admin-main">
            <?php if ($flash = flash_get()): ?>
                <div class="flash flash--<?= e($flash['type']) ?>" role="status">
                    <div class="admin-wrap"><?= e($flash['message']) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($claims && !str_starts_with($path, '/admin/orders')): ?>
                <div class="flash flash--error" role="status">
                    <div class="admin-wrap">
                        <?= $claims ?> bank transfer<?= $claims > 1 ? 's' : '' ?> awaiting verification —
                        <a href="/admin/orders?status=pending" style="text-decoration:underline">review pending orders</a>.
                    </div>
                </div>
            <?php endif; ?>
            <main id="main" class="admin-wrap">
                <?= $content ?>
            </main>
        </div>
    </div>
    <script src="/assets/js/main.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</body>
</html>
