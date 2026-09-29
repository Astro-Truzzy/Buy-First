<?php
/**
 * Cookie consent bar. Shown until the visitor chooses.
 * Essential cookies (session, CSRF) are always on — this only gates optional ones.
 */
if (consent() !== '') {
    return;
}
$return = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<div class="cookie-banner" role="dialog" aria-labelledby="cookie-title" aria-describedby="cookie-copy">
    <div class="cookie-banner__inner">
        <div>
            <h2 id="cookie-title" class="cookie-banner__title">Cookies</h2>
            <p id="cookie-copy">
                Essential cookies keep you signed in and protect forms.
                Optional cookies remember products you viewed, so we can show them again.
                See the <a href="/cookies">Cookie Policy</a>.
            </p>
        </div>
        <form class="cookie-banner__actions" method="post" action="/consent">
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <button class="btn btn--outline-light" type="submit" name="choice" value="essential">Essential only</button>
            <button class="btn btn--inverse" type="submit" name="choice" value="all">Accept all</button>
        </form>
    </div>
</div>
