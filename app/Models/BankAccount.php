<?php

declare(strict_types=1);

/**
 * BankAccount — the single admin-editable set of bank details shown to
 * customers who choose "Bank transfer" at checkout instead of card.
 */
class BankAccount
{
    public static function get(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM bank_accounts WHERE id = 1');

        return $stmt->fetch() ?: [
            'bank_name'      => '',
            'account_name'   => '',
            'account_number' => '',
        ];
    }

    public static function update(string $bankName, string $accountName, string $accountNumber): void
    {
        Database::pdo()->prepare(
            'UPDATE bank_accounts SET bank_name = ?, account_name = ?, account_number = ? WHERE id = 1'
        )->execute([$bankName, $accountName, $accountNumber]);
    }

    /** Bank transfer is only offered at checkout once all three fields are set. */
    public static function isConfigured(): bool
    {
        $account = self::get();

        return $account['bank_name'] !== '' && $account['account_name'] !== '' && $account['account_number'] !== '';
    }
}
