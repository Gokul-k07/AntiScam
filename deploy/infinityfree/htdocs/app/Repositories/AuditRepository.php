<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Audit log persistence — append-only.
 * Never UPDATE or DELETE from audit_logs at the application layer.
 */
class AuditRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    /**
     * Write one audit entry. metadata must be pre-redacted — no passwords, tokens, or full bodies.
     *
     * @param array<string,mixed>|null $metadata
     */
    public function log(
        string $action,
        ?int $actorUserId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $metadata = null
    ): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        // Treat ::1 (IPv6 loopback) same as 127.0.0.1
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs
                (actor_user_id, action, target_type, target_id, metadata_json, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorUserId,
            $action,
            $targetType,
            $targetId,
            $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null,
            $ip,
        ]);
    }

    /** Retrieve recent audit entries (admin view). */
    public function listRecent(int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.display_name
               FROM audit_logs a
               LEFT JOIN users u ON u.id = a.actor_user_id
               ORDER BY a.created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /** Retrieve audit entries for a specific target. */
    public function listForTarget(string $targetType, int $targetId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.display_name
               FROM audit_logs a
               LEFT JOIN users u ON u.id = a.actor_user_id
               WHERE a.target_type = ? AND a.target_id = ?
               ORDER BY a.created_at ASC'
        );
        $stmt->execute([$targetType, $targetId]);
        return $stmt->fetchAll();
    }
}
