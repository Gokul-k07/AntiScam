<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Session bootstrap and helpers — InfinityFree deployment version.
 *
 * KEY DIFFERENCE from local Session.php:
 *   InfinityFree terminates SSL at a load balancer. The PHP process behind the
 *   load balancer may not see $_SERVER['HTTPS'] set to 'on'. Instead, the load
 *   balancer forwards the original scheme via HTTP header X-Forwarded-Proto.
 *
 *   This version detects HTTPS via BOTH methods so session cookies always get
 *   the Secure flag when the client is on HTTPS:
 *     - $_SERVER['HTTPS'] is set and not 'off'  (direct TLS, local dev)
 *     - $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'  (InfinityFree CDN/LB)
 *
 * All other session security controls are unchanged:
 *  - HttpOnly and SameSite=Lax cookies
 *  - session_regenerate_id(true) after login / privilege change
 *  - Custom session name from config
 */
class Session
{
    private static bool $started = false;

    /**
     * Configure and start the session.
     * Safe to call multiple times — only starts once per request.
     *
     * @param array<string,mixed> $config  app config array (needs 'session' sub-key)
     */
    public static function start(array $config = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $sessConfig = $config['session'] ?? [];
        $name       = (string)($sessConfig['name']     ?? 'scamshield_sess');
        $lifetime   = (int)($sessConfig['lifetime']    ?? 1800);

        // Detect HTTPS: direct TLS OR forwarded via InfinityFree load balancer.
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    /** Get a session value; returns $default if not set. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /** Set a session value. */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** Remove a session key. */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** True if key exists in session. */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Flash a value that survives exactly one subsequent get.
     */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Retrieve and clear a flash value. */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Regenerate session ID after login or privilege change.
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** Destroy session completely (logout). */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }

    /** Return currently logged-in user array, or null. */
    public static function user(): ?array
    {
        return self::get('auth_user');
    }

    /** True if a user is logged in. */
    public static function isLoggedIn(): bool
    {
        return self::has('auth_user');
    }

    /** True if logged-in user has admin role. */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }
}
