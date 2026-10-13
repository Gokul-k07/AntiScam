<?php

/**
 * ScamShield — database setup script.
 *
 * CLI-only: creates the scamshield database if it does not exist.
 * Run from the project root:
 *
 *   php database/setup.php
 *
 * Requirements:
 *   - .env must be present (or environment variables already set).
 *   - DB_USERNAME must have CREATE DATABASE privileges (XAMPP root is fine locally).
 *   - This script must NOT be web-accessible (it lives in database/, not public/).
 */

declare(strict_types=1);

// Guard: refuse to run from a web server.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

// ── Bootstrap env ─────────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

if (!file_exists($envFile)) {
    fwrite(STDERR, "ERROR: .env file not found. Copy .env.example to .env and fill in your values.\n");
    exit(1);
}

// Inline minimal env loader (avoids depending on app/Support/Bootstrap.php).
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $eqPos = strpos($line, '=');
    if ($eqPos === false) {
        continue;
    }
    $key   = trim(substr($line, 0, $eqPos));
    $value = trim(substr($line, $eqPos + 1));
    if (strlen($value) >= 2
        && (($value[0] === '"' && $value[-1] === '"')
            || ($value[0] === "'" && $value[-1] === "'"))) {
        $value = substr($value, 1, -1);
    }
    if (!isset($_ENV[$key]) && getenv($key) === false) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// ── Read config ───────────────────────────────────────────────────────────────
$host   = $_ENV['DB_HOST']     ?? '127.0.0.1';
$port   = $_ENV['DB_PORT']     ?? '3306';
$dbName = 'scamshield';
$user   = $_ENV['DB_USERNAME'] ?? 'root';
$pass   = $_ENV['DB_PASSWORD'] ?? '';

echo "ScamShield — database setup\n";
echo "============================\n";
echo "Host:     {$host}:{$port}\n";
echo "Database: {$dbName}\n";
echo "User:     {$user}\n";
echo "Password: [not shown]\n\n";

// ── Connect WITHOUT specifying a database ─────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    echo "[OK] Connected to MariaDB/MySQL.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: Could not connect to database server.\n");
    fwrite(STDERR, "       " . $e->getMessage() . "\n");
    fwrite(STDERR, "       Check DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD in .env\n");
    exit(1);
}

// ── Create database ───────────────────────────────────────────────────────────
try {
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$dbName}`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    echo "[OK] Database `{$dbName}` created or already exists.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: Could not create database: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Optional: create a least-privilege app user ───────────────────────────────
$appUser = 'scamshield_user';
$appPass = $_ENV['DB_APP_PASSWORD'] ?? '';

if ($appPass === '') {
    echo "[SKIP] DB_APP_PASSWORD not set in .env — skipping app user creation.\n";
    echo "       To create a least-privilege user, set DB_APP_PASSWORD in .env and re-run.\n";
} else {
    try {
        // Create user only if it doesn't exist (MariaDB 10.1.3+).
        $pdo->exec(
            "CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY " .
            $pdo->quote($appPass)
        );
        $pdo->exec(
            "GRANT SELECT, INSERT, UPDATE, DELETE ON `{$dbName}`.* TO '{$appUser}'@'localhost'"
        );
        $pdo->exec("FLUSH PRIVILEGES");
        echo "[OK] App user `{$appUser}@localhost` created/verified with limited privileges.\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "WARNING: Could not create app user: " . $e->getMessage() . "\n");
        fwrite(STDERR, "         Continuing — you can create the user manually.\n");
    }
}

echo "\n[DONE] Database setup complete.\n";
echo "       Next step: run php database/setup.php again after adding schema tables (Milestone 2).\n";
exit(0);
