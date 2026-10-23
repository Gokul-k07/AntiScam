<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Session;

/**
 * Authorization middleware helpers.
 *
 * Call these at the top of any controller method that requires auth.
 * They redirect to /login on failure rather than throwing.
 */
class AuthMiddleware
{
    /**
     * Require an authenticated session.
     * Redirects to /login and halts if not logged in.
     */
    public static function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Please log in to continue.');
            header('Location: /login', true, 302);
            exit;
        }
    }

    /**
     * Require admin role.
     * Sends 403 and halts if user is not an admin.
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();   // must be logged in first

        if (!Session::isAdmin()) {
            http_response_code(403);
            // Render a minimal 403 page without leaking internals
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
               . '<title>403 Forbidden — ScamShield</title></head><body>'
               . '<h1>403 — Access Denied</h1>'
               . '<p>You do not have permission to access this page.</p>'
               . '<p><a href="/">Return to home</a></p>'
               . '</body></html>';
            exit;
        }
    }
}
