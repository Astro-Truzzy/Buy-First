<?php
/**
 * Cart drawer body: the line list, or the empty state.
 *
 * Rendered twice — once server-side inside the header on every page load,
 * and again as an HTML fragment by CartController::finish() after an AJAX
 * quantity change or removal, so the two never drift apart.
 *
 * Expects: $items (Cart::items() output).
 */
?>
<?php if ($items === []): ?>
    <div class="bag-empty bag-empty--flush">
        <p>Your bag is empty — but it doesn't have to stay that way.</p>
        <a class="btn btn--primary" href="/new">Shop New Arrivals</a>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <?= view('partials/bag-line', ['item' => $item, 'compact' => true, 'return' => '/cart']) ?>
    <?php endforeach; ?>
<?php endif; ?>
