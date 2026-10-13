<?php

/**
 * Minimal .env file loader.
 *
 * Reads KEY=VALUE pairs from the project root .env file and populates
 * $_ENV / putenv() for keys not already set by the system environment.
 * Must be the first config file included.
 */

declare(strict_types=1);

(static function (): void {
    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    if (!file_exists($envFile)) {
        // .env is required for local development; fail safely without exposing path.
        throw new RuntimeException(
            'Environment configuration file not found. ' .
            'Copy .env.example to .env and fill in your local values.'
        );
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments.
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split on first = only.
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

        // Only set if not already provided by the system environment.
        if (!isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
})();
