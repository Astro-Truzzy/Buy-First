<?php /** Receives: $logs */ ?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Audit log</h1>
        <p class="admin-muted">Who changed what, and when.</p>
    </div>
</div>
<div class="admin-card admin-card--flush">
<table class="admin-table">
    <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Entity</th><th>Detail</th></tr></thead>
    <tbody>
        <?php foreach ($logs as $row): ?>
            <tr>
                <td><?= e(date('j M Y H:i', strtotime($row['created_at']))) ?></td>
                <td>
                    <?php
                    $who = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $isYou = (int) ($row['user_id'] ?? 0) === (int) (Auth::id() ?? 0) && Auth::id() !== null;
                    ?>
                    <?= e($who !== '' ? $who : '—') ?>
                    <?php if ($isYou): ?><span class="admin-you">You</span><?php endif; ?>
                </td>
                <td><code><?= e($row['action']) ?></code></td>
                <td><?= e($row['entity_type']) ?> #<?= e((string) ($row['entity_id'] ?? '')) ?></td>
                <td><?= e($row['details'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
