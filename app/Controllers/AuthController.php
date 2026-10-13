<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;
use App\Support\Session;
use App\Support\Csrf;

/**
 * Authentication: register, login, logout.
 *
 * Security rules applied:
 *  - Passwords hashed with password_hash() / verified with password_verify()
 *  - Session regenerated after login (Rule 4 §3)
 *  - CSRF validated on every state-changing POST
 *  - No stack traces or SQL errors exposed to users
 *  - Audit entries written for login / logout / failed login
 */
class AuthController
{
    private UserRepository  $users;
    private AuditRepository $audit;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->audit = new AuditRepository();
    }

    // ── Register ──────────────────────────────────────────────────────────

    public function showRegister(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /', true, 302);
            exit;
        }
        $this->render('auth/register', 'Create Account', [
            'errors' => [],
            'old'    => [],
        ]);
    }

    public function register(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /', true, 302);
            exit;
        }

        try {
            Csrf::verify($_POST);
        } catch (\RuntimeException) {
            $this->render('auth/register', 'Create Account', [
                'errors' => ['form' => 'Security token expired. Please try again.'],
                'old'    => [],
            ]);
            return;
        }

        $name     = trim((string)($_POST['display_name'] ?? ''));
        $email    = trim(strtolower((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $errors = $this->validateRegistration($name, $email, $password, $confirm);

        if (!empty($errors)) {
            $this->render('auth/register', 'Create Account', [
                'errors' => $errors,
                'old'    => ['display_name' => $name, 'email' => $email],
            ]);
            return;
        }

        // Check duplicate email
        if ($this->users->emailExists($email)) {
            $this->render('auth/register', 'Create Account', [
                'errors' => ['email' => 'An account with this email address already exists.'],
                'old'    => ['display_name' => $name, 'email' => $email],
            ]);
            return;
        }

        $hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->users->create($name, $email, $hash);

        // Log in immediately after registration
        $user = $this->users->findById($userId);
        Session::regenerate();
        Session::set('auth_user', [
            'id'           => $user['id'],
            'display_name' => $user['display_name'],
            'email'        => $user['email'],
            'role'         => $user['role'],
        ]);

        $this->audit->log('register', $userId, 'user', $userId, ['email' => $email]);
        Session::flash('success', 'Welcome to ScamShield, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '!');
        header('Location: /', true, 302);
        exit;
    }

    // ── Login ─────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /', true, 302);
            exit;
        }
        $this->render('auth/login', 'Sign In', [
            'error' => Session::getFlash('error'),
            'old'   => [],
        ]);
    }

    public function login(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /', true, 302);
            exit;
        }

        try {
            Csrf::verify($_POST);
        } catch (\RuntimeException) {
            $this->render('auth/login', 'Sign In', [
                'error' => 'Security token expired. Please try again.',
                'old'   => [],
            ]);
            return;
        }

        $email    = trim(strtolower((string)($_POST['email']    ?? '')));
        $password = (string)($_POST['password'] ?? '');

        // Generic error message — do not reveal whether email exists
        $genericError = 'Invalid email address or password.';

        if ($email === '' || $password === '') {
            $this->render('auth/login', 'Sign In', [
                'error' => $genericError,
                'old'   => ['email' => $email],
            ]);
            return;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            $this->audit->log('login_failed', null, null, null, ['email_hash' => hash('sha256', $email)]);
            $this->render('auth/login', 'Sign In', [
                'error' => $genericError,
                'old'   => ['email' => $email],
            ]);
            return;
        }

        if ($user['status'] !== 'active') {
            $this->render('auth/login', 'Sign In', [
                'error' => 'Your account has been suspended. Contact support.',
                'old'   => [],
            ]);
            return;
        }

        // Successful login
        Session::regenerate();
        Session::set('auth_user', [
            'id'           => (int)$user['id'],
            'display_name' => $user['display_name'],
            'email'        => $user['email'],
            'role'         => $user['role'],
        ]);

        $this->audit->log('login', (int)$user['id'], 'user', (int)$user['id']);

        $redirect = (string)Session::getFlash('redirect_after_login', '/');
        header('Location: ' . $redirect, true, 302);
        exit;
    }

    // ── Logout ────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $userId = Session::user()['id'] ?? null;
        if ($userId !== null) {
            $this->audit->log('logout', (int)$userId, 'user', (int)$userId);
        }
        Session::destroy();
        header('Location: /login', true, 302);
        exit;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /** @return array<string,string> errors keyed by field name */
    private function validateRegistration(string $name, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['display_name'] = 'Name must be between 2 and 120 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        return $errors;
    }

    private function render(string $view, string $title, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        $viewsDir   = dirname(__DIR__) . '/Views/';
        $viewFile   = $viewsDir . $view . '.php';
        $layoutFile = $viewsDir . 'layouts/main.php';
        ob_start();
        include $viewFile;
        $content = (string)ob_get_clean();
        include $layoutFile;
    }
}
