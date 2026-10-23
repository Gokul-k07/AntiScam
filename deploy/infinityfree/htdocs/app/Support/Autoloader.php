<?php

/**
 * Simple PSR-4-style autoloader.
 *
 * Maps the App\ namespace prefix to the app/ directory.
 * No Composer required.
 */

declare(strict_types=1);

namespace App\Support;

class Autoloader
{
    /**
     * Register the autoloader with spl_autoload_register.
     */
    public static function register(): void
    {
        // app/ is two levels up from app/Support/Autoloader.php
        $appRoot = dirname(__DIR__);

        spl_autoload_register(static function (string $class) use ($appRoot): void {
            $prefix = 'App\\';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            // Strip the prefix and convert namespace separators to directory separators.
            $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
            $file     = $appRoot . DIRECTORY_SEPARATOR . $relative . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
