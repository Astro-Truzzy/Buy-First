<?php
/** Receives: $stats, $lowStock, $pending, $campaigns, $audit */
$paid = (int) (($stats['paid']['n'] ?? 0) + ($stats['packed']['n'] ?? 0) + ($stats['shipped']['n'] ?? 0) + ($stats['delivered']['n'] ?? 0));
$rev  = 0.0;
foreach ($stats as $row) {
    if (!in_array($row['status'], ['cancelled', 'refunded'], true)) {
        $rev += (float) $row['revenue'];
    }
}
?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Dashboard</h1>
        <p class="admin-muted">Orders, stock, campaigns, and the last things staff changed.</p>
    </div>
</div>

<div class="admin-kpis">
    <article>
        <p>Open orders</p>
        <strong><?= e((string) (($stats['pending']['n'] ?? 0) + ($stats['paid']['n'] ?? 0) + ($stats['packed']['n'] ?? 0) + ($stats['shipped']['n'] ?? 0))) ?></strong>
    </article>
    <article>
        <p>Fulfilled (paid+)</p>
        <strong><?= e((string) $paid) ?></strong>
    </article>
    <article>
        <p>Gross (ex. cancelled)</p>
        <strong><?= money($rev) ?></strong>
    </article>
    <article>
        <p>Reviews waiting</p>
        <strong><?= e((string) $pending) ?></strong>
        <?php if ($pending): ?><a href="/admin/reviews">Moderate</a><?php endif; ?>
    </article>
</div>

<div class="admin-grid">
    <section class="admin-card">
        <h2>Low stock (≤ 3)</h2>
        <?php if ($lowStock === []): ?>
            <p class="admin-muted">Nothing is critically low.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Size</th><th>SKU</th><th>Stock</th></tr></thead>
                <tbody>
                    <?php foreach ($lowStock as $row): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['product_id'])): ?>
                                    <a href="/admin/products/<?= e((string) $row['product_id']) ?>/edit"><?= e($row['name']) ?></a>
                                <?php else: ?>
                                    <?= e($row['name']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['size']) ?></td>
                            <td><code><?= e($row['sku']) ?></code></td>
                            <td><span class="admin-stock-pill<?= (int) $row['stock'] === 0 ? ' is-out' : ' is-low' ?>"><?= e((string) $row['stock']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <h2>Hero campaigns</h2>
        <ul class="admin-simple">
            <?php foreach ($campaigns as $c): ?>
                <li>
                    <span><?= e($c['title']) ?></span>
                    <form method="post" action="/admin/campaigns/<?= e((string) $c['id']) ?>/toggle">
                        <?= csrf_field() ?>
                        <input type="hidden" name="active" value="<?= (int) $c['is_active'] ? '0' : '1' ?>">
                        <?php $on = (int) $c['is_active'] === 1; ?>
                        <button type="submit"
                                class="admin-switch<?= $on ? ' is-on' : '' ?>"
                                aria-pressed="<?= $on ? 'true' : 'false' ?>"
                                aria-label="<?= $on ? 'Turn off ' . e($c['title']) : 'Turn on ' . e($c['title']) ?>">
                            <span class="admin-switch__track" aria-hidden="true"></span>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="admin-card">
    <div class="admin-row">
        <h2>Recent audit</h2>
        <a href="/admin/audit">Full log</a>
    </div>
    <?php if ($audit === []): ?>
        <p class="admin-muted">No admin actions recorded yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th></tr></thead>
            <tbody>
                <?php foreach ($audit as $row): ?>
                    <tr>
                        <td><?= e(date('j M H:i', strtotime($row['created_at']))) ?></td>
                        <td>
                            <?php
                            $who = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                            $isYou = (int) ($row['user_id'] ?? 0) === (int) (Auth::id() ?? 0) && Auth::id() !== null;
                            ?>
                            <?= e($who !== '' ? $who : '—') ?>
                            <?php if ($isYou): ?><span class="admin-you">You</span><?php endif; ?>
                        </td>
                        <td><code><?= e($row['action']) ?></code></td>
                        <td><?= e($row['details'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
