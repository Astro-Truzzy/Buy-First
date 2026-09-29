<?php $base = rtrim(config()['app']['url'], '/'); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#0a0a0a">
    <p style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#e01e2f;font-weight:700">BuyFirst</p>
    <h1 style="font-size:28px;text-transform:uppercase;margin:0 0 12px">Reset your password.</h1>
    <p>Someone asked to reset the password on this BuyFirst account. If it was you, use the button below. The link expires in one hour.</p>
    <p>
        <a href="<?= e($url) ?>"
           style="display:inline-block;background:#0a0a0a;color:#fff;padding:12px 20px;text-decoration:none">Choose a new password</a>
    </p>
    <p style="color:#555;font-size:13px;word-break:break-all"><?= e($url) ?></p>
    <p style="color:#8a8a84;font-size:13px">If you did not ask for this, you can ignore the email. Your password stays the same.</p>
</div>
