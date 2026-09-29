<?php
/**
 * Checkout. Receives:
 *   $items, $quote, $method, $coupon, $addresses, $errors, $old, $member, $idempotency
 */
$user  = Auth::user();
$count = 0;
foreach ($items as $item) {
    $count += (int) $item['quantity'];
}
?>

<div class="container checkout-page">
    <header class="flow-head">
        <p class="flow-head__kicker">Secure checkout</p>
        <h1 class="display page-title">Checkout</h1>
        <ol class="checkout-steps" aria-label="Checkout progress">
            <li class="is-done"><a href="/cart">Bag</a></li>
            <li class="is-current" aria-current="step">Delivery</li>
            <li>Payment</li>
        </ol>
    </header>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="checkout">
        <div class="checkout__main">

            <section class="checkout-block" id="checkout-delivery">
                <h2>Delivery</h2>
                <form method="post" action="/checkout/delivery" class="delivery-options">
                    <?= csrf_field() ?>
                    <label class="choice<?= $method === 'standard' ? ' is-selected' : '' ?>">
                        <input type="radio" name="delivery_method" value="standard"
                               <?= $method === 'standard' ? 'checked' : '' ?> data-autosubmit>
                        <span>
                            <strong>Standard</strong> · 3–7 working days
                            <em><?= ($member || $quote['subtotal'] >= Order::FREE_STANDARD_FROM) ? 'Free' : money(Order::STANDARD_FEE) ?></em>
                        </span>
                    </label>
                    <label class="choice<?= $method === 'express' ? ' is-selected' : '' ?>">
                        <input type="radio" name="delivery_method" value="express"
                               <?= $method === 'express' ? 'checked' : '' ?> data-autosubmit>
                        <span>
                            <strong>Express</strong> · 1–2 working days
                            <em><?= money(Order::EXPRESS_FEE) ?></em>
                        </span>
                    </label>
                    <noscript><button class="btn btn--outline" type="submit">Update delivery</button></noscript>
                </form>
                <?php if ($member && $method === 'standard'): ?>
                    <p class="field__hint">BuyFirst Club — standard delivery is always free.</p>
                <?php endif; ?>
            </section>

            <form method="post" action="/checkout" id="checkout-form">
                <?= csrf_field() ?>
                <input type="hidden" name="idempotency_key" value="<?= e($idempotency) ?>">
                <input type="hidden" name="delivery_method" value="<?= e($method) ?>">

                <section class="checkout-block" id="checkout-address">
                    <h2>Delivery Address</h2>

                    <?php if (!$user): ?>
                        <p class="checkout-signin">
                            Have an account? <a href="/login">Sign in</a> for saved addresses and order tracking.
                        </p>
                    <?php endif; ?>

                    <?php if ($addresses): ?>
                        <fieldset class="saved-addresses">
                            <legend class="visually-hidden">Saved addresses</legend>
                            <?php foreach ($addresses as $a): ?>
                                <label class="choice<?= (string) $a['id'] === (string) $old['address_id'] ? ' is-selected' : '' ?>">
                                    <input type="radio" name="address_id" value="<?= e((string) $a['id']) ?>"
                                           <?= (string) $a['id'] === (string) $old['address_id'] ? 'checked' : '' ?>
                                           data-toggle-address="saved">
                                    <span>
                                        <strong><?= e($a['label']) ?></strong>
                                        <?= e($a['line1']) ?>, <?= e($a['city']) ?> <?= e($a['postcode']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <label class="choice<?= $old['address_id'] === '' ? ' is-selected' : '' ?>">
                                <input type="radio" name="address_id" value=""
                                       <?= $old['address_id'] === '' ? 'checked' : '' ?>
                                       data-toggle-address="new">
                                <span><strong>Use a new address</strong></span>
                            </label>
                        </fieldset>
                    <?php endif; ?>

                    <div class="address-fields" id="address-fields"
                         <?= $addresses && $old['address_id'] !== '' ? 'hidden' : '' ?>>
                        <?php if (!$user): ?>
                            <div class="field">
                                <label for="email">Email (for your receipt)</label>
                                <input type="email" id="email" name="email"
                                       autocomplete="email" value="<?= e($old['email']) ?>">
                            </div>
                        <?php endif; ?>

                        <div class="auth__row">
                            <div class="field">
                                <label for="first_name">First name</label>
                                <input type="text" id="first_name" name="first_name" maxlength="60"
                                       autocomplete="given-name" value="<?= e($old['first_name']) ?>">
                            </div>
                            <div class="field">
                                <label for="last_name">Last name</label>
                                <input type="text" id="last_name" name="last_name" maxlength="60"
                                       autocomplete="family-name" value="<?= e($old['last_name']) ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="line1">Address line 1</label>
                            <input type="text" id="line1" name="line1" maxlength="120"
                                   autocomplete="address-line1" value="<?= e($old['line1']) ?>">
                        </div>
                        <div class="field">
                            <label for="line2">Address line 2 <span class="field__optional">(optional)</span></label>
                            <input type="text" id="line2" name="line2" maxlength="120"
                                   autocomplete="address-line2" value="<?= e($old['line2']) ?>">
                        </div>
                        <div class="auth__row">
                            <div class="field">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" maxlength="80"
                                       autocomplete="address-level2" value="<?= e($old['city']) ?>">
                            </div>
                            <div class="field">
                                <label for="postcode">Postal code</label>
                                <input type="text" id="postcode" name="postcode" maxlength="6" inputmode="numeric"
                                       autocomplete="postal-code" placeholder="100001" value="<?= e($old['postcode']) ?>">
                            </div>
                        </div>
                        <div class="auth__row">
                            <div class="field">
                                <label for="country">Country</label>
                                <select id="country" name="country" autocomplete="country">
                                    <?php foreach (Address::COUNTRIES as $code => $label): ?>
                                        <option value="<?= e($code) ?>" <?= $old['country'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="phone">Phone <span class="field__optional">(optional)</span></label>
                                <input type="tel" id="phone" name="phone" maxlength="20"
                                       autocomplete="tel" value="<?= e($old['phone']) ?>">
                            </div>
                        </div>

                        <?php if ($user): ?>
                            <label class="field field--checkbox">
                                <input type="checkbox" name="save_address" value="1">
                                Save this address to my account
                            </label>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="checkout-block checkout-block--pay" id="checkout-pay">
                    <h2>Payment</h2>

                    <?php if ($bankConfigured): ?>
                        <fieldset class="pay-method">
                            <legend class="visually-hidden">Payment method</legend>
                            <label class="choice<?= $paymentMethod === 'card' ? ' is-selected' : '' ?>">
                                <input type="radio" name="payment_method" value="card"
                                       <?= $paymentMethod === 'card' ? 'checked' : '' ?>
                                       data-toggle-payment="card">
                                <span><strong>Card</strong></span>
                            </label>
                            <label class="choice<?= $paymentMethod === 'bank_transfer' ? ' is-selected' : '' ?>">
                                <input type="radio" name="payment_method" value="bank_transfer"
                                       <?= $paymentMethod === 'bank_transfer' ? 'checked' : '' ?>
                                       data-toggle-payment="bank_transfer">
                                <span><strong>Bank transfer</strong></span>
                            </label>
                        </fieldset>
                    <?php endif; ?>

                    <div class="pay-card" id="pay-card-fields"
                         <?= $bankConfigured && $paymentMethod !== 'card' ? 'hidden' : '' ?>>
                        <p class="field__hint">Enter your card details below.</p>
                        <div class="field">
                            <label for="card_name">Name on card</label>
                            <input type="text" id="card_name" name="card_name"
                                   <?= !$bankConfigured || $paymentMethod === 'card' ? 'required' : '' ?>
                                   autocomplete="cc-name" value="<?= e(trim($old['first_name'] . ' ' . $old['last_name'])) ?>">
                        </div>
                        <div class="field">
                            <label for="card_number">Card number</label>
                            <input type="text" id="card_number" name="card_number"
                                   <?= !$bankConfigured || $paymentMethod === 'card' ? 'required' : '' ?>
                                   inputmode="numeric" autocomplete="cc-number"
                                   placeholder="ACCT-000015">
                        </div>
                        <div class="auth__row">
                            <div class="field">
                                <label for="card_exp">Expiry</label>
                                <input type="text" id="card_exp" name="card_exp"
                                       <?= !$bankConfigured || $paymentMethod === 'card' ? 'required' : '' ?>
                                       autocomplete="cc-exp" placeholder="MM / YY">
                            </div>
                            <div class="field">
                                <label for="card_cvc">CVC</label>
                                <input type="text" id="card_cvc" name="card_cvc"
                                       <?= !$bankConfigured || $paymentMethod === 'card' ? 'required' : '' ?>
                                       inputmode="numeric" autocomplete="cc-csc" placeholder="123">
                            </div>
                        </div>
                    </div>

                    <?php if ($bankConfigured): ?>
                        <div class="pay-bank" id="pay-bank-fields" <?= $paymentMethod !== 'bank_transfer' ? 'hidden' : '' ?>>
                            <p class="field__hint">
                                Transfer the order total to the account below. Your order is placed now and
                                marked as pending until we confirm your payment has arrived.
                            </p>
                            <dl class="bag-summary__rows">
                                <div><dt>Bank</dt><dd><?= e($bankAccount['bank_name']) ?></dd></div>
                                <div><dt>Account name</dt><dd><?= e($bankAccount['account_name']) ?></dd></div>
                                <div><dt>Account number</dt><dd><?= e($bankAccount['account_number']) ?></dd></div>
                            </dl>
                        </div>
                    <?php endif; ?>
                </section>

                <p class="checkout-lastlook">
                    Review your bag one last time — you can still change quantities or remove items before you pay.
                </p>

                <button class="btn btn--primary checkout__place" type="submit">
                    Place Order · <?= money($quote['total']) ?>
                </button>
            </form>
        </div>

        <aside class="bag-summary checkout-summary" aria-label="Order summary" id="checkout-bag">
            <div class="checkout-summary__head">
                <h2>Your bag</h2>
                <a href="/new">Add items</a>
            </div>
            <p class="checkout-summary__count">
                <?= e((string) $count) ?> <?= $count === 1 ? 'item' : 'items' ?>
            </p>

            <div class="checkout-summary__items">
                <?php foreach ($items as $item): ?>
                    <?= view('partials/bag-line', [
                        'item'    => $item,
                        'return'  => '/checkout',
                        'compact' => true,
                    ]) ?>
                <?php endforeach; ?>
            </div>

            <form method="post" action="/checkout/coupon" class="coupon-form">
                <?= csrf_field() ?>
                <?php if ($coupon): ?>
                    <p class="coupon-form__applied">
                        <strong><?= e($coupon['code']) ?></strong> applied
                        <button type="submit" name="remove" value="1" class="bag-line__remove">Remove</button>
                    </p>
                <?php else: ?>
                    <label class="visually-hidden" for="coupon-code">Promo code</label>
                    <input type="text" id="coupon-code" name="code" placeholder="Promo code" maxlength="30">
                    <button class="btn btn--outline" type="submit">Apply</button>
                <?php endif; ?>
            </form>

            <dl class="bag-summary__rows">
                <div><dt>Subtotal</dt><dd><?= money($quote['subtotal']) ?></dd></div>
                <?php if ($quote['discount'] > 0): ?>
                    <div><dt>Discount</dt><dd>−<?= money($quote['discount']) ?></dd></div>
                <?php endif; ?>
                <div>
                    <dt>Delivery</dt>
                    <dd><?= $quote['shipping'] === 0.0 ? 'Free' : money($quote['shipping']) ?></dd>
                </div>
                <div><dt>VAT (7.5%)</dt><dd><?= money($quote['tax']) ?></dd></div>
                <div class="bag-summary__total"><dt>Total</dt><dd><?= money($quote['total']) ?></dd></div>
            </dl>

            <button class="btn btn--primary bag-summary__cta" type="submit" form="checkout-form">
                Place Order · <?= money($quote['total']) ?>
            </button>
        </aside>
    </div>
</div>
