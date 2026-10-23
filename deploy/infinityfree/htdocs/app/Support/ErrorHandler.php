<?php

/**
 * Centralized error and exception handler — InfinityFree deployment version.
 *
 * IDENTICAL to local ErrorHandler.php in logic.
 * dirname(__DIR__) from app/Support/ resolves to htdocs/app/ which is correct
 * in both local (app/Support/ → app/) and deploy (app/Support/ → app/) layouts.
 * The view path dirname(__DIR__) . '/Views/pages/error.php' is therefore the same.
 *
 * Production hardening enforced here:
 *  - APP_DEBUG is read from $_ENV but clamped to false if APP_ENV=production.
 *  - Stack traces, file paths, and credentials are NEVER exposed to users in production.
 *  - Full detail goes to error log only.
 */

declare(strict_types=1);

namespace App\Support;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e): void {
            self::handle($e);
        });
    }

    public static function handle(Throwable $e, int $statusCode = 500): void
    {
        // In production (APP_ENV=production), ALWAYS suppress debug output
        // regardless of APP_DEBUG value — belt-and-suspenders for deployment.
        $env   = strtolower(trim($_ENV['APP_ENV'] ?? 'production'));
        $debug = ($env !== 'production')
            && filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        Logger::error('Unhandled exception', [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        if ($debug) {
            echo self::renderDebugPage($e);
        } else {
            echo self::renderErrorPage();
        }
    }

    private static function renderErrorPage(): string
    {
        ob_start();
        $viewFile = dirname(__DIR__) . '/Views/pages/error.php';
        if (file_exists($viewFile)) {
            $debug   = false;
            $message = null;
            $trace   = null;
            include $viewFile;
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
               . '<title>Error — ScamShield</title></head><body>'
               . '<h1>Something went wrong</h1>'
               . '<p>We could not process your request. Please try again.</p>'
               . '</body></html>';
        }
        return (string)ob_get_clean();
    }

    private static function renderDebugPage(Throwable $e): string
    {
        ob_start();
        $viewFile = dirname(__DIR__) . '/Views/pages/error.php';
        if (file_exists($viewFile)) {
            $debug   = true;
            $message = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $trace   = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            include $viewFile;
        } else {
            echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }
        return (string)ob_get_clean();
    }
}
