<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Analysis\UrlAnalysisService;

/**
 * AnalysisController
 *
 * Handles the URL / message / UPI analysis workflow:
 *
 *   GET  /analyze          — show analyzer form (pre-populated from query string if ?q= present)
 *   POST /analyze          — run service, redirect to result
 *   GET  /analyze/result   — display result from session (PRG pattern)
 *
 * Security:
 *  - POST input is capped at 4000 chars and stripped of NUL bytes before passing to the service.
 *  - The service NEVER makes outbound network requests (Rule 10).
 *  - All output is escaped with htmlspecialchars() in views.
 *  - No SQL in this controller — analysis is pure PHP, no DB writes.
 */
class AnalysisController
{
    /** Show the analyzer form (GET /analyze). */
    public function index(): void
    {
        $preloaded   = '';
        $activeTab   = 'url';
        $formAction  = '/analyze';
        $currentPage = 'analyzer';

        // Support ?q= pre-population (e.g., from home page "check this URL" links)
        if (!empty($_GET['q'])) {
            $preloaded = mb_substr(
                strip_tags((string)$_GET['q']),
                0,
                4000
            );
        }

        $title  = 'Analyzer';
        $this->render('pages/analyzer', $title, [
            'inputValue'  => $preloaded,
            'activeTab'   => $activeTab,
            'formAction'  => $formAction,
            'currentPage' => $currentPage,
        ]);
    }

    /** Run heuristics on the submitted payload (POST /analyze). */
    public function analyze(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Redirect GET requests to the form
            header('Location: /analyze', true, 302);
            exit;
        }

        // ── Input extraction ───────────────────────────────────────────
        $raw       = (string)($_POST['payload']    ?? '');
        $inputType = (string)($_POST['input_type'] ?? 'url');

        // Normalize type to allowed values
        if (!in_array($inputType, ['url', 'sms', 'upi'], true)) {
            $inputType = 'url';
        }

        // Sanitize: strip NUL bytes, trim, cap length
        $payload = str_replace("\0", '', $raw);
        $payload = trim($payload);
        $payload = mb_substr($payload, 0, 4000);

        // ── Validate ───────────────────────────────────────────────────
        if (mb_strlen($payload) < 4) {
            // Re-render the form with an error
            $this->render('pages/analyzer', 'Analyzer', [
                'inputValue'  => $payload,
                'activeTab'   => $inputType,
                'formAction'  => '/analyze',
                'currentPage' => 'analyzer',
                'formError'   => 'Please enter at least 4 characters to analyze.',
            ]);
            return;
        }

        // ── Run service ────────────────────────────────────────────────
        $service = new UrlAnalysisService();
        $result  = $service->analyze($payload, $inputType);

        // ── Render result page directly (no session / DB needed for MVP) ──
        $title       = 'Analysis Result — ' . ucfirst($result['risk_level']) . ' Risk';
        $currentPage = 'analyzer';

        $this->render('analysis/url_result', $title, [
            'result'      => $result,
            'currentPage' => $currentPage,
        ]);
    }

    /* ────────────────────────────────────────────────────────────────────
       PRIVATE HELPERS
       ──────────────────────────────────────────────────────────────────── */

    /**
     * Render a view inside the main layout.
     *
     * @param string               $view   Path under app/Views/ (no .php)
     * @param string               $title  Page <title> value
     * @param array<string, mixed> $vars   Variables to extract into view scope
     */
    private function render(string $view, string $title = 'ScamShield', array $vars = []): void
    {
        $viewsDir = dirname(__DIR__) . '/Views/';

        $viewFile   = $viewsDir . $view . '.php';
        $layoutFile = $viewsDir . 'layouts/main.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        // Make variables available to the view
        extract($vars, EXTR_SKIP);

        // Capture view output into $content
        ob_start();
        include $viewFile;
        $content = (string)ob_get_clean();

        // Render layout with $title and $content in scope
        include $layoutFile;
    }
}
