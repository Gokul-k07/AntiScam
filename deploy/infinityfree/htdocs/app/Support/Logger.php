<?php

/**
 * Minimal file-based logger.
 *
 * Writes structured log entries to LOG_PATH/app.log.
 * Redacts sensitive context keys before writing.
 * Never exposes file paths or secrets to HTTP output.
 */

declare(strict_types=1);

namespace App\Support;

class Logger
{
    /** Context keys whose values must be redacted in log output. */
    private const REDACTED_KEYS = ['password', 'passwd', 'key', 'secret', 'token', 'api_key'];

    /**
     * Write a log entry.
     *
     * @param string               $level   e.g. 'debug', 'info', 'warning', 'error'
     * @param string               $message Human-readable message.
     * @param array<string, mixed> $context Additional structured data.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logPath = $_ENV['LOG_PATH'] ?? null;

        // If log path is not configured, fail silently — never expose to output.
        if ($logPath === null || $logPath === '') {
            return;
        }

        $logFile = rtrim($logPath, '/\\') . DIRECTORY_SEPARATOR . 'app.log';

        // Redact sensitive context values.
        $safeContext = self::redactContext($context);

        $timestamp = date('Y-m-d H:i:s');
        $level     = strtoupper($level);
        $contextJson = $safeContext !== [] ? ' ' . json_encode($safeContext, JSON_UNESCAPED_SLASHES) : '';
        $entry     = "[{$timestamp}] [{$level}] {$message}{$contextJson}" . PHP_EOL;

        // Silently ignore write failures — never expose path errors to output.
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /** Convenience wrappers. */
    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Recursively redact sensitive keys from a context array.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redactContext(array $context): array
    {
        $safe = [];
        foreach ($context as $k => $v) {
            if (in_array(strtolower((string)$k), self::REDACTED_KEYS, true)) {
                $safe[$k] = '[REDACTED]';
            } elseif (is_array($v)) {
                $safe[$k] = self::redactContext($v);
            } else {
                $safe[$k] = $v;
            }
        }
        return $safe;
    }
}
