<?php
/** Receives: $user, $isMember, $exclusives */
$freeFrom = money(Order::FREE_STANDARD_FROM);
$stdFee   = money(Order::STANDARD_FEE);
?>
<section class="container membership">
    <header class="membership__head">
        <p class="membership__kicker">BuyFirst Club</p>
        <h1 class="display">Move First.<br>Pay Nothing To Join.</h1>
        <p class="membership__lede">
            Club is free. It unlocks free standard delivery, a longer returns window,
            and kit we only sell to members.
        </p>

        <div class="membership__cta">
            <?php if ($isMember): ?>
                <p class="membership__status">You’re a Club member. Standard delivery is free on every order.</p>
                <a class="btn btn--primary" href="/new?badge=member-exclusive">Shop member drops</a>
            <?php elseif (Auth::check()): ?>
                <form method="post" action="/membership/join">
                    <?= csrf_field() ?>
                    <button class="btn btn--primary" type="submit">Join The Club</button>
                </form>
            <?php else: ?>
                <a class="btn btn--primary" href="/register?club=1">Join The Club</a>
                <a class="btn btn--outline" href="/login?club=1">Sign in to join</a>
            <?php endif; ?>
        </div>
    </header>

    <h2 class="membership__h2" id="benefits">What you actually get</h2>
    <div class="membership__perks">
        <article>
            <h3>Free standard delivery</h3>
            <p>Every order, no minimum. Non-members pay <?= e($stdFee) ?> under <?= e($freeFrom) ?>.</p>
        </article>
        <article>
            <h3>60-day returns</h3>
            <p>Unworn kit comes back for 60 days. Everyone else has 30. No printout.</p>
        </article>
        <article>
            <h3>Member-only drops</h3>
            <p>Colourways and silhouettes that never hit the public catalog — like Baseline Lo.</p>
        </article>
        <article>
            <h3>It’s free</h3>
            <p>No fee, no subscription. Join with an account. Leave by writing to us if you ever want out.</p>
        </article>
    </div>
</section>

<?php if ($exclusives): ?>
<section class="container section">
    <div class="section-head">
        <h2 class="display">Member Exclusive</h2>
        <a class="section-head__link" href="/new?badge=member-exclusive">View all</a>
    </div>
    <div class="product-grid product-grid--4">
        <?php foreach ($exclusives as $product): ?>
            <?= view('partials/product-card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
