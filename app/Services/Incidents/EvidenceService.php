<?php

declare(strict_types=1);

namespace App\Services\Incidents;

use App\Repositories\EvidenceRepository;
use App\Repositories\AuditRepository;
use App\Support\Logger;

/**
 * Evidence file upload and download service.
 *
 * Security rules applied (RULES.md §6):
 *  - Allowlist of extensions and MIME types
 *  - Server-side MIME detection via fileinfo
 *  - Random server-generated storage filenames
 *  - Files stored outside the public web root
 *  - Authorization required for every download
 *  - Path traversal prevented
 *  - No execution of uploaded content
 */
class EvidenceService
{
    /** Maximum file size: 10 MB */
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** Allowed extension → canonical MIME pairs */
    private const ALLOWED = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
    ];

    private EvidenceRepository $repo;
    private AuditRepository    $audit;
    private string             $storagePath;

    public function __construct()
    {
        $this->repo        = new EvidenceRepository();
        $this->audit       = new AuditRepository();
        $this->storagePath = $this->resolveStoragePath();
    }

    /**
     * Process an uploaded file for a given incident.
     *
     * @param array<string,mixed> $file  Entry from $_FILES
     * @param int $incidentId
     * @param int $uploadedBy  user ID
     * @return array{ok:bool, error:string, file_id:int|null}
     */
    public function store(array $file, int $incidentId, int $uploadedBy): array
    {
        // Basic upload error check
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed or no file selected.', 'file_id' => null];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size === 0 || $size > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'File must be between 1 byte and 10 MB.', 'file_id' => null];
        }

        // Extract and sanitise the original filename for display only — never used for storage
        $originalName = pathinfo(basename((string)($file['name'] ?? '')), PATHINFO_BASENAME);
        $originalName = preg_replace('/[^\w.\-]/', '_', $originalName) ?? 'file';
        $originalName = mb_substr($originalName, 0, 100);
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!array_key_exists($ext, self::ALLOWED)) {
            return ['ok' => false, 'error' => 'File type not permitted. Allowed: ' . implode(', ', array_keys(self::ALLOWED)) . '.', 'file_id' => null];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'error' => 'Invalid upload.', 'file_id' => null];
        }

        // Server-side MIME detection — never trust the browser value
        $finfo        = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpPath);
        $allowedMime  = self::ALLOWED[$ext];
        if ($detectedMime !== $allowedMime) {
            return ['ok' => false, 'error' => 'File content does not match the declared extension.', 'file_id' => null];
        }

        // Generate random storage key — no relationship to original name
        $storageKey = bin2hex(random_bytes(24)) . '.' . $ext;
        $destPath   = $this->storagePath . DIRECTORY_SEPARATOR . $storageKey;

        // Prevent path traversal: storage key must be a bare filename
        if (basename($destPath) !== $storageKey) {
            return ['ok' => false, 'error' => 'Storage path error.', 'file_id' => null];
        }

        if (!move_uploaded_file($tmpPath, $destPath)) {
            Logger::error('Evidence move_uploaded_file failed', ['dest' => $destPath]);
            return ['ok' => false, 'error' => 'Could not save the file. Please try again.', 'file_id' => null];
        }

        $sha256  = hash_file('sha256', $destPath) ?: null;
        $fileId  = $this->repo->create([
            'incident_id'  => $incidentId,
            'uploaded_by'  => $uploadedBy,
            'storage_key'  => $storageKey,
            'original_name'=> $originalName,
            'detected_mime'=> $detectedMime,
            'size_bytes'   => $size,
            'sha256'       => $sha256,
        ]);

        $this->audit->log('evidence_upload', $uploadedBy, 'evidence', $fileId, [
            'incident_id' => $incidentId,
            'mime'        => $detectedMime,
            'size'        => $size,
        ]);

        return ['ok' => true, 'error' => '', 'file_id' => $fileId];
    }

    /**
     * Serve a file download for an authorized user.
     * Returns false if the file record is not found or path is invalid.
     *
     * @param array<string,mixed> $evidenceRow  row from evidence_files
     */
    public function serveDownload(array $evidenceRow, int $requestingUserId): bool
    {
        $storageKey = (string)($evidenceRow['storage_key'] ?? '');

        // Validate storage key format — prevent any path traversal
        if (!preg_match('/^[0-9a-f]{48}\.[a-z]{2,4}$/', $storageKey)) {
            return false;
        }

        $path = $this->storagePath . DIRECTORY_SEPARATOR . $storageKey;
        if (!file_exists($path) || !is_file($path)) {
            return false;
        }

        $this->audit->log('evidence_download', $requestingUserId, 'evidence', (int)$evidenceRow['id']);

        $mime         = (string)($evidenceRow['detected_mime'] ?? 'application/octet-stream');
        $displayName  = (string)($evidenceRow['original_name'] ?? 'file');

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($displayName) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        return true;
    }

    private function resolveStoragePath(): string
    {
        $path = $_ENV['STORAGE_EVIDENCE_PATH'] ?? '';
        if ($path === '' || str_contains($path, 'changeme')) {
            // Fallback to storage/evidence/ relative to project root
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
        }
        if (!is_dir($path)) {
            mkdir($path, 0750, true);
        }
        return $path;
    }
}
