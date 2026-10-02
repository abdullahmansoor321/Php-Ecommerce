<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

/**
 * Admin authentication, kept separate from the storefront Auth class.
 *
 * Uses the ADMINSESSID session (see Session::startAdmin()) and its own
 * session keys, so signing in to /admin no longer disturbs a customer
 * signed in on the storefront in the same browser - and vice versa.
 */
class AdminAuth
{
    /**
     * Authenticate an administrator by email and password.
     */
    public static function login(string $email, string $password): bool
    {
        Session::startAdmin();
        $db = Database::getInstance();

        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_user_name'] = $user['name'];
            $_SESSION['admin_user_email'] = $user['email'];
            $_SESSION['admin_user_role'] = $user['role'];

            return true;
        }

        return false;
    }

    /**
     * Check whether an administrator is signed in on the admin session.
     */
    public static function check(): bool
    {
        Session::startAdmin();
        return isset($_SESSION['admin_user_id']);
    }

    /**
     * Re-validate against the DB that the account is still active.
     */
    public static function isActiveAccount(): bool
    {
        if (!self::check()) {
            return false;
        }
        $db = Database::getInstance();
        $row = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$_SESSION['admin_user_id']]);
        return $row !== null && (int)$row['is_active'] === 1;
    }

    /**
     * Check whether the signed-in admin account has the admin role.
     */
    public static function isAdmin(): bool
    {
        Session::startAdmin();
        return self::check()
            && isset($_SESSION['admin_user_role'])
            && $_SESSION['admin_user_role'] === 'admin';
    }

    /**
     * Require admin privileges or bounce to the admin login page.
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
     * Get the currently signed-in administrator.
     */
    public static function user(): ?array
    {
        Session::startAdmin();
        if (!self::check()) {
            return null;
        }

        $db = Database::getInstance();
        return $db->fetchOne(
            "SELECT id, name, email, role, is_active, created_at FROM users WHERE id = ?",
            [$_SESSION['admin_user_id']]
        );
    }

    /**
     * Sign the administrator out, leaving any storefront session intact.
     */
    public static function logout(): void
    {
        Session::startAdmin();
        Session::destroy();
    }
}
