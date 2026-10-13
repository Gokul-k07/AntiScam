<?php

/**
 * Application bootstrap.
 *
 * Loads the autoloader, environment, and registers error handling.
 * Must be the very first thing included by the front controller.
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
        $basePath = dirname(__DIR__, 2); // project root = two levels up from app/Support/

        // 1. Manually require the Autoloader (it cannot load itself).
        require_once __DIR__ . '/Autoloader.php';
        Autoloader::register();

        // 2. Load .env into $_ENV.
        require_once $basePath . '/config/env.php';

        // 3. Load app config.
        $config = require $basePath . '/config/app.php';

        // 4. Override display_errors from config — do not rely on php.ini alone.
        ini_set('display_errors', $config['debug'] ? '1' : '0');
        ini_set('display_startup_errors', $config['debug'] ? '1' : '0');

        // 5. Point PHP's own error log to our log directory.
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
