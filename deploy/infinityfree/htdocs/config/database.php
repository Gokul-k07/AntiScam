<?php

/**
 * Database configuration.
 *
 * Requires config/env.php to have been loaded first (Bootstrap handles this).
 * All values come from environment — no hardcoded credentials.
 */

declare(strict_types=1);

$database = $_ENV['DB_DATABASE'] ?? '';

if ($database === '') {
    throw new RuntimeException(
        'Database name (DB_DATABASE) is not configured. Check your .env file.'
    );
}

return [
    'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT']     ?? '3306',
    'database' => $database,
    'username' => $_ENV['DB_USERNAME'] ?? '',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => 'utf8mb4',
];
