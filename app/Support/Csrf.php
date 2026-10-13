<?php

declare(strict_types=1);

namespace App\Support;

/**
 * CSRF token helpers.
 *
 * One token per session (rotating on successful validation).
 * Use in views:  <?= Csrf::field() ?>
 * Validate in controller:  Csrf::verify($_POST)   (throws on failure)
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME  = '_csrf';
    private const TOKEN_BYTES = 32;

    /** Return (and lazily generate) the current CSRF token string. */
    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(self::TOKEN_BYTES)));
        }
        return (string)Session::get(self::SESSION_KEY);
    }

    /**
     * Return an <input type="hidden"> HTML element with the CSRF token.
     * Safe to echo directly into a form.
     */
    public static function field(): string
    {
        $name  = htmlspecialchars(self::FIELD_NAME, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars(self::token(),    ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\">";
    }

    /**
     * Validate the CSRF token from a POST request.
     * Regenerates the token on success to prevent replay.
     *
     * @param array<string,mixed> $post  Typically $_POST
     * @throws \RuntimeException if token is missing or invalid
     */
    public static function verify(array $post): void
    {
        $submitted = (string)($post[self::FIELD_NAME] ?? '');
        $expected  = (string)Session::get(self::SESSION_KEY, '');

        if ($expected === '' || !hash_equals($expected, $submitted)) {
            // Log without exposing token values
            Logger::warning('CSRF validation failed', [
                'ip'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            http_response_code(403);
            throw new \RuntimeException('Invalid or missing CSRF token.');
        }

        // Rotate token after use
        Session::set(self::SESSION_KEY, bin2hex(random_bytes(self::TOKEN_BYTES)));
    }

    /** Return the field name (used in templates that need to know the key). */
    public static function fieldName(): string
    {
        return self::FIELD_NAME;
    }
}
