<?php

/**
 * Upsert CMS pages. Safe to re-run.
 *   E:\xampp\php\php.exe database/seed-pages.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

$pages = [

'help' => ['Help Center', <<<'HTML'
<p>Buy something. Wear it. Move. If anything in that chain snags, this is the place.</p>
<h2>Orders &amp; delivery</h2>
<ul>
<li><a href="/shipping">Shipping &amp; Delivery</a> — times, costs, Club free delivery.</li>
<li><a href="/returns">Returns &amp; Exchanges</a> — 30 days, 60 for Club members.</li>
<li><a href="/account/orders">Your orders</a> — tracking and receipts (sign in).</li>
</ul>
<h2>Product help</h2>
<ul>
<li><a href="/size-guides">Size Guides</a> — footwear and apparel.</li>
<li>Care: machine wash cold, inside out, hang dry. Don’t iron prints or foam.</li>
</ul>
<h2>Account</h2>
<ul>
<li><a href="/forgot-password">Reset your password</a></li>
<li><a href="/account/addresses">Saved addresses</a></li>
<li><a href="/privacy">How we use your data</a></li>
</ul>
<p>Still stuck? <a href="/contact">Write to us</a> — we answer within two working days.</p>
HTML],

'shipping' => ['Shipping & Delivery', <<<'HTML'
<p>We ship nationwide across Nigeria from our Lagos warehouse — Lagos, Abuja, Port Harcourt, Kano, and everywhere the road or flight goes.</p>
<h2>Options</h2>
<ul>
<li><strong>Standard</strong> — 3–7 working days, ₦4,990. Free on orders over ₦75,000 and for every BuyFirst Club member.</li>
<li><strong>Express</strong> — 1–2 working days in Lagos and Abuja, 2–3 working days elsewhere, ₦9,990.</li>
</ul>
<p>Promo code <strong>FREESHIP</strong> zeros delivery on orders over ₦50,000, including Express.</p>
<h2>When we dispatch</h2>
<p>Paid orders placed before 2pm on a working day leave the same day. Weekends and public holidays roll to the next working day. You’ll get a tracking link by email when the carrier scans the parcel.</p>
<h2>Where we don’t ship</h2>
<p>We don’t ship outside Nigeria yet. Enter a 6-digit Nigerian postal code at checkout — if it looks unusual, the form will say so.</p>
HTML],

'returns' => ['Returns & Exchanges', <<<'HTML'
<p>Unworn items, original packaging, 30 days from delivery. Club members get 60 days. No printout required.</p>
<h2>How</h2>
<ol>
<li>Sign in and open the order, or email <a href="mailto:returns@buyfirst.test">returns@buyfirst.test</a> with your order number.</li>
<li>We’ll send a prepaid waybill. Drop the parcel at any GIGL, DHL, or NIPOST desk.</li>
<li>Refunds go back to the original payment method within 5 working days of the warehouse scan. Club credit is instant.</li>
</ol>
<h2>What we can’t take back</h2>
<ul>
<li>Worn, washed, or customised pieces.</li>
<li>Gift cards and digital member passes.</li>
<li>Items marked Final Sale on the product page.</li>
</ul>
<p>Exchanges: return the original and place a new order for the size you want. That’s faster than holding stock against your name, and you won’t miss a drop.</p>
HTML],

'size-guides' => ['Size Guides', <<<'HTML'
<p>Measure in the evening — feet swell during the day. Between sizes? Go up for running, down for lifestyle.</p>
<h2>Footwear (EU)</h2>
<table>
<thead><tr><th>EU</th><th>UK</th><th>US</th><th>Foot length (cm)</th></tr></thead>
<tbody>
<tr><td>40</td><td>6</td><td>7</td><td>24.5</td></tr>
<tr><td>41</td><td>7</td><td>8</td><td>25.5</td></tr>
<tr><td>42</td><td>8</td><td>9</td><td>26.5</td></tr>
<tr><td>43</td><td>9</td><td>10</td><td>27.5</td></tr>
<tr><td>44</td><td>10</td><td>11</td><td>28.5</td></tr>
<tr><td>45</td><td>11</td><td>12</td><td>29.5</td></tr>
</tbody>
</table>
<h2>Apparel</h2>
<table>
<thead><tr><th>Size</th><th>Chest (cm)</th><th>Waist (cm)</th><th>Hip (cm)</th></tr></thead>
<tbody>
<tr><td>XS</td><td>84–89</td><td>68–73</td><td>84–89</td></tr>
<tr><td>S</td><td>90–97</td><td>74–81</td><td>90–97</td></tr>
<tr><td>M</td><td>98–105</td><td>82–89</td><td>98–105</td></tr>
<tr><td>L</td><td>106–113</td><td>90–98</td><td>106–113</td></tr>
<tr><td>XL</td><td>114–121</td><td>99–108</td><td>114–121</td></tr>
</tbody>
</table>
<p>Kids’ sizes follow age labels on the product (e.g. 8–9yr). When in doubt, size up — they grow through it.</p>
HTML],

'contact' => ['Contact Us', <<<'HTML'
<p>Orders, returns, product questions, press — use the form. We read everything. We reply within two working days, usually sooner.</p>
<p>For an open order, include the order number (it looks like <strong>BF-260712-K3M8</strong>). That saves a round of “which Jordan?”.</p>
HTML],

'about' => ['About BuyFirst', <<<'HTML'
<p>BuyFirst exists for people who don’t wait for the whistle. We design running, training, court and lifestyle gear in Lagos and cut it to move — not to sit on a rail.</p>
<h2>The name</h2>
<p>First mile. First set. First to the rim. The kit should already be on you.</p>
<h2>How we work</h2>
<ul>
<li>We sell our own products. No marketplace noise, no mystery sellers.</li>
<li>Prices are in naira (₦). VAT at 7.5% is shown at checkout — no surprise fees at the door.</li>
<li>Club members get free standard delivery, 60-day returns, and first look at drops.</li>
</ul>
<p>We’re a small team. That’s why a human still reads the <a href="/contact">contact form</a>.</p>
HTML],

'careers' => ['Careers', <<<'HTML'
<p>We hire people who still get a kick out of a well-cut hoodie and a clean SQL query. Currently open:</p>
<ul>
<li>Product designer (footwear) — Lagos, hybrid.</li>
<li>Backend engineer (PHP) — remote Nigeria.</li>
<li>Warehouse lead — Ikeja.</li>
</ul>
<p>Send a short note and a link to work you like to <a href="mailto:jobs@buyfirst.test">jobs@buyfirst.test</a>. No agencies, no “culture decks”. Tell us what you’d change on the site by Friday.</p>
HTML],

'sustainability' => ['Sustainability', <<<'HTML'
<p>Gear should last more than a season. That’s the most useful climate decision we can make as a clothing brand.</p>
<h2>What we’re doing</h2>
<ul>
<li>Recycled polyester in the majority of apparel uppers and linings.</li>
<li>Repair guides instead of “buy another one” for common failures (laces, heel cups).</li>
<li>Warehouse powered on a renewable tariff. Last-mile partners chosen for EV fleets in city centres.</li>
</ul>
<p>We don’t sell carbon offsets as a product. We publish an annual materials breakdown in the Journal when the numbers are real, not when they look good.</p>
HTML],

'journal' => ['Journal', <<<'HTML'
<h2>Move First.</h2>
<p>The Velocity Runner came back in Crimson Volt because the original colourway still gets stopped on towpaths. This drop keeps the full-length foam and swaps the upper to a cooler engineered mesh for summer miles.</p>
<p>Sizing is true to the last version. If you own an EU 43 in the first Velocity, buy an EU 43. If you’re coming from a max-cushion trainer, stay with your usual EU size — the toe box is already generous.</p>
<p><a href="/product/velocity-runner">Shop the Velocity Runner</a> · <a href="/sport/running">All running</a></p>
<h2>On the court</h2>
<p>Apex Court Pro sits low. That’s the point. If you want a cushioned lifestyle shoe for the commute, look at <a href="/sport/lifestyle">Lifestyle</a> instead of forcing a basketball shoe into that job.</p>
HTML],

'stores' => ['Store Locator', <<<'HTML'
<p>Two doors. Both do fit advice, Club sign-up, and returns without a printer.</p>
<h2>Lagos — Ikeja</h2>
<p>12 Allen Avenue, Ikeja 100271. Open 10:00–19:00 Monday–Saturday, 12:00–17:00 Sunday. Step-free from the street. Book a 20-minute fit session on the contact form.</p>
<h2>Abuja — Wuse II</h2>
<p>22 Aminu Kano Crescent, Wuse II 900288. Open 10:00–18:00 daily. Click-and-collect from this shop lands next working day if you order before 2pm.</p>
<p>Everything else is the warehouse in Ikeja — no public visits, no samples piled on a folding table. That’s why the site has to be good.</p>
HTML],

'sport' => ['Shop by Sport', <<<'HTML'
<p>Pick a lane. Every product on BuyFirst is tagged with one of these, so the filters stay honest.</p>
<ul>
<li><a href="/sport/running">Running</a> — daily trainers, jackets, tights.</li>
<li><a href="/sport/training">Training &amp; Gym</a> — stable shoes, bras, hoodies.</li>
<li><a href="/sport/basketball">Basketball</a> — court shoes built to stay low.</li>
<li><a href="/sport/football">Football</a> — boots and training kit.</li>
<li><a href="/sport/lifestyle">Lifestyle</a> — the pair you actually live in.</li>
</ul>
HTML],

'privacy' => ['Privacy Policy', <<<'HTML'
<p>BuyFirst Ltd is the data controller for this shop. We collect as little as we can, keep it only as long as we need it, and we never sell it.</p>
<h2>What we hold</h2>
<ul>
<li><strong>Account</strong> — name, email, password hash (not the password), newsletter preference.</li>
<li><strong>Orders</strong> — items, amounts, delivery address. Card numbers never touch our database; we store only a payment reference from the processor.</li>
<li><strong>Session</strong> — a random cookie so your bag and login survive a page refresh. HttpOnly, SameSite=Lax.</li>
</ul>
<h2>Why</h2>
<p>To fulfil orders, run your account, send newsletters you opted into, and keep the shop secure (failed-login lockouts, CSRF tokens). Legal basis: contract, legitimate interest, or consent — depending on the row.</p>
<h2>Your rights</h2>
<p>Access, correction, erasure, portability, objection. Email <a href="mailto:privacy@buyfirst.test">privacy@buyfirst.test</a>. You can also complain to the NDPC. We will not ignore that email.</p>
<p>See also the <a href="/cookies">Cookie Policy</a>.</p>
HTML],

'terms' => ['Terms & Conditions', <<<'HTML'
<p>By placing an order you enter a contract with BuyFirst Ltd. Nigerian law, Lagos courts. The important bits in plain language:</p>
<ol>
<li>Prices are in naira (₦). The total you confirm at checkout — including delivery and VAT at 7.5% — is the price. We recompute it on the server; an edited hidden field does nothing.</li>
<li>We may refuse or cancel an order if stock disappears between bag and payment, or if we reasonably suspect fraud.</li>
<li>Risk in the goods passes when the carrier scans them as delivered. Title passes when we receive payment.</li>
<li>Your statutory cancellation rights sit on top of our <a href="/returns">returns policy</a>, which is more generous.</li>
<li>We are not liable for lost training PBs, rain, or a colourway selling out while you thought about it.</li>
</ol>
<p>Club membership is free, can be withdrawn if abused, and does not change these terms except where we say so (delivery, returns window).</p>
HTML],

'cookies' => ['Cookie Policy', <<<'HTML'
<p>We use a small number of cookies. None of them are for advertising networks.</p>
<table>
<thead><tr><th>Cookie</th><th>Why</th><th>Lasts</th></tr></thead>
<tbody>
<tr><td>PHPSESSID</td><td>Session: bag, login, CSRF token, flash messages.</td><td>Until you close the browser (or the server expires it).</td></tr>
</tbody>
</table>
<p>That session cookie is HttpOnly (JavaScript cannot read it) and SameSite=Lax (it is not sent on cross-site POSTs). We do not set a tracking pixel, a Facebook pixel, or a “personalisation” cookie that follows you around the web.</p>
<p>You can delete cookies in your browser. You’ll look signed out and your guest bag will empty. That’s the trade.</p>
HTML],

'accessibility' => ['Accessibility', <<<'HTML'
<p>We aim for WCAG 2.2 AA. The site should be usable with a keyboard, a screen reader, and <code>prefers-reduced-motion</code> turned on.</p>
<h2>What you’ll find</h2>
<ul>
<li>Skip link to main content (Tab once on any page).</li>
<li>Visible focus rings. Buttons that are real buttons. Links that are real links.</li>
<li>Alt text on product images. Form errors attached to the fields they belong to.</li>
<li>Motion that stops if your operating system asks it to.</li>
</ul>
<p>If something is in the way, tell us via <a href="/contact">Contact</a> with the page URL and the tool you use (VoiceOver, NVDA, keyboard only). We’ll treat that as a bug, not a suggestion.</p>
HTML],

'imprint' => ['Company Information', <<<'HTML'
<p><strong>BuyFirst Ltd</strong><br>Registered in Nigeria.<br>RC number 14100001.<br>Registered office: 12 Allen Avenue, Ikeja, Lagos 100271.</p>
<p>VAT / TIN: 00000000-0001.</p>
<p>Contact: <a href="mailto:hello@buyfirst.test">hello@buyfirst.test</a> · <a href="/contact">Contact form</a></p>
<p>BuyFirst accepts payment by card or bank transfer. Card payments are processed at checkout; bank transfer details are provided after you place your order.</p>
HTML],

];

$pdo = Database::pdo();
$upsert = $pdo->prepare(
    'INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)'
);

foreach ($pages as $slug => [$title, $html]) {
    $upsert->execute([$slug, $title, trim($html)]);
    echo "  {$slug}\n";
}

echo count($pages) . " pages upserted.\n";
