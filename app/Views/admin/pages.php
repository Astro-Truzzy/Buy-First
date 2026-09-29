<?php /** Receives: $pages */ ?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Pages</h1>
        <p class="admin-muted">Help, company, and legal copy on the storefront.</p>
    </div>
</div>
<div class="admin-card admin-card--flush">
<table class="admin-table">
    <thead><tr><th>Title</th><th>Slug</th><th>Updated</th></tr></thead>
    <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><a href="/admin/pages/<?= e((string) $p['id']) ?>/edit"><?= e($p['title']) ?></a></td>
                <td><a href="/<?= e($p['slug']) ?>" target="_blank" rel="noopener">/<?= e($p['slug']) ?></a></td>
                <td><?= e(date('j M Y', strtotime($p['updated_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
