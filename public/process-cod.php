<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Cart.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';

Session::start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::check() || Auth::isAdmin() || !Auth::isActiveAccount()) {
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

        // Stock is deducted by DB trigger trg_deduct_stock_on_item on the INSERT below.
        // If stock is insufficient the trigger raises an error -> caught below -> rollback.
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

    // The order is committed, so the mail below cannot roll anything back. This
    // is deliberately inside the outer try only because the JSON response is
    // built here too; Mailer::send() itself never throws, so a mail failure
    // still returns success and the customer reaches their confirmation page.
    Mailer::send(
        trim($input['name']),
        trim($input['email']),
        "Order confirmed - {$orderNumber} (pay on delivery)",
        Mailer::renderOrder(
            'Order received',
            'Thanks for shopping with us. We have received your order and it is being prepared. '
                . 'Payment is cash on delivery: $' . number_format($totalAmount, 2)
                . ' to be paid when your order arrives. Please keep the exact amount ready, '
                . 'as the courier may not be able to provide change. '
                . 'Check the delivery address below and reply to this email if anything needs '
                . 'changing, as we can still update it before dispatch.',
            [
                'order_number'     => $orderNumber,
                'total_amount'     => $totalAmount,
                'payment_method'   => 'Cash on Delivery',
                'created_at'       => date('Y-m-d H:i:s'),
                'shipping_address' => $shippingAddress,
            ],
            array_map(static function (array $item): array {
                $unitPrice = (float)$item['price'];
                return [
                    'name'       => (string)$item['name'],
                    'quantity'   => (int)$item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal'   => $unitPrice * (int)$item['quantity'],
                ];
            }, $cartItems)
        )
    );

    echo json_encode(['success' => true, 'redirect_url' => FRONT_URL . '/order-confirmation.php?order_number=' . urlencode($orderNumber)]);
} catch (Throwable $error) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
