<?php /** Receives: $user, $errors, $old */ ?>
<div class="container account-page">
    <header class="account__head">
        <p class="account__kicker">Your account</p>
        <h1 class="display">Profile</h1>
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

            <div class="account-forms">
                <form method="post" action="/account/profile" class="account-form account-panel">
                    <?= csrf_field() ?>
                    <h2>Your details</h2>
                    <div class="auth__row">
                        <div class="field">
                            <label for="first_name">First name</label>
                            <input type="text" id="first_name" name="first_name" maxlength="60"
                                   value="<?= e($old['first_name']) ?>">
                        </div>
                        <div class="field">
                            <label for="last_name">Last name</label>
                            <input type="text" id="last_name" name="last_name" maxlength="60"
                                   value="<?= e($old['last_name']) ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                    </div>
                    <label class="field field--checkbox">
                        <input type="checkbox" name="newsletter" <?= !empty($old['newsletter']) ? 'checked' : '' ?>>
                        Email me about new drops, offers and member events.
                    </label>
                    <button class="btn btn--primary" type="submit">Save profile</button>
                </form>

                <form method="post" action="/account/password" class="account-form account-panel">
                    <?= csrf_field() ?>
                    <h2>Password</h2>
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required
                               autocomplete="current-password">
                    </div>
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8"
                               autocomplete="new-password">
                        <p class="field__hint">At least 8 characters.</p>
                    </div>
                    <div class="field">
                        <label for="new_password_confirm">Confirm new password</label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" required
                               minlength="8" autocomplete="new-password">
                    </div>
                    <button class="btn btn--outline" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
