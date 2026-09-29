<?php

/**
 * One-time repair for the Trending image mix-up.
 *
 * What happened: the old import-trending-images.php replaced rows in the
 * shared `product_images` table, which is also what the product page, cart,
 * wishlist, and every other listing read from — so swapping the Trending
 * photo for a product replaced its photo everywhere. Three of the swapped-in
 * photos (Apex Court Pro, Foundation Hoodie, Momentum Leggings) were also the
 * wrong kind of product (shoe photos on a hoodie and on leggings).
 *
 * This script:
 *   1. Adds products.trending_image / trending_image_alt — a Trending-only
 *      override column that Product::featured() now reads, so a Trending
 *      photo never again overwrites the product's real gallery.
 *   2. Restores product_images for the 7 affected products back to the
 *      original seeded photos (undoing the collateral damage).
 *   3. Re-applies the Trending-only override for the four products whose
 *      swapped photo actually matched the product (Velocity Runner, Stride
 *      Pulse 2, Pace Chaser Trail, Metro Ease — all shoes).
 *   4. Leaves Apex Court Pro, Foundation Hoodie and Momentum Leggings on
 *      their real photos, since the images supplied for them didn't match
 *      the product (shoe photos for a hoodie / leggings). Use
 *      import-trending-images.php once you have suitable photos for those.
 *
 * Safe to re-run.
 *
 * Run it the same way you ran the previous script (whatever PHP command
 * worked for you before), e.g.:
 *   php database/fix-trending-images.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

$pdo = Database::pdo();

// 1. Add the Trending-only override columns, if they aren't there yet.
$existing = $pdo->query("SHOW COLUMNS FROM products LIKE 'trending_image'")->fetchAll();
if ($existing === []) {
    $pdo->exec('ALTER TABLE products
        ADD COLUMN trending_image VARCHAR(500) NULL,
        ADD COLUMN trending_image_alt VARCHAR(200) NULL');
    echo "Added products.trending_image / trending_image_alt.\n";
} else {
    echo "products.trending_image already exists — skipping.\n";
}

// 2. Restore the real product photos exactly as originally seeded.
$originalImages = [
    1 => [ // Velocity Runner — Crimson Volt
        ['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=900&q=80', 'Velocity Runner in Crimson Volt, side profile', 'Crimson Volt'],
        ['https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Runner mid-stride wearing the Velocity Runner', 'Crimson Volt'],
        ['https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Velocity Runner worn on foot, street view', 'Crimson Volt'],
        ['https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Track session in the Velocity Runner', 'Crimson Volt'],
        ['https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Velocity Runner pair, angled view', 'Crimson Volt'],
    ],
    2 => [ // Stride Pulse 2 — Glacier White
        ['https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Stride Pulse 2 in Glacier White, angled view', 'Glacier White'],
        ['https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=900&q=80', 'Athlete running on track in the Stride Pulse 2', 'Glacier White'],
        ['https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio session in the Stride Pulse 2', 'Glacier White'],
        ['https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Stride Pulse 2 worn on foot', 'Glacier White'],
        ['https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Stride Pulse 2 clean side profile', 'Glacier White'],
    ],
    3 => [ // Apex Court Pro — Ink Black
        ['https://images.unsplash.com/photo-1546519638-68e109498ffc?w=900&q=80', 'Apex Court Pro on an outdoor basketball court', 'Ink Black'],
        ['https://images.unsplash.com/photo-1519861531473-9200262188bf?w=900&q=80', 'Player rising for a dunk in the Apex Court Pro', 'Ink Black'],
        ['https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Apex Court Pro pair, side view', 'Ink Black'],
        ['https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Apex Court Pro on the hardwood', 'Ink Black'],
        ['https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Apex Court Pro top-down view', 'Ink Black'],
    ],
    6 => [ // Pace Chaser Trail — Storm Blue
        ['https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=900&q=80', 'Pace Chaser Trail in Storm Blue, side profile', 'Storm Blue'],
        ['https://images.unsplash.com/photo-1483721310020-03333e577078?w=900&q=80', 'Trail shoes on rocky terrain', 'Storm Blue'],
        ['https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=900&q=80', 'Pace Chaser Trail worn on foot', 'Storm Blue'],
        ['https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=900&q=80', 'Running the trail in the Pace Chaser', 'Storm Blue'],
        ['https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=900&q=80', 'Pace Chaser Trail pair, side view', 'Storm Blue'],
    ],
    7 => [ // Metro Ease — Classic White
        ['https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&q=80', 'Metro Ease in Classic White, side profile', 'Classic White'],
        ['https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=900&q=80', 'Metro Ease pair on concrete', 'Classic White'],
        ['https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=900&q=80', 'Metro Ease angled view', 'Classic White'],
        ['https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=900&q=80', 'Metro Ease single shoe, studio', 'Classic White'],
        ['https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=900&q=80', 'Metro Ease sole detail', 'Classic White'],
    ],
    15 => [ // Foundation Hoodie — Heather Grey
        ['https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&q=80', 'Foundation Hoodie in Heather Grey, worn', 'Heather Grey'],
        ['https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=900&q=80', 'Foundation Hoodie folded, detail view', 'Heather Grey'],
        ['https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=900&q=80', 'Foundation Hoodie styled outdoors', 'Heather Grey'],
        ['https://images.unsplash.com/photo-1552374196-1ab2a1c593e8?w=900&q=80', 'Foundation Hoodie casual look', 'Heather Grey'],
        ['https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&q=80', 'Foundation Hoodie layered', 'Heather Grey'],
    ],
    16 => [ // Momentum Leggings — Ink Black
        ['https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=900&q=80', 'Momentum Leggings during training', 'Ink Black'],
        ['https://images.unsplash.com/photo-1518611012118-696072aa579a?w=900&q=80', 'Squat session in the Momentum Leggings', 'Ink Black'],
        ['https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80', 'Studio work in the Momentum Leggings', 'Ink Black'],
        ['https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=900&q=80', 'Momentum Leggings on the track', 'Ink Black'],
        ['https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=80', 'Momentum Leggings at the gym', 'Ink Black'],
    ],
];

$deleteImages = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
$insertImage  = $pdo->prepare(
    'INSERT INTO product_images (product_id, url, alt, color, sort_order) VALUES (?, ?, ?, ?, ?)'
);

foreach ($originalImages as $productId => $images) {
    $deleteImages->execute([$productId]);
    foreach ($images as $sortOrder => [$url, $alt, $color]) {
        $insertImage->execute([$productId, $url, $alt, $color, $sortOrder]);
    }
}
echo count($originalImages) . " products restored to their real photos.\n";

// 3. Re-apply the Trending-only swap for the products where it actually fits
//    (files already sitting in public/assets/img/products/ from the last run).
$goodSwaps = [
    'velocity-runner'   => ['/assets/img/products/velocity-runner-1.png', 'Velocity Runner'],
    'stride-pulse-2'    => ['/assets/img/products/stride-pulse-2-1.avif', 'Stride Pulse 2'],
    'pace-chaser-trail' => ['/assets/img/products/pace-chaser-trail-1.png', 'Pace Chaser Trail'],
    'metro-ease'        => ['/assets/img/products/metro-ease-1.png', 'Metro Ease'],
];

$setTrending = $pdo->prepare(
    'UPDATE products SET trending_image = ?, trending_image_alt = ? WHERE slug = ?'
);
foreach ($goodSwaps as $slug => [$url, $alt]) {
    $setTrending->execute([$url, $alt, $slug]);
}
echo count($goodSwaps) . " products kept their Trending swap (shoe photo on a shoe product).\n";

// 4. Clear the mismatched swaps — these fall back to the real photo above
//    until you supply an actual hoodie / leggings / basketball-shoe photo.
$clearTrending = $pdo->prepare(
    'UPDATE products SET trending_image = NULL, trending_image_alt = NULL WHERE slug = ?'
);
foreach (['apex-court-pro', 'foundation-hoodie', 'momentum-leggings'] as $slug) {
    $clearTrending->execute([$slug]);
}
echo "3 mismatched swaps cleared (apex-court-pro, foundation-hoodie, momentum-leggings) — back to their real photos.\n";

echo "Done.\n";
