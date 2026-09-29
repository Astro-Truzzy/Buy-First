<?php

/**
 * One-shot: convert a live BuyFirst DB from GBP/UK to NGN/Nigeria.
 * Safe to re-run — money is only multiplied if product prices still look like pounds.
 *
 *   C:\xampp\php\php.exe database/localize-nigeria.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

$pdo = Database::pdo();

$max = (float) $pdo->query('SELECT MAX(price) FROM products')->fetchColumn();
if ($max > 0 && $max < 1000) {
    $pdo->exec('UPDATE products SET price = price * 1000, sale_price = sale_price * 1000');
    $pdo->exec("UPDATE coupons SET
        value = CASE WHEN type = 'fixed' THEN value * 1000 ELSE value END,
        min_spend = min_spend * 1000");
    $pdo->exec('UPDATE orders SET
        subtotal = subtotal * 1000, discount = discount * 1000,
        shipping_cost = shipping_cost * 1000, tax = tax * 1000, total = total * 1000');
    $pdo->exec('UPDATE order_items SET unit_price = unit_price * 1000, line_total = line_total * 1000');
    $pdo->exec('UPDATE payments SET amount = amount * 1000, currency = \'NGN\'');
    echo "Prices multiplied to naira.\n";
} else {
    $pdo->exec("UPDATE payments SET currency = 'NGN' WHERE currency = 'GBP'");
    echo "Prices already look like naira — skipped multiply.\n";
}

$sizes = [
    'UK 13' => 'EU 32', 'UK 12' => 'EU 46', 'UK 11' => 'EU 45', 'UK 10' => 'EU 44',
    'UK 9' => 'EU 43', 'UK 8' => 'EU 42', 'UK 7' => 'EU 41', 'UK 6' => 'EU 40',
    'UK 5' => 'EU 38', 'UK 4' => 'EU 37', 'UK 3' => 'EU 35', 'UK 2' => 'EU 34', 'UK 1' => 'EU 33',
];
foreach ($sizes as $from => $to) {
    $stmt = $pdo->prepare('UPDATE product_variants SET size = ? WHERE size = ?');
    $stmt->execute([$to, $from]);
    $stmt = $pdo->prepare('UPDATE order_items SET size = ? WHERE size = ?');
    $stmt->execute([$to, $from]);
    $stmt = $pdo->prepare('UPDATE products SET details = REPLACE(details, ?, ?), description = REPLACE(description, ?, ?)');
    $stmt->execute([$from, $to, $from, $to]);
}

$pdo->exec("UPDATE addresses SET
    line1 = '12 Allen Avenue', line2 = NULL, city = 'Ikeja', postcode = '100271',
    country = 'NG', phone = '+234 803 555 0123'
    WHERE user_id = 2 AND label = 'Home'");
$pdo->exec("UPDATE addresses SET
    line1 = '15 Adeola Odeku Street', line2 = 'Victoria Island', city = 'Lagos',
    postcode = '101241', country = 'NG'
    WHERE user_id = 2 AND label = 'Work'");
$pdo->exec("UPDATE addresses SET
    line1 = '8 Admiralty Way', line2 = 'Lekki Phase 1', city = 'Lagos',
    postcode = '105102', country = 'NG', phone = '+234 809 555 0456'
    WHERE user_id = 3");

$pdo->exec("UPDATE orders SET
    ship_line1 = '12 Allen Avenue', ship_city = 'Ikeja', ship_postcode = '100271',
    ship_country = 'NG', ship_phone = '+234 803 555 0123'
    WHERE user_id = 2 AND ship_city IN ('London', 'Ikeja') AND order_number IN ('BF-260712-K3M8','BF-260801-Q7T2')");
$pdo->exec("UPDATE orders SET
    ship_line1 = '15 Adeola Odeku Street', ship_city = 'Lagos', ship_postcode = '101241',
    ship_country = 'NG'
    WHERE order_number = 'BF-260812-A1C9'");
$pdo->exec("UPDATE orders SET
    ship_first_name = 'Chinedu', ship_last_name = 'Okoro',
    ship_line1 = '22 Aminu Kano Crescent', ship_city = 'Abuja', ship_postcode = '900288',
    ship_country = 'NG'
    WHERE order_number = 'BF-260813-Z4R7'");

$pdo->exec("UPDATE campaigns SET
    title = 'Rain Won\\'t Wait.',
    subtitle = 'The Aero Shield Jacket packs into its own pocket. Harmattan or downpour — no excuses left.'
    WHERE title LIKE 'Winter%'");

$pdo->exec("UPDATE reviews SET body = REPLACE(body, 'I am a UK 9', 'I am an EU 43')");
$pdo->exec("UPDATE reviews SET body = REPLACE(body, 'the 9 fits', 'the 43 fits')");
$pdo->exec("UPDATE reviews SET body = REPLACE(body, 'I am a EU 43', 'I am an EU 43')");

try {
    $pdo->exec("ALTER TABLE addresses ALTER country SET DEFAULT 'NG'");
    $pdo->exec("ALTER TABLE orders ALTER ship_country SET DEFAULT 'NG'");
    $pdo->exec("ALTER TABLE payments ALTER currency SET DEFAULT 'NGN'");
} catch (PDOException $e) {
    echo "Default ALTER skipped: {$e->getMessage()}\n";
}

echo "Live database localised to Nigeria / naira.\n";
