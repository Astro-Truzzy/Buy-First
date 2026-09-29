<?php

/**
 * Add bank-transfer claim tracking to an existing database. Safe to re-run.
 *   C:\xampp\php\php.exe database/migrate-payment-claim.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

$pdo = Database::pdo();

try {
    $pdo->exec('ALTER TABLE orders ADD COLUMN payment_claimed_at DATETIME NULL AFTER payment_method');
    echo "  orders.payment_claimed_at added\n";
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1060) { // Duplicate column name
        echo "  orders.payment_claimed_at already present\n";
    } else {
        throw $e;
    }
}
