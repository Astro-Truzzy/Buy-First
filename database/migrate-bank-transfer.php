<?php

/**
 * Add bank-transfer support to an existing database. Safe to re-run.
 *   C:\xampp\php\php.exe database/migrate-bank-transfer.php
 */

declare(strict_types=1);

require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Database.php';
load_env(__DIR__ . '/../.env');
require __DIR__ . '/../config/config.php';

$pdo = Database::pdo();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS bank_accounts (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bank_name      VARCHAR(120) NOT NULL DEFAULT '',
        account_name   VARCHAR(120) NOT NULL DEFAULT '',
        account_number VARCHAR(40)  NOT NULL DEFAULT '',
        updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "  bank_accounts table ready\n";

$pdo->prepare('INSERT IGNORE INTO bank_accounts (id, bank_name, account_name, account_number) VALUES (1, ?, ?, ?)')
    ->execute(['', '', '']);
echo "  bank_accounts row 1 ready\n";

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT 'card' AFTER delivery_method");
    echo "  orders.payment_method added\n";
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1060) { // Duplicate column name
        echo "  orders.payment_method already present\n";
    } else {
        throw $e;
    }
}
