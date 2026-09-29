<?php

/**
 * Quick sanity check: can PHP reach MySQL through our Database class?
 * Run from the project root:  C:\xampp\php\php.exe database/test-connection.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

// A prepared statement: the SQL travels first with ? placeholders,
// the values follow separately — SQL injection is impossible this way.
$stmt = Database::pdo()->prepare(
    'SELECT name, price, sale_price FROM products WHERE gender = ? AND sport = ?'
);
$stmt->execute(['men', 'running']);

echo "Men's running products:\n";
foreach ($stmt->fetchAll() as $row) {
    $paying = $row['sale_price'] ?? $row['price'];
    echo "  - {$row['name']} — " . money($paying) . "\n";
}

echo "Connection OK.\n";
