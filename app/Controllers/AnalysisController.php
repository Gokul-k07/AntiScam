<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Analysis\UrlAnalysisService;
use App\Services\Analysis\VirusTotalProvider;
use App\Services\Analysis\VirusTotalResult;
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
 *  - UrlAnalysisService NEVER makes outbound network requests (Rule §10)
 *  - VirusTotalProvider contacts only the fixed VT API endpoint, never the
 *    user-submitted URL itself (Rule §10)
 *  - All output escaped in views
 *  - DB persistence via prepared statements only (AnalysisRepository)
 *  - analysis_id returned to view so user can link it to an incident
 *  - VT API key is never passed to views, logs, or the browser
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

    /** Run heuristics (+ optional VT reputation), persist result, render result page (POST /analyze). */
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

        // ── 1. Local heuristic analysis (always runs, always airgapped) ──
        $service = new UrlAnalysisService();
        $result  = $service->analyze($payload, $inputType);

        // ── 2. VirusTotal external reputation (URL only, best-effort) ────
        //
        // We only look up VT for URL-type inputs. SMS and UPI text is never
        // sent to any external provider (message text may contain sensitive info).
        //
        // The VT result is intentionally kept separate from the heuristic result.
        // A VT result of "not_found" or zero detections MUST NOT be presented as
        // evidence of safety — see VirusTotalResult::notFound() message.
        $vtResult = VirusTotalResult::notConfigured();
        if ($inputType === 'url') {
            // Extract the actual URL from the payload (the service handles
            // URLs that are embedded inside longer text).
            $urlForLookup = $this->extractUrl($payload) ?? $payload;
            $vtResult = (new VirusTotalProvider())->lookup($urlForLookup);
        }

        // ── 3. Persist result ────────────────────────────────────────────
        // Nullable user_id if not logged in.
        $userId     = Session::isLoggedIn() ? (int)Session::user()['id'] : null;
        $repo       = new AnalysisRepository();
        $analysisId = $repo->create($result, $userId, $vtResult->toThreatStatus());

        $title       = 'Analysis Result — ' . ucfirst($result['risk_level']) . ' Risk';
        $currentPage = 'analyzer';

        $this->render('analysis/url_result', $title, [
            'result'      => $result,
            'vtResult'    => $vtResult,
            'analysisId'  => $analysisId,
            'currentPage' => $currentPage,
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Extract the first HTTP/HTTPS URL from a string.
     * Pure string parsing — no network requests.
     */
    private function extractUrl(string $text): ?string
    {
        if (preg_match('#(https?://[^\s"\'<>]+)#i', $text, $m)) {
            return $m[1];
        }
        return null;
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
