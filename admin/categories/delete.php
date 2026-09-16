<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);

if ($category) {
    // Delete category image if exists
    if (!empty($category['image'])) {
        $imagePath = BASE_PATH . '/public/uploads/categories/' . $category['image'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    try {
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);
        Session::setFlash('success', 'Category deleted successfully!');
    } catch (mysqli_sql_exception $e) {
        Session::setFlash('error', 'Cannot delete category because products are still assigned to it.');
    }
} else {
    Session::setFlash('error', 'Category not found.');
}

header('Location: ' . APP_URL . '/admin/categories/index.php');
exit;
