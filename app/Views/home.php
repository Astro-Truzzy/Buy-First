<?php
/**
 * Home page. Receives from HomeController:
 *   $campaigns   — active hero banners from the campaigns table
 *   $trending    — featured products (quick-nav tile grid)
 *   $justIn      — newest products (horizontal rail)
 *   $bestSellers — best-seller badged products (grid)
 */
?>

<!-- ============================== Hero carousel ============================== -->
<section class="hero-carousel" data-carousel aria-label="Featured campaigns">
    <?php foreach ($campaigns as $i => $campaign): ?>
        <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>">
            <img class="hero-slide__img" src="<?= e($campaign['image_url']) ?>"
                 alt="" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
            <div class="hero-slide__content container">
                <h1 class="hero-slide__title display"><?= e($campaign['title']) ?></h1>
                <?php if ($campaign['subtitle']): ?>
                    <p class="hero-slide__sub"><?= e($campaign['subtitle']) ?></p>
                <?php endif; ?>
                <a class="btn btn--inverse" href="<?= e($campaign['cta_url']) ?>"><?= e($campaign['cta_text']) ?></a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (count($campaigns) > 1): ?>
        <div class="hero-carousel__dots" role="tablist" aria-label="Choose campaign">
            <?php foreach ($campaigns as $i => $campaign): ?>
                <button class="hero-carousel__dot<?= $i === 0 ? ' is-active' : '' ?>"
                        data-slide="<?= $i ?>" aria-label="Show campaign: <?= e($campaign['title']) ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============================== Just In rail ============================== -->
<section class="container section reveal">
    <div class="section-head">
        <h2 class="display">Just In</h2>
        <a class="section-head__link" href="/new">View all</a>
    </div>
    <div class="product-rail">
        <?php foreach ($justIn as $product): ?>
            <?= view('partials/product-card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================== Shop by sport ============================== -->
<section class="container section reveal">
    <div class="section-head">
        <h2 class="display">Shop By Sport</h2>
    </div>
    <div class="sport-tiles">
        <a class="sport-tile" href="/sport/running">
            <img src="https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80" alt="Sprinters leaving the blocks on a running track" loading="lazy">
            <span class="sport-tile__label display">Running</span>
        </a>
        <a class="sport-tile" href="/sport/training">
            <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80" alt="Athlete training with dumbbells in a gym" loading="lazy">
            <span class="sport-tile__label display">Training</span>
        </a>
        <a class="sport-tile" href="/sport/basketball">
            <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?w=900&q=80" alt="Outdoor basketball court at dusk" loading="lazy">
            <span class="sport-tile__label display">Basketball</span>
        </a>
        <a class="sport-tile" href="/sport/football">
            <img src="https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=900&q=80" alt="Footballer controlling the ball" loading="lazy">
            <span class="sport-tile__label display">Football</span>
        </a>
    </div>
</section>

<!-- ============================== Best Sellers ============================== -->
<section class="container section reveal">
    <div class="section-head">
        <h2 class="display">Best Sellers</h2>
        <a class="section-head__link" href="/men?badge=best-seller">View all</a>
    </div>
    <div class="product-grid product-grid--4">
        <?php foreach ($bestSellers as $product): ?>
            <?= view('partials/product-card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================== Trending ============================== -->
<?php if ($trending): ?>
<section class="container section reveal trending">
    <div class="trending__head">
        <h2 class="display">Trending</h2>
        <p class="trending__sub">Classic silhouettes and everyday essentials our shoppers keep coming back for.</p>
    </div>
    <div class="trending-tiles">
        <?php foreach ($trending as $product): ?>
            <a class="trending-tile" href="/product/<?= e($product['slug']) ?>">
                <span class="trending-tile__media">
                    <img src="<?= e($product['image'] ?? '') ?>"
                         alt="<?= e($product['image_alt'] ?? $product['name']) ?>" loading="lazy">
                </span>
                <span class="trending-tile__label"><?= e($product['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================== Membership CTA ============================== -->
<section class="member-cta reveal">
    <div class="container member-cta__inner">
        <h2 class="display">Become A Member.<br>Move First, Always.</h2>
        <p>Free delivery, member-only drops, early access to sale and a birthday
           reward. Joining BuyFirst Club costs nothing — moving first is priceless.</p>
        <div class="member-cta__actions">
            <?php $clubUser = Auth::user(); ?>
            <?php if (!empty($clubUser['is_member'])): ?>
                <a class="btn btn--inverse" href="/new?badge=member-exclusive">Shop member drops</a>
            <?php elseif ($clubUser): ?>
                <form method="post" action="/membership/join">
                    <?= csrf_field() ?>
                    <button class="btn btn--inverse" type="submit">Join The Club</button>
                </form>
            <?php else: ?>
                <a class="btn btn--inverse" href="/register?club=1">Join The Club</a>
            <?php endif; ?>
            <a class="btn btn--outline btn--outline-light" href="/membership#benefits">Member Benefits</a>
        </div>
    </div>
</section>

<!-- ============================== Editorial block ============================== -->
<section class="container section reveal">
    <div class="editorial">
        <a class="editorial__media" href="/journal/easy-runs-too-fast">
            <img class="editorial__img" src="https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1200&q=80"
                 alt="Runner at full stride on an open road" loading="lazy">
        </a>
        <div class="editorial__body">
            <p class="editorial__kicker">From The Journal</p>
            <h2 class="display"><a href="/journal/easy-runs-too-fast">Why Your Easy Runs Are Too Fast</a></h2>
            <p class="editorial__meta">Training · 7 min read · Amaka Okonkwo</p>
            <p>Most runners train in the grey zone — too hard to recover, too easy to improve.
               Slowing down four days a week is how you get faster on race day.</p>
            <a class="btn btn--primary" href="/journal/easy-runs-too-fast">Read The Story</a>
        </div>
    </div>
</section>
