<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Cart.php';
require_once __DIR__ . '/../core/Database.php';

Session::start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::check() || Auth::isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Customer login is required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$requiredFields = ['name', 'email', 'street', 'city', 'state', 'zip', 'phone'];
foreach ($requiredFields as $field) {
    if (trim((string)($input[$field] ?? '')) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please complete all shipping fields.']);
        exit;
    }
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

$cartItems = Cart::getItems();
if (empty($cartItems)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$shippingAddress = implode("\n", [
    trim($input['name']),
    trim($input['street']),
    trim($input['city']) . ', ' . trim($input['state']) . ' ' . trim($input['zip']),
    'Phone: ' . trim($input['phone']),
]);
$db = Database::getInstance();
$mysqli = $db->getConnection();
$userId = (int)$_SESSION['user_id'];
$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$totalAmount = Cart::getTotal();

try {
    $mysqli->begin_transaction();

    $orderStatement = $mysqli->prepare(
        "INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, order_status, transaction_id, shipping_address)
         VALUES (?, ?, ?, 'cod', 'pending', 'processing', NULL, ?)"
    );
    $orderStatement->bind_param('isds', $userId, $orderNumber, $totalAmount, $shippingAddress);
    $orderStatement->execute();
    $orderId = $orderStatement->insert_id;
    $orderStatement->close();

    foreach ($cartItems as $item) {
        $productId = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        $unitPrice = (float)$item['price'];
        $subtotal = $unitPrice * $quantity;

        $stockStatement = $mysqli->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND status = 1 AND stock >= ?');
        $stockStatement->bind_param('iii', $quantity, $productId, $quantity);
        $stockStatement->execute();
        if ($stockStatement->affected_rows !== 1) {
            $stockStatement->close();
            throw new RuntimeException('A product no longer has enough stock.');
        }
        $stockStatement->close();

        $itemStatement = $mysqli->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
        $itemStatement->bind_param('iiidd', $orderId, $productId, $quantity, $unitPrice, $subtotal);
        $itemStatement->execute();
        $itemStatement->close();
    }

    $db->query('DELETE FROM cart_items WHERE user_id = ?', [$userId]);
    $cartToken = $_COOKIE['cart_token'] ?? '';
    if ($cartToken !== '') {
        $db->query('DELETE FROM cart_items WHERE cart_token = ?', [$cartToken]);
    }
    $mysqli->commit();

    echo json_encode(['success' => true, 'redirect_url' => FRONT_URL . '/order-confirmation.php?order_number=' . urlencode($orderNumber)]);
} catch (Throwable $error) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
