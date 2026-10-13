<?php

/**
 * PDO database connection factory.
 *
 * Provides a singleton PDO instance configured for MariaDB/MySQL.
 * Uses PDO::ERRMODE_EXCEPTION and disables emulated prepares.
 * Credentials are never echoed, logged, or exposed in output.
 */

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Return the shared PDO instance, creating it on first call.
     *
     * @param array<string, mixed>|null $config  Pass config array on first call;
     *                                           subsequent calls reuse the instance.
     */
    public static function getPdo(?array $config = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if ($config === null) {
            // Auto-load from config file if not provided.
            $config = require dirname(__DIR__, 2) . '/config/database.php';
        }

        $host    = $config['host']     ?? '127.0.0.1';
        $port    = $config['port']     ?? '3306';
        $db      = $config['database'] ?? '';
        $charset = $config['charset']  ?? 'utf8mb4';
        $user    = $config['username'] ?? '';
        $pass    = $config['password'] ?? '';

        if ($db === '') {
            throw new RuntimeException('Database name is not configured.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Log the real error but throw a sanitized exception — never expose credentials.
            Logger::error('Database connection failed', ['error' => $e->getMessage()]);
            throw new RuntimeException(
                'Unable to connect to the database. Check your configuration.',
                0,
                $e
            );
        }

        return self::$instance;
    }

    /**
     * Reset the singleton (useful in tests).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
