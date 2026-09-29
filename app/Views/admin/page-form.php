<?php /** Receives: $page, $errors */ ?>
<div class="admin-head">
    <div>
        <p class="admin-crumb"><a href="/admin/pages">Pages</a> / <?= e($page['slug']) ?></p>
        <h1 class="admin-h1"><?= e($page['title']) ?></h1>
        <p class="admin-muted">Only a small tag list reaches the storefront (<code>p, h2, h3, ul, a…</code>). Scripts are stripped.</p>
    </div>
    <a class="btn btn--outline" href="/<?= e($page['slug']) ?>" target="_blank" rel="noopener">View</a>
</div>

<?php if ($errors): ?>
    <div class="form-errors" role="alert">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="/admin/pages/<?= e((string) $page['id']) ?>" class="admin-form admin-card">
    <?= csrf_field() ?>
    <div class="field">
        <label for="title">Title</label>
        <input id="title" name="title" value="<?= e($page['title']) ?>">
    </div>
    <div class="field">
        <label for="content">HTML content</label>
        <textarea id="content" name="content" rows="18"><?= e($page['content']) ?></textarea>
    </div>
    <button class="btn btn--primary" type="submit">Save page</button>
</form>
