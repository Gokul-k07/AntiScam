<?php

/**
 * Application configuration.
 *
 * Requires config/env.php to have been loaded first (Bootstrap handles this).
 */

declare(strict_types=1);

return [
    'env'       => $_ENV['APP_ENV']   ?? 'production',
    'debug'     => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    'key'       => $_ENV['APP_KEY']   ?? '',
    'base_path' => dirname(__DIR__),
    'log_path'  => $_ENV['LOG_PATH']  ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs'),
    'log_level' => $_ENV['LOG_LEVEL'] ?? 'error',
    'session'   => [
        'name'     => $_ENV['SESSION_NAME']     ?? 'scamshield_sess',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 1800),
    ],
];
