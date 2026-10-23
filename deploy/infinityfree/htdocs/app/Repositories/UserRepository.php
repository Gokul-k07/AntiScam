<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * User persistence — all queries use prepared statements.
 */
class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    /** Find a user by their email address. Returns array or null. */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, display_name, email, password_hash, role, status, created_at
               FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Find a user by ID. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, display_name, email, role, status, created_at
               FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Create a new user. Returns the new user's ID.
     * Caller must hash the password before passing it.
     */
    public function create(string $displayName, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (display_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$displayName, $email, $passwordHash, $role]);
        return (int)$this->pdo->lastInsertId();
    }

    /** True if email is already registered. */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }
}
