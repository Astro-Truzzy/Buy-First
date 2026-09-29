<?php /** Receives: $account, $errors */ ?>
<div class="admin-head">
    <div>
        <p class="admin-crumb">Payment settings</p>
        <h1 class="admin-h1">Payment settings</h1>
        <p class="admin-muted">
            These details are shown to customers who choose "Bank transfer" at checkout.
            Bank transfer only appears as an option once all three fields below are filled in.
        </p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="form-errors" role="alert">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="/admin/payment-settings" class="admin-form admin-card">
    <?= csrf_field() ?>
    <div class="field">
        <label for="bank_name">Bank name</label>
        <input id="bank_name" name="bank_name" maxlength="120" value="<?= e($account['bank_name']) ?>">
    </div>
    <div class="field">
        <label for="account_name">Account name</label>
        <input id="account_name" name="account_name" maxlength="120" value="<?= e($account['account_name']) ?>">
    </div>
    <div class="field">
        <label for="account_number">Account number</label>
        <input id="account_number" name="account_number" maxlength="40" value="<?= e($account['account_number']) ?>">
    </div>
    <button class="btn btn--primary" type="submit">Save details</button>
</form>
