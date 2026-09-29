<?php
/**
 * Site header: logo, main nav with mega menu, action icons, mobile menu.
 * The nav links point at pages we will build in the coming steps.
 */
?>
<header class="site-header<?= !empty($transparentHeader) ? ' site-header--transparent' : '' ?>" id="site-header">
    <div class="header-inner">

        <a class="logo" href="/" aria-label="BuyFirst home">BUYFIRST<span class="logo__mark">.</span></a>

        <nav class="main-nav" aria-label="Main navigation">
            <ul class="main-nav__list">
                <li class="main-nav__item">
                    <a href="/new">New &amp; Featured</a>
                </li>
                <li class="main-nav__item main-nav__item--has-mega">
                    <a href="/men">Men</a>
                    <div class="mega" aria-label="Men submenu">
                        <div class="mega__inner">
                            <div class="mega__col">
                                <h3>Shoes</h3>
                                <a href="/men/shoes?sport=running">Running</a>
                                <a href="/men/shoes?sport=training">Training &amp; Gym</a>
                                <a href="/men/shoes?sport=basketball">Basketball</a>
                                <a href="/men/shoes?sport=lifestyle">Lifestyle</a>
                            </div>
                            <div class="mega__col">
                                <h3>Clothing</h3>
                                <a href="/men/clothing">All Clothing</a>
                                <a href="/men/clothing?sport=running">Running</a>
                                <a href="/men/clothing?sport=training">Training &amp; Gym</a>
                                <a href="/men/clothing?sport=lifestyle">Lifestyle</a>
                            </div>
                            <div class="mega__col">
                                <h3>Featured</h3>
                                <a href="/new?gender=men">Just In</a>
                                <a href="/men?badge=best-seller">Best Sellers</a>
                                <a href="/sale?gender=men">Sale</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="main-nav__item main-nav__item--has-mega">
                    <a href="/women">Women</a>
                    <div class="mega" aria-label="Women submenu">
                        <div class="mega__inner">
                            <div class="mega__col">
                                <h3>Shoes</h3>
                                <a href="/women/shoes?sport=running">Running</a>
                                <a href="/women/shoes?sport=training">Training &amp; Gym</a>
                                <a href="/women/shoes?sport=lifestyle">Lifestyle</a>
                            </div>
                            <div class="mega__col">
                                <h3>Clothing</h3>
                                <a href="/women/clothing">All Clothing</a>
                                <a href="/women/clothing?sport=running">Running</a>
                                <a href="/women/clothing?sport=training">Training &amp; Gym</a>
                                <a href="/women/clothing?sport=lifestyle">Lifestyle</a>
                            </div>
                            <div class="mega__col">
                                <h3>Featured</h3>
                                <a href="/new?gender=women">Just In</a>
                                <a href="/women?badge=best-seller">Best Sellers</a>
                                <a href="/sale?gender=women">Sale</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="main-nav__item">
                    <a href="/kids">Kids</a>
                </li>
                <li class="main-nav__item main-nav__item--has-mega">
                    <a href="/sport">Sport</a>
                    <div class="mega" aria-label="Sport submenu">
                        <div class="mega__inner">
                            <div class="mega__col">
                                <h3>By Sport</h3>
                                <a href="/sport/running">Running</a>
                                <a href="/sport/training">Training &amp; Gym</a>
                                <a href="/sport/basketball">Basketball</a>
                                <a href="/sport/football">Football</a>
                                <a href="/sport/lifestyle">Lifestyle</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="main-nav__item">
                    <a href="/journal">Journal</a>
                </li>
                <li class="main-nav__item">
                    <a class="main-nav__sale" href="/sale">Sale</a>
                </li>
            </ul>
        </nav>

        <div class="header-actions">
            <form class="header-search" action="/search" method="get" role="search" data-header-search>
                <div class="header-search__field">
                    <label class="header-search__icon" for="search-q">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <span class="visually-hidden">Search products</span>
                    </label>
                    <input type="search" id="search-q" name="q" placeholder="Search gear, sport, or SKU"
                           autocomplete="off" minlength="2" required>
                </div>
                <div class="search-suggest" id="search-suggest" hidden></div>
            </form>
            <button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Toggle dark mode">
                <svg class="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                <svg class="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a6.8 6.8 0 0 0 10.5 10.5z"/></svg>
            </button>
            <a class="icon-btn" href="<?= Auth::check() ? '/account' : '/login' ?>"
               aria-label="<?= Auth::check() ? 'My account' : 'Sign in' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
            </a>
            <?php $wishCount = Wishlist::count(); ?>
            <a class="icon-btn icon-btn--wish<?= $wishCount > 0 ? ' is-saved' : '' ?>" href="/wishlist"
               aria-label="Wishlist<?= $wishCount > 0 ? ', ' . $wishCount . ' item' . ($wishCount === 1 ? '' : 's') : '' ?>">
                <svg viewBox="0 0 24 24" fill="<?= $wishCount > 0 ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21C7 16.5 3 13.2 3 9.1 3 6.3 5.2 4 8 4c1.6 0 3.1.8 4 2 .9-1.2 2.4-2 4-2 2.8 0 5 2.3 5 5.1 0 4.1-4 7.4-9 11.9z"/></svg>
                <?php if ($wishCount > 0): ?>
                    <span class="bag-count" aria-hidden="true"><?= $wishCount > 9 ? '9+' : $wishCount ?></span>
                <?php endif; ?>
            </a>
            <?php
            $cartItems = Cart::items();
            $bagCount = 0;
            foreach ($cartItems as $cartItem) {
                $bagCount += (int) $cartItem['quantity'];
            }
            $cartSubtotal = Cart::subtotal($cartItems);
            ?>
            <a class="icon-btn icon-btn--bag" href="/cart" id="cart-toggle"
               aria-haspopup="true" aria-expanded="false" aria-controls="cart-drawer"
               aria-label="Shopping bag<?= $bagCount > 0 ? ', ' . $bagCount . ' item' . ($bagCount === 1 ? '' : 's') : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 8h12l-1 13H7L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                <span class="bag-count" id="cart-count" aria-hidden="true"<?= $bagCount > 0 ? '' : ' hidden' ?>><?= $bagCount > 9 ? '9+' : $bagCount ?></span>
            </a>
            <button class="icon-btn nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
</header>

<!-- Mobile navigation and cart drawer live outside <header> on purpose: the
     transparent header gets `backdrop-filter` once scrolled, which makes it
     a containing block for any `position: fixed` descendant and breaks
     full-viewport fixed positioning of these off-canvas panels. -->

<!-- Mobile navigation: hidden off-canvas, slides in from the right -->
<nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
    <a href="/new">New &amp; Featured</a>
    <a href="/men">Men</a>
    <a href="/women">Women</a>
    <a href="/kids">Kids</a>
    <a href="/sport">Sport</a>
    <a class="mobile-nav__sale" href="/sale">Sale</a>
    <hr>
    <a href="/search">Search</a>
    <a href="/wishlist">Wishlist</a>
    <a href="/account">My Account</a>
    <a href="/help">Help</a>
    <a href="/journal">Journal</a>
</nav>
<div class="nav-overlay" hidden></div>

<!-- Cart drawer: off-canvas, slides in from the right showing bag contents -->
<aside class="cart-drawer" id="cart-drawer" aria-label="Shopping bag" aria-hidden="true">
    <div class="cart-drawer__head">
        <h2 id="cart-drawer-title">Your Bag<?= $bagCount > 0 ? ' <span>(' . e((string) $bagCount) . ')</span>' : '' ?></h2>
        <button class="cart-drawer__close" type="button" id="cart-close" aria-label="Close bag">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="cart-drawer__body" id="cart-drawer-body">
        <?= view('partials/cart-drawer-items', ['items' => $cartItems]) ?>
    </div>

    <!-- Always rendered, hidden when the bag is empty. An add-to-bag on the
         PDP arrives as JSON and only toggles this footer's `hidden` — if the
         markup were conditional on a non-empty bag at page load, the first
         item added to an empty bag would leave the drawer with no actions. -->
    <div class="cart-drawer__footer" id="cart-drawer-footer"<?= $cartItems === [] ? ' hidden' : '' ?>>
        <div class="cart-drawer__subtotal">
            <span>Subtotal</span>
            <strong id="cart-drawer-subtotal"><?= money($cartSubtotal) ?></strong>
        </div>
        <div class="cart-drawer__actions">
            <button type="button" class="btn btn--outline" id="cart-continue">Continue shopping</button>
            <a class="btn btn--primary cart-drawer__cta" id="cart-view" href="/cart">View Cart</a>
        </div>
        <form method="post" action="/cart/clear" class="cart-drawer__clear"
              data-confirm="Remove all items from your bag? This cannot be undone.">
            <?= csrf_field() ?>
            <button type="submit" class="bag-line__remove">Cancel</button>
        </form>
    </div>
</aside>
<div class="cart-overlay" id="cart-overlay" hidden></div>
