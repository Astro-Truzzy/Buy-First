<?php
/**
 * Journal story — full editorial article.
 * Receives: $story, $products, $more
 */
?>

<article>
    <header class="story-hero">
        <img class="story-hero__img" src="<?= e($story['hero']) ?>" alt="<?= e($story['hero_alt']) ?>">
        <div class="story-hero__content container">
            <nav class="story-crumb" aria-label="Breadcrumb">
                <a href="/journal">The Journal</a>
                <span aria-hidden="true">/</span>
                <span><?= e($story['kicker']) ?></span>
            </nav>
            <h1 class="display story-hero__title"><?= e($story['title']) ?></h1>
            <p class="story-hero__dek"><?= e($story['dek']) ?></p>
        </div>
    </header>

    <div class="story-byline">
        <div class="container story-byline__inner">
            <p class="story-byline__who">
                <strong><?= e($story['author']) ?></strong>
                <span><?= e($story['role']) ?></span>
            </p>
            <p class="journal-meta">
                <time datetime="<?= e($story['date']) ?>"><?= e(date('j F Y', strtotime($story['date']))) ?></time>
                <span><?= (int) $story['read_mins'] ?> min read</span>
            </p>
        </div>
    </div>

    <div class="container story-layout">
        <div class="story-prose">
            <?php $leadDone = false; ?>
            <?php foreach ($story['blocks'] as $block): ?>
                <?php if ($block['type'] === 'p'): ?>
                    <p<?= !$leadDone ? ' class="story-prose__lead"' : '' ?>><?= e($block['text']) ?></p>
                    <?php $leadDone = true; ?>
                <?php elseif ($block['type'] === 'h2'): ?>
                    <h2><?= e($block['text']) ?></h2>
                <?php elseif ($block['type'] === 'quote'): ?>
                    <blockquote class="story-quote">
                        <p><?= e($block['text']) ?></p>
                    </blockquote>
                <?php elseif ($block['type'] === 'callout'): ?>
                    <aside class="story-callout">
                        <p class="story-callout__label"><?= e($block['label']) ?></p>
                        <p><?= e($block['text']) ?></p>
                    </aside>
                <?php elseif ($block['type'] === 'image'): ?>
                    <figure class="story-figure">
                        <img src="<?= e($block['src']) ?>" alt="<?= e($block['alt']) ?>" loading="lazy">
                        <?php if (!empty($block['caption'])): ?>
                            <figcaption><?= e($block['caption']) ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</article>

<?php if (!empty($story['pull_quote'])): ?>
    <aside class="story-pull">
        <div class="container">
            <p class="display"><?= e($story['pull_quote']) ?></p>
        </div>
    </aside>
<?php endif; ?>

<?php if ($products): ?>
    <section class="container section">
        <div class="section-head">
            <h2 class="display">Shop The Story</h2>
            <?php if (!empty($story['cta'])): ?>
                <a class="section-head__link" href="<?= e($story['cta']['href']) ?>"><?= e($story['cta']['label']) ?></a>
            <?php endif; ?>
        </div>
        <div class="product-grid product-grid--3">
            <?php foreach ($products as $product): ?>
                <?= view('partials/product-card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($more): ?>
    <section class="container section story-more">
        <div class="section-head">
            <h2 class="display">More From The Journal</h2>
            <a class="section-head__link" href="/journal">All stories</a>
        </div>
        <div class="journal-grid journal-grid--more">
            <?php foreach ($more as $item): ?>
                <a class="journal-card journal-card--compact reveal" href="/journal/<?= e($item['slug']) ?>">
                    <div class="journal-card__media">
                        <img src="<?= e($item['hero']) ?>" alt="<?= e($item['hero_alt']) ?>" loading="lazy">
                    </div>
                    <div class="journal-card__body">
                        <p class="journal-kicker"><?= e($item['kicker']) ?></p>
                        <h3 class="display journal-card__title"><?= e($item['title']) ?></h3>
                        <p class="journal-meta">
                            <span><?= e(date('j M Y', strtotime($item['date']))) ?></span>
                            <span><?= (int) $item['read_mins'] ?> min</span>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
