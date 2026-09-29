<?php
/**
 * CMS page. Receives: $page, $group, $errors, $old
 */
$slug = $page['slug'];
?>
<div class="container cms">
    <?php if ($group): ?>
        <nav class="cms__nav" aria-label="<?= e($group['name']) ?>">
            <p class="cms__nav-title"><?= e($group['name']) ?></p>
            <?php foreach ($group['links'] as $s => $label): ?>
                <a href="/<?= e($s) ?>"<?= $s === $slug ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <article class="cms__body">
        <h1 class="display"><?= e($page['title']) ?></h1>
        <p class="cms__updated">Updated <?= e(date('j F Y', strtotime($page['updated_at']))) ?></p>

        <div class="prose">
            <?= safe_html($page['content']) ?>
        </div>

        <?php if ($slug === 'contact'): ?>
            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/contact" class="account-form cms__form">
                <?= csrf_field() ?>
                <!-- Honeypot: hidden from humans, irresistible to dumb bots. -->
                <div class="hp" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <div class="field">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" maxlength="80" required
                           value="<?= e($old['name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           value="<?= e($old['email'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required><?= e($old['message'] ?? '') ?></textarea>
                </div>
                <button class="btn btn--primary" type="submit">Send message</button>
            </form>
        <?php endif; ?>
    </article>
</div>
