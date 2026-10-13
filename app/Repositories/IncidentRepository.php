<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Incident persistence — all queries use prepared statements.
 */
class IncidentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    /**
     * Create a new incident. Returns new incident ID.
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO incidents
                (public_reference, user_id, incident_type, incident_at, description, channel, status, analysis_id)
             VALUES (?, ?, ?, ?, ?, ?, \'submitted\', ?)'
        );
        $stmt->execute([
            $data['public_reference'],
            $data['user_id'],
            $data['incident_type'],
            $data['incident_at'] ?? null,
            $data['description'],
            $data['channel']     ?? '',
            $data['analysis_id'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Find an incident by its internal ID. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM incidents WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Find an incident by public reference, ensuring it belongs to the given user
     *  (pass null $userId to skip ownership check — admin use only). */
    public function findByReference(string $ref, ?int $userId = null): ?array
    {
        if ($userId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM incidents WHERE public_reference = ? AND user_id = ? LIMIT 1'
            );
            $stmt->execute([$ref, $userId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM incidents WHERE public_reference = ? LIMIT 1');
            $stmt->execute([$ref]);
        }
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** List incidents for a user, newest first. */
    public function listForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, public_reference, incident_type, status, created_at
               FROM incidents WHERE user_id = ?
               ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /** List all incidents (admin). Optionally filter by status. */
    public function listAll(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT i.*, u.display_name, u.email
                   FROM incidents i
                   JOIN users u ON u.id = i.user_id
                   WHERE i.status = ?
                   ORDER BY i.created_at DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$status, $limit, $offset]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT i.*, u.display_name, u.email
                   FROM incidents i
                   JOIN users u ON u.id = i.user_id
                   ORDER BY i.created_at DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Update incident status. Returns true on success.
     * Caller must also write an incident_status_history row.
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        $allowed = ['submitted', 'under_review', 'info_needed', 'closed'];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE incidents SET status = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$newStatus, $id]);
        return $stmt->rowCount() > 0;
    }

    /** Append a status history row. */
    public function appendHistory(int $incidentId, int $changedBy, string $old, string $new, string $reason = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO incident_status_history
                (incident_id, changed_by, old_status, new_status, reason)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$incidentId, $changedBy, $old, $new, mb_substr($reason, 0, 500)]);
    }

    /** Fetch status history for an incident. */
    public function getHistory(int $incidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.*, u.display_name
               FROM incident_status_history h
               JOIN users u ON u.id = h.changed_by
               WHERE h.incident_id = ?
               ORDER BY h.created_at ASC'
        );
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll();
    }
}
