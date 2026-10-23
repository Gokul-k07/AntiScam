<?php

/**
 * ScamShield — Front Controller (InfinityFree / htdocs-root deployment)
 *
 * Differences from local public/index.php:
 *  - dirname(__DIR__) removed; app/ lives at __DIR__ . '/app/' (same htdocs root)
 *  - Bootstrap is required relative to this file's own directory
 *
 * InfinityFree open_basedir: PHP can only access htdocs/. All paths must
 * stay within htdocs/ — no ../.. navigation outside this directory tree.
 *
 * DO NOT upload local .env, tests/, Docs/, design/, or .git into htdocs.
 * DO upload config/.env (protected by config/.htaccess) with production values.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/Support/Bootstrap.php';

use App\Support\Bootstrap;
use App\Support\Router;
use App\Controllers\HomeController;
use App\Controllers\HealthController;
use App\Controllers\AnalysisController;
use App\Controllers\AuthController;
use App\Controllers\IncidentController;
use App\Controllers\AdminController;

$config = Bootstrap::init();

$router = new Router();

// ── Public ────────────────────────────────────────────────────────────────
$router->get('/', fn() => (new HomeController())->index());
$router->get('/health', fn() => (new HealthController())->index());

// ── Analysis ──────────────────────────────────────────────────────────────
$router->get('/analyze',  fn() => (new AnalysisController())->index());
$router->post('/analyze', fn() => (new AnalysisController())->analyze());

// ── Auth ──────────────────────────────────────────────────────────────────
$router->get('/register',  fn() => (new AuthController())->showRegister());
$router->post('/register', fn() => (new AuthController())->register());
$router->get('/login',     fn() => (new AuthController())->showLogin());
$router->post('/login',    fn() => (new AuthController())->login());
$router->get('/logout',    fn() => (new AuthController())->logout());
$router->post('/logout',   fn() => (new AuthController())->logout());

// ── Incidents (auth required — enforced inside controller) ────────────────
$router->get('/incidents',         fn() => (new IncidentController())->index());
$router->get('/incidents/create',  fn() => (new IncidentController())->showCreate());
$router->post('/incidents/create', fn() => (new IncidentController())->create());

// Dynamic incident routes: extract reference from URI
$router->setDynamicHandler(function (string $method, string $path): bool {
    // GET /incidents/{ref}
    if ($method === 'GET' && preg_match('#^/incidents/([A-Z0-9\-]{5,20})$#', $path, $m)) {
        (new IncidentController())->show($m[1]);
        return true;
    }
    // POST /incidents/{ref}/evidence
    if ($method === 'POST' && preg_match('#^/incidents/([A-Z0-9\-]{5,20})/evidence$#', $path, $m)) {
        (new IncidentController())->uploadEvidence($m[1]);
        return true;
    }
    // GET /evidence/{id}/download
    if ($method === 'GET' && preg_match('#^/evidence/(\d+)/download$#', $path, $m)) {
        (new IncidentController())->downloadEvidence((int)$m[1]);
        return true;
    }
    // GET /admin/incidents/{ref}
    if ($method === 'GET' && preg_match('#^/admin/incidents/([A-Z0-9\-]{5,20})$#', $path, $m)) {
        (new AdminController())->detail($m[1]);
        return true;
    }
    // POST /admin/incidents/{ref}/status
    if ($method === 'POST' && preg_match('#^/admin/incidents/([A-Z0-9\-]{5,20})/status$#', $path, $m)) {
        (new AdminController())->changeStatus($m[1]);
        return true;
    }
    return false;
});

// ── Admin (role check inside controller) ──────────────────────────────────
$router->get('/admin/incidents', fn() => (new AdminController())->queue());
$router->get('/admin/audit',     fn() => (new AdminController())->auditLog());

// ── 404 ───────────────────────────────────────────────────────────────────
$router->setNotFound(function (): void {
    $viewsDir = __DIR__ . '/app/Views/';
    ob_start();
    include $viewsDir . 'pages/not-found.php';
    $content = (string)ob_get_clean();
    $title   = 'Page Not Found';
    include $viewsDir . 'layouts/main.php';
});

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);
