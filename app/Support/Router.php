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
     * Match and dispatch the current request.
     *
     * @param string $method HTTP method from $_SERVER['REQUEST_METHOD']
     * @param string $uri    Request URI from $_SERVER['REQUEST_URI']
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        // Strip query string, normalize path.
        $path = $this->normalizePath(strtok($uri, '?') ?: '/');

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
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
