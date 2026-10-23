<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\Incidents\IncidentService;
use App\Repositories\IncidentRepository;
use App\Repositories\EvidenceRepository;
use App\Repositories\AuditRepository;
use App\Support\Session;
use App\Support\Csrf;

/**
 * Admin-only pages: report queue, incident detail, status change, audit log.
 * Every method calls AuthMiddleware::requireAdmin() first.
 */
class AdminController
{
    private IncidentService    $service;
    private IncidentRepository $repo;
    private EvidenceRepository $evidenceRepo;
    private AuditRepository    $audit;

    public function __construct()
    {
        $this->service      = new IncidentService();
        $this->repo         = new IncidentRepository();
        $this->evidenceRepo = new EvidenceRepository();
        $this->audit        = new AuditRepository();
    }

    // ── Queue (all reports) ───────────────────────────────────────────────

    public function queue(): void
    {
        AuthMiddleware::requireAdmin();

        $statusFilter = isset($_GET['status']) && $_GET['status'] !== ''
            ? (string)$_GET['status']
            : null;

        $incidents = $this->repo->listAll($statusFilter, 100, 0);
        $success   = Session::getFlash('success');

        $this->render('admin/queue', 'Admin — Report Queue', [
            'incidents'    => $incidents,
            'statusFilter' => $statusFilter,
            'success'      => $success,
        ]);
    }

    // ── Incident detail ───────────────────────────────────────────────────

    public function detail(string $reference): void
    {
        AuthMiddleware::requireAdmin();

        $incident = $this->repo->findByReference($reference);
        if ($incident === null) {
            http_response_code(404);
            $this->render('pages/not-found', 'Not Found', []);
            return;
        }

        $history   = $this->repo->getHistory((int)$incident['id']);
        $evidence  = $this->evidenceRepo->listForIncident((int)$incident['id']);
        $success   = Session::getFlash('success');
        $error     = Session::getFlash('error');

        $this->render('admin/detail', 'Incident ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'), [
            'incident'           => $incident,
            'history'            => $history,
            'evidence'           => $evidence,
            'success'            => $success,
            'error'              => $error,
            'allowedTransitions' => $this->service->allowedTransitions($incident['status']),
        ]);
    }

    // ── Status change (POST) ──────────────────────────────────────────────

    public function changeStatus(string $reference): void
    {
        AuthMiddleware::requireAdmin();

        try {
            Csrf::verify($_POST);
        } catch (\RuntimeException) {
            Session::flash('error', 'Security token expired. Please try again.');
            header('Location: /admin/incidents/' . urlencode($reference), true, 302);
            exit;
        }

        $incident = $this->repo->findByReference($reference);
        if ($incident === null) {
            http_response_code(404);
            echo 'Incident not found.';
            exit;
        }

        $newStatus = trim((string)($_POST['new_status'] ?? ''));
        $reason    = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 500);
        $adminId   = (int)Session::user()['id'];

        $ok = $this->service->changeStatus((int)$incident['id'], $adminId, $newStatus, $reason);

        if ($ok) {
            Session::flash('success', 'Status updated to ' . htmlspecialchars($newStatus, ENT_QUOTES, 'UTF-8') . '.');
        } else {
            Session::flash('error', 'Invalid status transition or incident not found.');
        }

        header('Location: /admin/incidents/' . urlencode($reference), true, 302);
        exit;
    }

    // ── Audit log ─────────────────────────────────────────────────────────

    public function auditLog(): void
    {
        AuthMiddleware::requireAdmin();
        $entries = $this->audit->listRecent(200);
        $this->render('admin/audit', 'Admin — Audit Log', [
            'entries' => $entries,
        ]);
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
