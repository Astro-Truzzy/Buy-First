<?php
/**
 * Journal index — magazine landing.
 * Receives: $featured, $stories
 */
?>

<section class="journal-hero">
    <?php if ($featured): ?>
        <img class="journal-hero__img" src="<?= e($featured['hero']) ?>" alt="<?= e($featured['hero_alt']) ?>">
        <div class="journal-hero__content container">
            <p class="journal-kicker">The Journal · <?= e($featured['kicker']) ?></p>
            <h1 class="display journal-hero__title"><?= e($featured['title']) ?></h1>
            <p class="journal-hero__dek"><?= e($featured['dek']) ?></p>
            <p class="journal-meta journal-meta--light">
                <span><?= e($featured['author']) ?></span>
                <span><?= e(date('j F Y', strtotime($featured['date']))) ?></span>
                <span><?= (int) $featured['read_mins'] ?> min read</span>
            </p>
            <a class="btn btn--inverse" href="/journal/<?= e($featured['slug']) ?>">Read The Story</a>
        </div>
    <?php endif; ?>
</section>

<section class="container section journal-index">
    <div class="section-head">
        <h2 class="display">From The Desk</h2>
        <p class="journal-index__issue">Vol. 04 — August 2026</p>
    </div>
    <p class="journal-index__lede">Training notes, product drops, and the culture around the kit — written like we mean it, not like a lookbook caption.</p>

    <div class="journal-grid">
        <?php foreach ($stories as $item): ?>
            <a class="journal-card reveal" href="/journal/<?= e($item['slug']) ?>">
                <div class="journal-card__media">
                    <img src="<?= e($item['hero']) ?>" alt="<?= e($item['hero_alt']) ?>" loading="lazy">
                </div>
                <div class="journal-card__body">
                    <p class="journal-kicker"><?= e($item['kicker']) ?></p>
                    <h3 class="display journal-card__title"><?= e($item['title']) ?></h3>
                    <p class="journal-card__dek"><?= e($item['dek']) ?></p>
                    <p class="journal-meta">
                        <span><?= e(date('j M Y', strtotime($item['date']))) ?></span>
                        <span><?= (int) $item['read_mins'] ?> min</span>
                    </p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
