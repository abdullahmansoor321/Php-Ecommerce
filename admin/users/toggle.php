<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();
$id = (int)($_POST['id'] ?? 0);

// CSRF: only accept form submissions from our own pages (blocks forged requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['csrf_token'] ?? null)) {
    Session::setFlash('error', 'Security check failed. User status was not changed.');
    header('Location: ' . APP_URL . '/admin/users/index.php');
    exit;
}

// Prevent admin from deactivating themselves
if ($id === (int)$_SESSION['user_id']) {
    Session::setFlash('error', 'You cannot deactivate your own active admin account.');
    header('Location: ' . APP_URL . '/admin/users/index.php');
    exit;
}

$user = $db->fetchOne("SELECT id, is_active FROM users WHERE id = ?", [$id]);

if ($user) {
    $newStatus = $user['is_active'] == 1 ? 0 : 1;
    $db->query("UPDATE users SET is_active = ? WHERE id = ?", [$newStatus, $id]);
    Session::setFlash('success', 'User status updated successfully!');
} else {
    Session::setFlash('error', 'User not found.');
}

header('Location: ' . APP_URL . '/admin/users/index.php');
exit;
