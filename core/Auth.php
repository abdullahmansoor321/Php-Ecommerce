<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

class Auth
{
    /**
     * Authenticate user with email and password
     */
    public static function login(string $email, string $password): bool
    {
        Session::start();
        $db = Database::getInstance();
        
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            return true;
        }

        return false;
    }

    /**
     * Register a new customer
     */
    public static function register(string $name, string $email, string $password): bool
    {
        $db = Database::getInstance();

        // Check if email already exists
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->query(
            "INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'customer', 1)",
            [$name, $email, $hashedPassword]
        );

        return $stmt->affected_rows > 0;
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        Session::start();
        return isset($_SESSION['user_id']);
    }

    /**
     * Re-validate against the DB that the logged-in account is still active.
     * Closes the gap where a deactivated user keeps a working session.
     */
    public static function isActiveAccount(): bool
    {
        if (!self::check()) {
            return false;
        }
        $db = Database::getInstance();
        $row = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$_SESSION['user_id']]);
        return $row !== null && (int)$row['is_active'] === 1;
    }

    /**
     * Check if logged in user is admin
     */
    public static function isAdmin(): bool
    {
        Session::start();
        return self::check() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Require admin role or redirect
     */
    public static function requireAdmin(): void
    {
        if (!self::isAdmin() || !self::isActiveAccount()) {
            if (self::check()) {
                // Account was deactivated mid-session: kill the session
                self::logout();
            }
            Session::setFlash('error', 'Unauthorized access. Admin privileges required.');
            header('Location: ' . APP_URL . '/admin/login.php');
            exit;
        }
    }

    /**
     * Get current logged-in user data
     */
    public static function user(): ?array
    {
        Session::start();
        if (!self::check()) {
            return null;
        }

        $db = Database::getInstance();
        return $db->fetchOne("SELECT id, name, email, role, is_active, created_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        Session::destroy();
    }
}
