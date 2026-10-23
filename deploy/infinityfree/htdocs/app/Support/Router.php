<?php

/**
 * Minimal array-based HTTP router.
 *
 * Supports GET, POST, and any HTTP method.
 * Normalizes trailing slashes and strips query strings before matching.
 */

declare(strict_types=1);

namespace App\Support;

class Router
{
    /** @var array<string, array<string, callable>> [METHOD => [path => handler]] */
    private array $routes = [];

    /** @var callable|null */
    private $notFoundHandler = null;

    /** @var callable|null */
    private $dynamicHandler = null;

    /**
     * Return the URL base path for building links and asset URLs.
     * Returns '' when served at a vhost root, or '/AntiScam/public' when in subfolder.
     * Use in views:  Router::basePath() . '/assets/css/tokens.css'
     */
    public static function basePath(): string
    {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim(dirname($scriptName), '/\\');
    }

    /**
     * Register a route.
     *
     * @param string   $method  HTTP method (GET, POST, …)
     * @param string   $path    URI path e.g. '/', '/health'
     * @param callable $handler Called when the route matches.
     */
    public function register(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path   = $this->normalizePath($path);
        $this->routes[$method][$path] = $handler;
    }

    /** Convenience: register a GET route. */
    public function get(string $path, callable $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    /** Convenience: register a POST route. */
    public function post(string $path, callable $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    /**
     * Register the handler called when no route matches.
     */
    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Register a dynamic route handler called before the 404 handler.
     * Receives (string $method, string $path) and returns bool (true = handled).
     */
    public function setDynamicHandler(callable $handler): void
    {
        $this->dynamicHandler = $handler;
    }

    /**
     * Match and dispatch the current request.
     *
     * Works correctly whether the app is served:
     *   (a) at a vhost root   — http://scamshield.local/  (SCRIPT_NAME = /index.php)
     *   (b) in a subfolder    — http://localhost/AntiScam/public/ (SCRIPT_NAME = /AntiScam/public/index.php)
     *
     * Strategy: derive basePath from dirname(SCRIPT_NAME), strip it from
     * REQUEST_URI, then normalize. This means routes are always registered
     * as '/', '/analyze', etc. regardless of the URL prefix.
     *
     * @param string $method HTTP method from $_SERVER['REQUEST_METHOD']
     * @param string $uri    Request URI from $_SERVER['REQUEST_URI']
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        // Strip query string
        $uriPath = strtok($uri, '?') ?: '/';

        // Compute base path from SCRIPT_NAME so the router works in a subfolder.
        // e.g. SCRIPT_NAME = /AntiScam/public/index.php  →  basePath = /AntiScam/public
        //      SCRIPT_NAME = /index.php                  →  basePath = (empty string)
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath   = rtrim(dirname($scriptName), '/\\');

        // Strip base path prefix from URI (case-insensitive on Windows paths)
        if ($basePath !== '' && stripos($uriPath, $basePath) === 0) {
            $uriPath = substr($uriPath, strlen($basePath));
        }

        $path = $this->normalizePath($uriPath ?: '/');

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        // Try dynamic (pattern) handler before 404
        if ($this->dynamicHandler !== null) {
            if (($this->dynamicHandler)($method, $path)) {
                return;
            }
        }

        // No match — call 404 handler.
        if ($this->notFoundHandler !== null) {
            http_response_code(404);
            ($this->notFoundHandler)();
        } else {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
               . '<title>Not Found — ScamShield</title></head><body>'
               . '<h1>404 — Page Not Found</h1>'
               . '<p><a href="/">Return to home</a></p>'
               . '</body></html>';
        }
    }

    /**
     * Normalize a path: ensure leading slash, collapse doubles, remove trailing slash
     * (except root '/').
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';
        return $path;
    }
}
