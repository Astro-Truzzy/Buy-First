<?php

declare(strict_types=1);

/**
 * Database connection manager.
 *
 * Call Database::pdo() anywhere to get the app's single shared PDO
 * connection. The first call connects; every later call reuses the same
 * connection (opening a fresh one per query would be slow and wasteful).
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $db = config()['db'];

            // DSN = Data Source Name: a string telling PDO what to connect to.
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";

            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                // Throw an exception when a query fails, instead of failing
                // silently — silent SQL failures are a classic debugging trap.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // Fetch rows as ['column' => value] arrays by default.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Use REAL prepared statements (MySQL holds SQL and data
                // separately), not PDO's client-side emulation.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}
