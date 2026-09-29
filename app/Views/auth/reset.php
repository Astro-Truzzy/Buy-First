<?php /** Receives: $token, $errors */ ?>
<section class="auth container">
    <div class="auth__card">
        <h1 class="display">New Password.</h1>
        <p class="auth__sub">Choose something you’ll remember — at least 8 characters.</p>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/reset-password/<?= e($token) ?>">
            <?= csrf_field() ?>
            <div class="field">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn--primary auth__submit">Update password</button>
        </form>
    </div>
</section>
