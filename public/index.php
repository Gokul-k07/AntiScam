<?php

/**
 * ScamShield — front controller.
 *
 * All HTTP requests to the application pass through this file.
 * Located in public/ so that app/, config/, storage/, and .env
 * are never directly accessible over HTTP.
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
// app/ is one directory up from public/
require_once dirname(__DIR__) . '/app/Support/Bootstrap.php';

use App\Support\Bootstrap;
use App\Support\Router;
use App\Controllers\HomeController;
use App\Controllers\HealthController;
use App\Controllers\AnalysisController;

$config = Bootstrap::init();

// ── Router ────────────────────────────────────────────────────────────────────
$router = new Router();

// Home
$router->get('/', function (): void {
    (new HomeController())->index();
});

// Health check
$router->get('/health', function (): void {
    (new HealthController())->index();
});

// Analyzer form (GET)
$router->get('/analyze', function (): void {
    (new AnalysisController())->index();
});

// Run analysis (POST) — submits to /analyze
$router->post('/analyze', function (): void {
    (new AnalysisController())->analyze();
});

// 404 handler
$router->setNotFound(function (): void {
    $title   = 'Page Not Found';
    $content = '';
    ob_start();
    $viewFile = dirname(__DIR__) . '/app/Views/pages/not-found.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    }
    $content = (string) ob_get_clean();

    $layoutFile = dirname(__DIR__) . '/app/Views/layouts/main.php';
    if (file_exists($layoutFile)) {
        include $layoutFile;
    } else {
        echo '<h1>404 — Page Not Found</h1><p><a href="/">Home</a></p>';
    }
});

// ── Dispatch ──────────────────────────────────────────────────────────────────
$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);
