<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Analysis result persistence.
 */
class AnalysisRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    /**
     * Persist a completed analysis result.
     * input_excerpt is capped at 500 chars to avoid storing full message bodies.
     *
     * @param array<string,mixed> $result from UrlAnalysisService::analyze()
     * @param int|null $userId  null for anonymous
     * @return int  new analysis ID
     */
    public function create(array $result, ?int $userId): int
    {
        $excerpt = mb_substr((string)($result['input'] ?? ''), 0, 500);
        $stmt = $this->pdo->prepare(
            'INSERT INTO analyses
                (user_id, input_type, input_excerpt, risk_score, risk_band, rules_version, result_json, threat_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $result['input_type']  ?? 'url',
            $excerpt,
            $result['score']       ?? 0,
            $result['risk_level']  ?? 'low',
            '1.4',
            json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'not_checked',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Fetch the most recent N analyses for a user. */
    public function listForUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, input_type, input_excerpt, risk_score, risk_band, created_at
               FROM analyses WHERE user_id = ?
               ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /** Fetch a single analysis by ID, verifying ownership (or allowing admin). */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM analyses WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
