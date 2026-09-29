<?php
/**
 * Heart toggle. Expects: $productId, optional $return, optional $label.
 * POSTs to /wishlist/toggle. Guests are sent to login, then back here.
 */
$return = $return ?? safe_internal_path(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    '/'
);
$saved = Wishlist::has((int) $productId);
$label = $label ?? ($saved ? 'Remove from wishlist' : 'Save to wishlist');
?>
<form class="wish-form<?= !empty($showText) ? ' wish-form--row' : '' ?>" method="post" action="/wishlist/toggle">
    <?= csrf_field() ?>
    <input type="hidden" name="product_id" value="<?= e((string) $productId) ?>">
    <input type="hidden" name="return" value="<?= e($return) ?>">
    <button class="wish-btn<?= $saved ? ' is-saved' : '' ?>" type="submit" aria-label="<?= e($label) ?>" aria-pressed="<?= $saved ? 'true' : 'false' ?>">
        <svg viewBox="0 0 24 24" fill="<?= $saved ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 21C7 16.5 3 13.2 3 9.1 3 6.3 5.2 4 8 4c1.6 0 3.1.8 4 2 .9-1.2 2.4-2 4-2 2.8 0 5 2.3 5 5.1 0 4.1-4 7.4-9 11.9z"/>
        </svg>
        <?php if (!empty($showText)): ?>
            <span><?= $saved ? 'Saved' : 'Save' ?></span>
        <?php endif; ?>
    </button>
</form>
