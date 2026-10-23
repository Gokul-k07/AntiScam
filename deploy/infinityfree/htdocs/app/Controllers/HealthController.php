<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\Logger;

/**
 * Diagnostic health endpoint.
 *
 * Returns JSON: {"status":"ok"|"degraded","checks":{...}}
 * HTTP 200 when all checks pass; HTTP 503 when any check fails.
 *
 * NEVER exposes: credentials, file paths, environment variable values,
 * database usernames/passwords, or internal stack traces.
 */
class HealthController
{
    public function index(): void
    {
        $checks = [];
        $allOk  = true;

        // --- Check 1: PHP version ---
        $phpVersion = PHP_VERSION;
        $phpOk      = version_compare($phpVersion, '8.0.0', '>=');
        $checks['php'] = [
            'status'  => $phpOk ? 'pass' : 'fail',
            'version' => $phpVersion,
        ];
        if (!$phpOk) {
            $allOk = false;
        }

        // --- Check 2: Required PHP extensions ---
        $required   = ['PDO', 'pdo_mysql', 'openssl', 'mbstring', 'fileinfo'];
        $extResults = [];
        foreach ($required as $ext) {
            $present           = extension_loaded($ext);
            $extResults[$ext]  = $present ? 'pass' : 'fail';
            if (!$present) {
                $allOk = false;
            }
        }
        $checks['extensions'] = $extResults;

        // --- Check 3: GD extension (optional — only report status) ---
        $checks['gd_extension'] = [
            'status' => extension_loaded('gd') ? 'pass' : 'absent',
            'note'   => 'GD is not required for MVP features.',
        ];

        // --- Check 4: Database connectivity ---
        try {
            $pdo  = Database::getPdo();
            $stmt = $pdo->query('SELECT 1');
            $row  = $stmt ? $stmt->fetch() : false;
            $dbOk = ($row !== false);
            $checks['database'] = [
                'status'  => $dbOk ? 'pass' : 'fail',
                // Report only the database name (not host, user, or password).
                'database' => $_ENV['DB_DATABASE'] ?? 'unknown',
            ];
            if (!$dbOk) {
                $allOk = false;
            }
        } catch (\Throwable $e) {
            Logger::error('Health check: DB connection failed', ['error' => $e->getMessage()]);
            $checks['database'] = [
                'status' => 'fail',
                'error'  => 'Connection could not be established. Check server logs.',
            ];
            $allOk = false;
        }

        // --- Compose response ---
        $response = [
            'status'     => $allOk ? 'ok' : 'degraded',
            'checks'     => $checks,
            'app'        => 'ScamShield',
            'timestamp'  => date('c'),
        ];

        if (!headers_sent()) {
            http_response_code($allOk ? 200 : 503);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
