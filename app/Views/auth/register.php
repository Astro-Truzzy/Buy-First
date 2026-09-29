<?php /** Receives: $errors (array of strings), $old (previous input) */ ?>
<section class="auth container">
    <div class="auth__card">
        <h1 class="display"><?= !empty($club) ? 'Join The Club.' : 'Join BuyFirst.' ?></h1>
        <p class="auth__sub"><?= !empty($club)
            ? 'Create a free account and you’re in — free standard delivery starts on the next order.'
            : 'One account for orders, favourites and member drops.' ?></p>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/register">
            <?= csrf_field() ?>
            <?php if (!empty($club)): ?>
                <input type="hidden" name="club" value="1">
            <?php endif; ?>

            <div class="auth__row">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" required maxlength="60"
                           autocomplete="given-name" value="<?= e($old['first_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" required maxlength="60"
                           autocomplete="family-name" value="<?= e($old['last_name'] ?? '') ?>">
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= e($old['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       autocomplete="new-password" aria-describedby="password-hint">
                <p class="field__hint" id="password-hint">At least 8 characters.</p>
            </div>

            <div class="field">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" required
                       minlength="8" autocomplete="new-password">
            </div>

            <label class="field field--checkbox">
                <input type="checkbox" name="newsletter" <?= !empty($old['newsletter']) ? 'checked' : '' ?>>
                Email me about new drops, offers and member events.
            </label>

            <button type="submit" class="btn btn--primary auth__submit"><?= !empty($club) ? 'Join BuyFirst Club' : 'Create Account' ?></button>
        </form>

        <p class="auth__alt">Already have an account? <a href="/login<?= !empty($club) ? '?club=1' : '' ?>">Sign in</a></p>
    </div>
</section>
