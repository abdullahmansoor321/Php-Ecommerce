<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Cart.php';
require_once __DIR__ . '/../core/Stripe.php';

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

$config = require __DIR__ . '/../config/stripe.php';
$shippingAddress = implode("\n", [
    trim($input['name']),
    trim($input['street']),
    trim($input['city']) . ', ' . trim($input['state']) . ' ' . trim($input['zip']),
    'Phone: ' . trim($input['phone']),
]);

$lineItems = [];
foreach ($cartItems as $item) {
    $lineItems[] = [
        'price_data' => [
            'currency' => $config['currency'],
            'product_data' => ['name' => $item['name']],
            'unit_amount' => (int)round((float)$item['price'] * 100),
        ],
        'quantity' => (int)$item['quantity'],
    ];
}

try {
    $session = Stripe::createCheckoutSession([
        'mode' => 'payment',
        'success_url' => FRONT_URL . '/process-stripe.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => FRONT_URL . '/checkout.php?payment=cancelled',
        'customer_email' => trim($input['email']),
        'line_items' => $lineItems,
        'metadata' => [
            'user_id' => (string)$_SESSION['user_id'],
            'shipping_address' => $shippingAddress,
        ],
    ]);

    echo json_encode(['success' => true, 'url' => $session['url']]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
