<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\Incidents\IncidentService;
use App\Services\Incidents\EvidenceService;
use App\Repositories\IncidentRepository;
use App\Repositories\EvidenceRepository;
use App\Support\Session;
use App\Support\Csrf;

/**
 * Incident and evidence management for authenticated users.
 *
 * Routes served:
 *   GET  /incidents            — list user's incidents
 *   GET  /incidents/create     — show create form
 *   POST /incidents/create     — submit incident
 *   GET  /incidents/{ref}      — view incident detail + evidence list
 *   POST /incidents/{ref}/evidence  — upload evidence file
 *   GET  /evidence/{id}/download    — serve authorized file download
 */
class IncidentController
{
    private IncidentService    $service;
    private EvidenceService    $evidenceService;
    private IncidentRepository $repo;
    private EvidenceRepository $evidenceRepo;

    public function __construct()
    {
        $this->service         = new IncidentService();
        $this->evidenceService = new EvidenceService();
        $this->repo            = new IncidentRepository();
        $this->evidenceRepo    = new EvidenceRepository();
    }

    // ── List ──────────────────────────────────────────────────────────────

    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $userId    = (int)Session::user()['id'];
        $incidents = $this->repo->listForUser($userId);

        $this->render('incidents/index', 'My Incidents', [
            'incidents' => $incidents,
            'success'   => Session::getFlash('success'),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function showCreate(): void
    {
        AuthMiddleware::requireAuth();
        $this->render('incidents/create', 'Report an Incident', [
            'errors' => [],
            'old'    => [],
            'analysisId' => (string)($_GET['from_analysis'] ?? ''),
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::requireAuth();

        try {
            Csrf::verify($_POST);
        } catch (\RuntimeException) {
            $this->render('incidents/create', 'Report an Incident', [
                'errors' => ['form' => 'Security token expired. Please try again.'],
                'old'    => $_POST,
                'analysisId' => '',
            ]);
            return;
        }

        $userId   = (int)Session::user()['id'];
        $result   = $this->service->validateCreate($_POST);

        if (!empty($result['errors'])) {
            $this->render('incidents/create', 'Report an Incident', [
                'errors' => $result['errors'],
                'old'    => $_POST,
                'analysisId' => (string)($_POST['analysis_id'] ?? ''),
            ]);
            return;
        }

        $created = $this->service->create($result['data'], $userId);
        Session::flash('success', 'Incident ' . $created['reference'] . ' submitted successfully.');
        header('Location: /incidents/' . urlencode($created['reference']), true, 302);
        exit;
    }

    // ── Show ──────────────────────────────────────────────────────────────

    public function show(string $reference): void
    {
        AuthMiddleware::requireAuth();
        $userId   = (int)Session::user()['id'];
        $isAdmin  = Session::isAdmin();

        // Authorization: users can only see their own incidents (admins see all)
        $incident = $isAdmin
            ? $this->repo->findByReference($reference, null)
            : $this->repo->findByReference($reference, $userId);

        if ($incident === null) {
            http_response_code(404);
            $this->render('pages/not-found', 'Not Found', []);
            return;
        }

        $evidence   = $this->evidenceRepo->listForIncident((int)$incident['id']);
        $history    = $this->repo->getHistory((int)$incident['id']);
        $uploadError = Session::getFlash('upload_error');
        $success     = Session::getFlash('success');

        $this->render('incidents/show', 'Incident ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'), [
            'incident'    => $incident,
            'evidence'    => $evidence,
            'history'     => $history,
            'uploadError' => $uploadError,
            'success'     => $success,
            'allowedTransitions' => $this->service->allowedTransitions($incident['status']),
        ]);
    }

    // ── Evidence upload ───────────────────────────────────────────────────

    public function uploadEvidence(string $reference): void
    {
        AuthMiddleware::requireAuth();

        try {
            Csrf::verify($_POST);
        } catch (\RuntimeException) {
            Session::flash('upload_error', 'Security token expired. Please try again.');
            header('Location: /incidents/' . urlencode($reference), true, 302);
            exit;
        }

        $userId   = (int)Session::user()['id'];
        $isAdmin  = Session::isAdmin();
        $incident = $isAdmin
            ? $this->repo->findByReference($reference, null)
            : $this->repo->findByReference($reference, $userId);

        if ($incident === null) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if ($incident['status'] === 'closed') {
            Session::flash('upload_error', 'Evidence cannot be added to a closed incident.');
            header('Location: /incidents/' . urlencode($reference), true, 302);
            exit;
        }

        $file   = $_FILES['evidence_file'] ?? [];
        $result = $this->evidenceService->store($file, (int)$incident['id'], $userId);

        if (!$result['ok']) {
            Session::flash('upload_error', $result['error']);
        } else {
            Session::flash('success', 'Evidence file uploaded successfully.');
        }

        header('Location: /incidents/' . urlencode($reference), true, 302);
        exit;
    }

    // ── Evidence download ─────────────────────────────────────────────────

    public function downloadEvidence(int $evidenceId): void
    {
        AuthMiddleware::requireAuth();
        $userId  = (int)Session::user()['id'];
        $isAdmin = Session::isAdmin();

        $file = $this->evidenceRepo->findById($evidenceId);
        if ($file === null) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }

        // Authorization: verify user owns the parent incident (or is admin)
        $incident = $this->repo->findById((int)$file['incident_id']);
        if ($incident === null || (!$isAdmin && (int)$incident['user_id'] !== $userId)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $ok = $this->evidenceService->serveDownload($file, $userId);
        if (!$ok) {
            http_response_code(404);
            echo 'File not available.';
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function render(string $view, string $title, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        $viewsDir   = dirname(__DIR__) . '/Views/';
        $viewFile   = $viewsDir . $view . '.php';
        $layoutFile = $viewsDir . 'layouts/main.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }
        ob_start();
        include $viewFile;
        $content = (string)ob_get_clean();
        include $layoutFile;
    }
}
