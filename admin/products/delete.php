<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);

if ($product) {
    if (!empty($product['image'])) {
        $imagePath = BASE_PATH . '/public/uploads/products/' . $product['image'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    try {
        $db->query("DELETE FROM products WHERE id = ?", [$id]);
        Session::setFlash('success', 'Product deleted successfully!');
    } catch (mysqli_sql_exception $e) {
        Session::setFlash('error', 'Cannot delete product because it is referenced in past customer orders.');
    }
} else {
    Session::setFlash('error', 'Product not found.');
}

header('Location: ' . APP_URL . '/admin/products/index.php');
exit;
