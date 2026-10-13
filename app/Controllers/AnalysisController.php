<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Analysis\UrlAnalysisService;
use App\Repositories\AnalysisRepository;
use App\Support\Session;

/**
 * AnalysisController
 *
 * GET  /analyze          — show analyzer form
 * POST /analyze          — run analysis, persist result, render result page
 *
 * Security:
 *  - POST input capped at 4000 chars, NUL bytes stripped
 *  - Service NEVER makes outbound network requests (Rule §4.1)
 *  - All output escaped in views
 *  - DB persistence via prepared statements only (AnalysisRepository)
 *  - analysis_id returned to view so user can link it to an incident
 */
class AnalysisController
{
    /** Show the analyzer form (GET /analyze). */
    public function index(): void
    {
        $preloaded   = '';
        $activeTab   = 'url';
        $currentPage = 'analyzer';

        if (!empty($_GET['q'])) {
            $preloaded = mb_substr(strip_tags((string)$_GET['q']), 0, 4000);
        }

        $this->render('pages/analyzer', 'Analyzer', [
            'inputValue'  => $preloaded,
            'activeTab'   => $activeTab,
            'formAction'  => '/analyze',
            'currentPage' => $currentPage,
            'formError'   => null,
        ]);
    }

    /** Run heuristics, persist result, render result page (POST /analyze). */
    public function analyze(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /analyze', true, 302);
            exit;
        }

        $raw       = (string)($_POST['payload']    ?? '');
        $inputType = (string)($_POST['input_type'] ?? 'url');

        if (!in_array($inputType, ['url', 'sms', 'upi'], true)) {
            $inputType = 'url';
        }

        $payload = mb_substr(str_replace("\0", '', trim($raw)), 0, 4000);

        if (mb_strlen($payload) < 4) {
            $this->render('pages/analyzer', 'Analyzer', [
                'inputValue'  => $payload,
                'activeTab'   => $inputType,
                'formAction'  => '/analyze',
                'currentPage' => 'analyzer',
                'formError'   => 'Please enter at least 4 characters to analyze.',
            ]);
            return;
        }

        $service    = new UrlAnalysisService();
        $result     = $service->analyze($payload, $inputType);

        // Persist result (nullable user_id if not logged in)
        $userId     = Session::isLoggedIn() ? (int)Session::user()['id'] : null;
        $repo       = new AnalysisRepository();
        $analysisId = $repo->create($result, $userId);

        $title       = 'Analysis Result — ' . ucfirst($result['risk_level']) . ' Risk';
        $currentPage = 'analyzer';

        $this->render('analysis/url_result', $title, [
            'result'      => $result,
            'analysisId'  => $analysisId,
            'currentPage' => $currentPage,
        ]);
    }

    private function render(string $view, string $title = 'ScamShield', array $vars = []): void
    {
        $viewsDir = dirname(__DIR__) . '/Views/';
        extract($vars, EXTR_SKIP);
        ob_start();
        include $viewsDir . $view . '.php';
        $content = (string)ob_get_clean();
        include $viewsDir . 'layouts/main.php';
    }
}
