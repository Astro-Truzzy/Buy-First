<?php /** Receives: $reviews */ ?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Pending reviews</h1>
        <p class="admin-muted">Approved reviews go live on the product page.</p>
    </div>
</div>
<?php if ($reviews === []): ?>
    <p class="admin-muted">Queue is clear.</p>
<?php else: ?>
    <?php foreach ($reviews as $r): ?>
        <article class="admin-review">
            <header>
                <strong><?= e($r['title']) ?></strong>
                · <?= e((string) $r['rating']) ?>/5
                · <?= e($r['first_name'] . ' ' . $r['last_name']) ?>
                on <a href="/product/<?= e($r['product_slug']) ?>"><?= e($r['product_name']) ?></a>
            </header>
            <p><?= e($r['body']) ?></p>
            <div class="admin-review__actions">
                <form method="post" action="/admin/reviews/<?= e((string) $r['id']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="approved">
                    <button class="btn btn--primary" type="submit">Approve</button>
                </form>
                <form method="post" action="/admin/reviews/<?= e((string) $r['id']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="rejected">
                    <button class="btn btn--outline" type="submit">Reject</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
