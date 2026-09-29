<?php

/**
 * Swap in your own photo for the Trending section, for one or more products,
 * WITHOUT touching that product's real photos (product page, cart, wishlist,
 * etc.). This only writes to products.trending_image / trending_image_alt,
 * which Product::featured() prefers over the normal product_images row —
 * see database/fix-trending-images.php for why that separation exists.
 *
 * Use an image that actually matches the product (a hoodie photo for a
 * hoodie, a shoe photo for a shoe) — this script does not check that for you.
 *
 * HOW TO USE
 *   1. Edit the $imports array below. For each product slug, give the local
 *      image file you want — absolute path ("C:\Users\you\Pictures\shoe.jpg")
 *      or a path relative to the project root ("my-images\shoe.jpg").
 *   2. Run this file from the project root:
 *        php database/import-trending-images.php
 *   3. It copies the file into public/assets/img/products/ and sets that
 *      product's trending_image / trending_image_alt.
 *
 * To go back to a product's real photo on the Trending tile, set its value
 * to null, e.g. 'apex-court-pro' => null.
 *
 * Safe to re-run.
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

// ---------------------------------------------------------------------
// EDIT THIS: product slug => local image file (or an array with alt text),
// or null to clear a previous override and fall back to the real photo.
//
//     'velocity-runner'   => 'C:\Users\you\Desktop\velocity.jpg',
//     'foundation-hoodie' => ['path' => 'C:\Users\you\Desktop\hoodie.jpg', 'alt' => 'Foundation Hoodie, worn'],
//     'apex-court-pro'    => null,
// ---------------------------------------------------------------------
$imports = [
    // 'velocity-runner' => 'path/to/your/image.jpg',
];

// ---------------------------------------------------------------------
// Nothing below this line needs editing.
// ---------------------------------------------------------------------

$destDir = __DIR__ . '/../public/assets/img/products';
if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
    fwrite(STDERR, "Could not create {$destDir}\n");
    exit(1);
}

if ($imports === []) {
    echo "Nothing to do — add entries to \$imports in this file first.\n";
    exit(0);
}

$pdo = Database::pdo();
$findProduct = $pdo->prepare('SELECT id, name FROM products WHERE slug = ?');
$setTrending = $pdo->prepare(
    'UPDATE products SET trending_image = ?, trending_image_alt = ? WHERE id = ?'
);

foreach ($imports as $slug => $entry) {
    $findProduct->execute([$slug]);
    $product = $findProduct->fetch();

    if ($product === false) {
        fwrite(STDERR, "Skipping '{$slug}': no product with that slug.\n");
        continue;
    }

    if ($entry === null) {
        $setTrending->execute([null, null, $product['id']]);
        echo "{$slug}: cleared — back to the real product photo.\n";
        continue;
    }

    $spec    = is_array($entry) ? $entry : ['path' => $entry];
    $srcPath = (string) $spec['path'];

    // Allow paths given relative to the project root as well as absolute ones.
    $resolved = realpath($srcPath) ?: realpath(__DIR__ . '/../' . $srcPath);
    if ($resolved === false) {
        fwrite(STDERR, "Skipping '{$slug}': file not found — {$srcPath}\n");
        continue;
    }

    $ext      = pathinfo($resolved, PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $slug . '-trending.' . strtolower($ext);
    $destPath = $destDir . '/' . $filename;

    if (!copy($resolved, $destPath)) {
        fwrite(STDERR, "Skipping '{$slug}': could not copy {$resolved}\n");
        continue;
    }

    $url = '/assets/img/products/' . $filename;
    $alt = $spec['alt'] ?? $product['name'];
    $setTrending->execute([$url, $alt, $product['id']]);

    echo "{$slug}: Trending image set to {$url}\n";
}

echo "Done.\n";
