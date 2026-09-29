<?php
/**
 * Server error. Receives: $debug (bool), $message (string)
 */
?>
<section class="error-page container">
    <p class="error-page__code">500</p>
    <h1 class="display">Something broke on our side.</h1>
    <p class="error-page__lede">
        That is a server error, not a bad link. Try again in a moment,
        or head back to the store.
    </p>
    <?php if (!empty($debug) && !empty($message)): ?>
        <p class="error-page__debug"><code><?= e($message) ?></code></p>
    <?php endif; ?>
    <p class="error-page__actions">
        <a class="btn btn--primary" href="/">Back to the store</a>
    </p>
</section>
