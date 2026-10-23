<?php

/**
 * Application bootstrap — InfinityFree deployment version.
 *
 * KEY DIFFERENCE from local Bootstrap.php:
 *   Local: basePath = dirname(__DIR__, 2) — two levels up from app/Support/
 *          to reach the project root (AntiScam/).
 *   Deploy: basePath = dirname(__DIR__) — ONE level up from app/Support/
 *           to reach htdocs/ which IS the deployment root.
 *
 *   Layout on InfinityFree:
 *     htdocs/                 ← $basePath (this is the "project root" in deploy)
 *       app/Support/          ← __DIR__
 *       config/env.php        ← loaded from $basePath . '/config/env.php'
 *       config/app.php
 *       storage/logs/         ← log path from $config['log_path']
 *
 * All subsequent paths derived from $basePath stay within htdocs/ and are
 * therefore within open_basedir bounds.
 */

declare(strict_types=1);

namespace App\Support;

class Bootstrap
{
    /**
     * Initialise the application and return the app config array.
     *
     * @return array<string, mixed>
     */
    public static function init(): array
    {
        // ONE level up from app/Support/ = htdocs/ (the deployment root).
        $basePath = dirname(__DIR__);

        // 1. Manually require the Autoloader (it cannot load itself).
        require_once __DIR__ . '/Autoloader.php';
        Autoloader::register();

        // 2. Load config/.env into $_ENV.
        //    env.php now loads .env from __DIR__ (config/) rather than project root.
        require_once $basePath . '/config/env.php';

        // 3. Load app config.
        $config = require $basePath . '/config/app.php';

        // 4. Override display_errors from config — production must be Off.
        ini_set('display_errors', $config['debug'] ? '1' : '0');
        ini_set('display_startup_errors', $config['debug'] ? '1' : '0');

        // 5. Point PHP's error log to our log directory (inside htdocs/storage/logs/).
        $phpErrorLog = rtrim((string)$config['log_path'], '/\\')
            . DIRECTORY_SEPARATOR . 'php_errors.log';
        ini_set('error_log', $phpErrorLog);

        // 6. Register centralized error / exception handlers.
        ErrorHandler::register();

        // 7. Start secure session.
        Session::start($config);

        return $config;
    }
}
