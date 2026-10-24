<?php

/**
 * Minimal .env file loader — InfinityFree deployment version.
 *
 * KEY DIFFERENCE from local env.php:
 *   Local version loads .env from the project root (one level above config/).
 *   InfinityFree version loads .env from config/ itself because:
 *     1. open_basedir restricts PHP to htdocs/ only — files above htdocs/ are inaccessible.
 *     2. .env must therefore live inside htdocs/, in config/ protected by config/.htaccess.
 *
 * The .env file at config/.env is protected from HTTP access by config/.htaccess.
 * It is NOT web-accessible, but it IS readable by PHP (same htdocs tree).
 *
 * Reads KEY=VALUE pairs and populates $_ENV / putenv() for keys not already
 * set by the system environment. Must be the first config file included.
 */

declare(strict_types=1);

(static function (): void {
    // Check config/.env first (standard location), then fall back to root htdocs/.env
    $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($envFile)) {
        $rootEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($rootEnv)) {
            $envFile = $rootEnv;
        }
    }

    if (!file_exists($envFile)) {
        error_log('[ScamShield Boot] Notice: .env file not found in config/ or htdocs/. Using production defaults.');
        $_ENV['APP_ENV']   = $_ENV['APP_ENV']   ?? 'production';
        $_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 'false';
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new RuntimeException('Could not read environment configuration file.');
    }

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

        // Strip optional surrounding quotes.
        if (
            strlen($value) >= 2
            && (
                ($value[0] === '"'  && $value[-1] === '"')
                || ($value[0] === "'" && $value[-1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        if (!isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
})();
