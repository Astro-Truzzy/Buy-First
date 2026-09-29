<?php
/** Receives: $user, $address (row or null), $errors, $old */
$editing = $address !== null;
?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__crumb"><a href="/account/addresses">Addresses</a></p>
        <h1 class="display"><?= $editing ? 'Edit address' : 'Add address' ?></h1>
    </header>

    <div class="account-layout">
        <?= view('account/nav') ?>

        <div class="account-main">
            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $editing ? '/account/addresses/' . e((string) $address['id']) : '/account/addresses' ?>" class="account-form account-panel">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="label">Label</label>
                    <input type="text" id="label" name="label" maxlength="40"
                           value="<?= e($old['label'] ?? 'Home') ?>" placeholder="Home, Work…">
                </div>

                <div class="auth__row">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input type="text" id="first_name" name="first_name" maxlength="60"
                               value="<?= e($old['first_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input type="text" id="last_name" name="last_name" maxlength="60"
                               value="<?= e($old['last_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="field">
                    <label for="line1">Address line 1</label>
                    <input type="text" id="line1" name="line1" maxlength="120"
                           value="<?= e($old['line1'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="line2">Address line 2 <span class="field__optional">(optional)</span></label>
                    <input type="text" id="line2" name="line2" maxlength="120"
                           value="<?= e($old['line2'] ?? '') ?>">
                </div>
                <div class="auth__row">
                    <div class="field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" maxlength="80"
                               value="<?= e($old['city'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="postcode">Postal code</label>
                        <input type="text" id="postcode" name="postcode" maxlength="6" inputmode="numeric"
                               placeholder="100001" value="<?= e($old['postcode'] ?? '') ?>">
                    </div>
                </div>
                <div class="auth__row">
                    <div class="field">
                        <label for="country">Country</label>
                        <select id="country" name="country">
                            <?php foreach (Address::COUNTRIES as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= ($old['country'] ?? 'NG') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="phone">Phone <span class="field__optional">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" maxlength="20"
                               value="<?= e($old['phone'] ?? '') ?>">
                    </div>
                </div>

                <button class="btn btn--primary" type="submit"><?= $editing ? 'Save changes' : 'Save address' ?></button>
            </form>
        </div>
    </div>
</div>
