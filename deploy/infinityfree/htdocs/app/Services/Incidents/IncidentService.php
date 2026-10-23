<?php

declare(strict_types=1);

namespace App\Services\Incidents;

use App\Repositories\IncidentRepository;
use App\Repositories\AuditRepository;

/**
 * Domain logic for incident creation and status management.
 * Repository calls use prepared statements; no user input is concatenated into SQL.
 */
class IncidentService
{
    private IncidentRepository $repo;
    private AuditRepository    $audit;

    public function __construct()
    {
        $this->repo  = new IncidentRepository();
        $this->audit = new AuditRepository();
    }

    /**
     * Create a new incident report.
     *
     * @param array<string,mixed> $validated  pre-validated fields from controller
     * @param int $userId
     * @return array{id:int, reference:string}
     */
    public function create(array $validated, int $userId): array
    {
        $reference = $this->generateReference();

        $id = $this->repo->create([
            'public_reference' => $reference,
            'user_id'          => $userId,
            'incident_type'    => $validated['incident_type'],
            'incident_at'      => $validated['incident_at']   ?? null,
            'description'      => $validated['description'],
            'channel'          => $validated['channel']       ?? '',
            'analysis_id'      => $validated['analysis_id']   ?? null,
        ]);

        $this->audit->log('incident_created', $userId, 'incident', $id, [
            'type'      => $validated['incident_type'],
            'reference' => $reference,
        ]);

        return ['id' => $id, 'reference' => $reference];
    }

    /**
     * Change an incident's status. Only permitted transitions are allowed.
     * Records a history entry and audit log entry.
     *
     * @return bool false if transition is invalid or incident not found
     */
    public function changeStatus(int $incidentId, int $changedBy, string $newStatus, string $reason = ''): bool
    {
        $incident = $this->repo->findById($incidentId);
        if ($incident === null) {
            return false;
        }

        $allowed = $this->allowedTransitions($incident['status']);
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        $oldStatus = $incident['status'];
        $ok        = $this->repo->updateStatus($incidentId, $newStatus);
        if (!$ok) {
            return false;
        }

        $this->repo->appendHistory($incidentId, $changedBy, $oldStatus, $newStatus, $reason);
        $this->audit->log('status_change', $changedBy, 'incident', $incidentId, [
            'old' => $oldStatus,
            'new' => $newStatus,
        ]);

        return true;
    }

    /**
     * Returns allowed next statuses for a given current status.
     * @return string[]
     */
    public function allowedTransitions(string $currentStatus): array
    {
        return match($currentStatus) {
            'submitted'    => ['under_review', 'closed'],
            'under_review' => ['info_needed', 'closed'],
            'info_needed'  => ['under_review', 'closed'],
            'closed'       => [],
            default        => [],
        };
    }

    /** Validate and sanitize incident creation fields from POST data.
     *  @return array{data:array<string,mixed>, errors:array<string,string>} */
    public function validateCreate(array $post): array
    {
        $errors = [];

        $type = trim((string)($post['incident_type'] ?? ''));
        $allowedTypes = [
            'phishing_url', 'phishing_sms', 'upi_fraud', 'impersonation',
            'digital_arrest', 'job_scam', 'investment_scam', 'other',
        ];
        if (!in_array($type, $allowedTypes, true)) {
            $errors['incident_type'] = 'Please select a valid incident type.';
        }

        $description = trim((string)($post['description'] ?? ''));
        if (mb_strlen($description) < 20) {
            $errors['description'] = 'Please describe the incident in at least 20 characters.';
        }
        if (mb_strlen($description) > 5000) {
            $errors['description'] = 'Description must not exceed 5000 characters.';
        }

        $channel = mb_substr(trim((string)($post['channel'] ?? '')), 0, 200);

        $incidentAt = null;
        $rawDate    = trim((string)($post['incident_at'] ?? ''));
        if ($rawDate !== '') {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $rawDate);
            if ($dt !== false && $dt <= new \DateTimeImmutable()) {
                $incidentAt = $dt->format('Y-m-d 00:00:00');
            } else {
                $errors['incident_at'] = 'Invalid date or future date not allowed.';
            }
        }

        $analysisId = null;
        if (!empty($post['analysis_id']) && ctype_digit((string)$post['analysis_id'])) {
            $analysisId = (int)$post['analysis_id'];
        }

        return [
            'data' => [
                'incident_type' => $type,
                'description'   => $description,
                'channel'       => $channel,
                'incident_at'   => $incidentAt,
                'analysis_id'   => $analysisId,
            ],
            'errors' => $errors,
        ];
    }

    /** Generate a unique human-readable reference: SS-YYYY-XXXXXX */
    private function generateReference(): string
    {
        $year   = date('Y');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        return "SS-{$year}-{$random}";
    }
}
