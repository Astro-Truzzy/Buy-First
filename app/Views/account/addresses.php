<?php /** Receives: $user, $addresses */ ?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__kicker">Your account</p>
        <h1 class="display">Addresses</h1>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <div class="account-panel__title account-panel__title--flush">
                <h2>Saved addresses</h2>
                <a class="btn btn--primary" href="/account/addresses/new">Add address</a>
            </div>

            <?php if ($addresses === []): ?>
                <p class="account__note">No saved addresses yet. Add one to check out faster next time.</p>
            <?php else: ?>
                <ul class="address-list">
                    <?php foreach ($addresses as $a): ?>
                        <li class="address-card">
                            <p class="address-card__label">
                                <?= e($a['label']) ?>
                                <?php if ((int) $a['is_default_shipping'] === 1): ?>
                                    <span class="status status--paid">Default</span>
                                <?php endif; ?>
                            </p>
                            <address>
                                <?= e($a['first_name'] . ' ' . $a['last_name']) ?><br>
                                <?= e($a['line1']) ?><br>
                                <?php if ($a['line2']): ?><?= e($a['line2']) ?><br><?php endif; ?>
                                <?= e($a['city']) ?> <?= e($a['postcode']) ?><br>
                                <?= e(country_name($a['country'])) ?>
                            </address>
                            <div class="address-card__actions">
                                <a href="/account/addresses/<?= e((string) $a['id']) ?>/edit">Edit</a>
                                <?php if ((int) $a['is_default_shipping'] !== 1): ?>
                                    <form method="post" action="/account/addresses/default">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="address_id" value="<?= e((string) $a['id']) ?>">
                                        <button type="submit">Make default</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="/account/addresses/delete">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="address_id" value="<?= e((string) $a['id']) ?>">
                                    <button type="submit">Remove</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
