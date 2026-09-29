<?php
/**
 * Master layout. Every page on the site is rendered inside this shell.
 * Variables provided by render(): $content, $title, $transparentHeader
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Applies a saved dark-mode preference before first paint, so the
         page never flashes the wrong theme. See main.js for the toggle. -->
    <script>(function(){try{var t=localStorage.getItem('bf-theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
    <title><?= e($title) ?> | BuyFirst</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=2">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="shortcut icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#e01e2f">
    <meta name="theme-color" content="#e01e2f">

    <!-- Fonts: Anton (display) + Archivo (body). preconnect speeds up the
         first connection to the font server. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body<?= consent() === '' ? ' class="has-cookie-banner"' : '' ?>>
    <!-- Skip link: invisible until focused with the keyboard (Tab key).
         Lets keyboard and screen-reader users jump past the navigation. -->
    <a class="skip-link" href="#main">Skip to main content</a>

    <!-- Hidden filter def for the glass panels' edge distortion (header,
         mega menu, search suggestions, cookie banner) — referenced from
         main.css via backdrop-filter: var(--glass-distortion). Chromium
         renders the ripple; browsers that don't support SVG filters in
         backdrop-filter just keep the plain blur. -->
    <svg class="visually-hidden-filters" aria-hidden="true" focusable="false">
        <filter id="glass-distortion" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence type="fractalNoise" baseFrequency="0.01 0.015" numOctaves="2" seed="7" result="noise" />
            <feGaussianBlur in="noise" stdDeviation="3" result="softNoise" />
            <feDisplacementMap in="SourceGraphic" in2="softNoise" scale="45" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main id="main">
        <?php if ($flash = flash_get()): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" role="status" aria-live="polite" data-flash>
                <div class="container flash__inner">
                    <?php if ($flash['type'] === 'cart'): ?>
                        <svg class="flash__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.18" />
                            <path d="M5.5 10.5l3 3 6-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    <?php endif; ?>
                    <span class="flash__message"><?= e($flash['message']) ?></span>
                    <button type="button" class="flash__close" data-flash-close aria-label="Dismiss">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
    <?php require __DIR__ . '/../partials/cookie-banner.php'; ?>

    <!-- "defer" = download in parallel, run after the HTML is parsed -->
    <script src="/assets/js/main.js" defer></script>
</body>
</html>
