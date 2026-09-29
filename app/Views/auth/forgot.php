<?php /** Receives: $errors */ ?>
<section class="auth container">
    <div class="auth__card">
        <h1 class="display">Forgot Password.</h1>
        <p class="auth__sub">Enter your email and we’ll send a reset link if an account exists.</p>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/forgot-password">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <button type="submit" class="btn btn--primary auth__submit">Send reset link</button>
        </form>

        <p class="auth__alt"><a href="/login">Back to sign in</a></p>
    </div>
</section>
