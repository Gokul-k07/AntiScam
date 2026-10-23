<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Evidence file metadata persistence.
 * File contents live outside the web root; this table holds metadata only.
 */
class EvidenceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO evidence_files
                (incident_id, uploaded_by, storage_key, original_name, detected_mime, size_bytes, sha256)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['incident_id'],
            $data['uploaded_by'],
            $data['storage_key'],
            $data['original_name'],
            $data['detected_mime'],
            $data['size_bytes'],
            $data['sha256'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listForIncident(int $incidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, storage_key, original_name, detected_mime, size_bytes, sha256, created_at
               FROM evidence_files WHERE incident_id = ?
               ORDER BY created_at ASC'
        );
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, ?int $incidentId = null): ?array
    {
        if ($incidentId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM evidence_files WHERE id = ? AND incident_id = ? LIMIT 1'
            );
            $stmt->execute([$id, $incidentId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM evidence_files WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
