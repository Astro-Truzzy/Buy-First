<?php
/** Receives: $errors (array of strings), $old (previous input) */
$carouselBadgeLabels = [
    'just-in'          => 'Just In',
    'best-seller'      => 'Best Seller',
    'member-exclusive' => 'Member Exclusive',
    'limited-drop'     => 'Limited Drop',
];

// Each slide pairs a lifestyle photo (not an isolated product cutout) with the
// two cards that slide in over it: the product name (left) and a smaller
// price/badge card (right). Mixes shoes with apparel so the panel isn't just
// sneakers on repeat.
$loginCarousel = [
    [
        'src'      => '/assets/img/login-carousel/velocity-runner.jpg',
        'alt'      => 'Runner splashing through a puddle in white Velocity Runner sneakers',
        'name'     => 'Velocity Runner',
        'subtitle' => "Men's Running",
        'price'    => 129990.00,
        'badge'    => 'best-seller',
    ],
    [
        'src'      => '/assets/img/login-carousel/apex-court-pro.jpg',
        'alt'      => 'Apex Court Pro sneakers in colour-blocked panels on a studio plinth',
        'name'     => 'Apex Court Pro',
        'subtitle' => "Men's Basketball",
        'price'    => 139990.00,
        'badge'    => null,
    ],
    [
        'src'      => '/assets/img/login-carousel/foundation-hoodie.jpg',
        'alt'      => 'Model wearing the grey Foundation Hoodie under a denim jacket',
        'name'     => 'Foundation Hoodie',
        'subtitle' => 'Lifestyle',
        'price'    => 64990.00,
        'badge'    => 'best-seller',
    ],
    [
        'src'      => '/assets/img/login-carousel/momentum-leggings.jpg',
        'alt'      => 'Silhouette of a woman stretching in Momentum Leggings at sunset',
        'name'     => 'Momentum Leggings',
        'subtitle' => "Women's Training",
        'price'    => 59990.00,
        'badge'    => 'best-seller',
    ],
    [
        'src'      => '/assets/img/login-carousel/pace-chaser-trail.jpg',
        'alt'      => 'Runners silhouetted against the sunrise wearing Pace Chaser Trail shoes',
        'name'     => 'Pace Chaser Trail',
        'subtitle' => "Men's Running",
        'price'    => 149990.00,
        'badge'    => 'limited-drop',
    ],
];
?>
<section class="login-split">
    <div class="login-split__visual">
        <span class="login-split__mark">BUYFIRST<span class="logo__mark">.</span></span>

        <div class="carousel" data-carousel aria-roledescription="carousel" aria-label="Featured products">
            <div class="carousel__viewport">
                <?php foreach ($loginCarousel as $i => $slide): ?>
                    <figure class="carousel__slide<?= $i === 0 ? ' is-active' : '' ?>">
                        <img src="<?= e($slide['src']) ?>" alt="<?= e($slide['alt']) ?>"
                             loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">

                        <span class="carousel__card carousel__card--name">
                            <span class="carousel__card-eyebrow">BuyFirst</span>
                            <span class="carousel__card-title"><?= e($slide['name']) ?></span>
                            <span class="carousel__card-sub"><?= e($slide['subtitle']) ?></span>
                        </span>

                        <span class="carousel__card carousel__card--info">
                            <?php if ($slide['badge']): ?>
                                <span class="carousel__card-badge"><?= e($carouselBadgeLabels[$slide['badge']] ?? $slide['badge']) ?></span>
                            <?php endif; ?>
                            <span class="carousel__card-price"><?= money($slide['price']) ?></span>
                        </span>
                    </figure>
                <?php endforeach; ?>
            </div>
            <div class="carousel__nav">
                <button type="button" class="carousel__arrow" data-carousel-prev aria-label="Previous product">&lsaquo;</button>
                <div class="carousel__dots" role="tablist" aria-label="Choose product image">
                    <?php foreach ($loginCarousel as $i => $slide): ?>
                        <button type="button" class="carousel__dot<?= $i === 0 ? ' is-active' : '' ?>" role="tab"
                                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Show product <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="carousel__arrow" data-carousel-next aria-label="Next product">&rsaquo;</button>
            </div>
        </div>
    </div>

    <div class="login-split__form">
        <div class="auth__card">
            <p class="auth__eyebrow">Sign In</p>
            <h1 class="display">Welcome Back.</h1>
            <p class="auth__sub"><?= !empty($club)
                ? 'Sign in and we’ll add BuyFirst Club to your account — it’s free.'
                : 'Sign in to your BuyFirst account.' ?></p>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/login">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           value="<?= e($old['email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="field__control">
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" data-password-toggle="password"
                                aria-pressed="false" aria-label="Show password">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary auth__submit">Sign In</button>
            </form>

            <p class="auth__alt"><a href="/forgot-password">Forgot your password?</a></p>
            <p class="auth__alt">New to BuyFirst? <a href="/register<?= !empty($club) ? '?club=1' : '' ?>">Create an account</a></p>
        </div>
    </div>
</section>
