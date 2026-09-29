<?php

declare(strict_types=1);

/**
 * Audit log — a diary of admin actions.
 *
 * When something looks wrong ("who refunded this?"), this table answers.
 * We record the actor, the verb, the entity, and a short human detail.
 */
class Audit
{
    public static function log(string $action, string $entityType, ?int $entityId, string $details = ''): void
    {
        Database::pdo()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([Auth::id(), $action, $entityType, $entityId, $details !== '' ? $details : null]);
    }

    public static function recent(int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, u.first_name, u.last_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
