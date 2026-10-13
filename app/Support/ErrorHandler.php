<?php

/**
 * Centralized error and exception handler.
 *
 * In development (APP_DEBUG=true):  shows detailed output + logs.
 * In production (APP_DEBUG=false):  shows only a generic message; full detail in log only.
 *
 * Never exposes stack traces, file paths, credentials, or environment values to users
 * when APP_DEBUG is false.
 */

declare(strict_types=1);

namespace App\Support;

use Throwable;

class ErrorHandler
{
    /**
     * Register PHP error and exception handlers.
     */
    public static function register(): void
    {
        // Convert PHP errors to ErrorException so they flow through the exception handler.
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

    /**
     * Handle a throwable: log details, render appropriate output.
     */
    public static function handle(Throwable $e, int $statusCode = 500): void
    {
        $debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        // Always log the full detail.
        Logger::error('Unhandled exception', [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        // Send HTTP status if headers not yet sent.
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        if ($debug) {
            // Development: show full detail (never in production).
            echo self::renderDebugPage($e);
        } else {
            // Production: generic message only.
            echo self::renderErrorPage();
        }
    }

    /**
     * Render a clean generic error page (production).
     */
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

    /**
     * Render a detailed debug page (development only).
     */
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
