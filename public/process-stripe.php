<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Cart.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Stripe.php';

Session::start();

if (!Auth::check() || Auth::isAdmin()) {
    Session::setFlash('error', 'Please sign in as a customer to complete payment.');
    header('Location: ' . FRONT_URL . '/login.php');
    exit;
}

$sessionId = trim($_GET['session_id'] ?? '');
if ($sessionId === '') {
    Session::setFlash('error', 'Stripe payment session is missing.');
    header('Location: ' . FRONT_URL . '/checkout.php');
    exit;
}

try {
    $stripeSession = Stripe::retrieveCheckoutSession($sessionId);
    $metadata = $stripeSession['metadata'] ?? [];
    $userId = (int)$_SESSION['user_id'];

    if (($stripeSession['payment_status'] ?? '') !== 'paid' || (int)($metadata['user_id'] ?? 0) !== $userId) {
        throw new RuntimeException('Stripe payment could not be verified.');
    }

    $transactionId = (string)($stripeSession['payment_intent'] ?? $sessionId);
    $db = Database::getInstance();
    $existingOrder = $db->fetchOne('SELECT order_number FROM orders WHERE transaction_id = ?', [$transactionId]);
    if ($existingOrder) {
        header('Location: ' . FRONT_URL . '/order-confirmation.php?order_number=' . urlencode($existingOrder['order_number']));
        exit;
    }

    $cartItems = Cart::getItems();
    if (empty($cartItems)) {
        throw new RuntimeException('Your cart is empty or has already been processed.');
    }

    $cartTotalCents = (int)round(Cart::getTotal() * 100);
    if ((int)($stripeSession['amount_total'] ?? -1) !== $cartTotalCents) {
        throw new RuntimeException('The Stripe amount does not match the current cart.');
    }

    $mysqli = $db->getConnection();

    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $totalAmount = $cartTotalCents / 100;
    $shippingAddress = trim((string)($metadata['shipping_address'] ?? ''));

    if ($shippingAddress === '') {
        throw new RuntimeException('Shipping details are missing.');
    }

    $mysqli->begin_transaction();
    try {
        $orderStatement = $mysqli->prepare(
            "INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, order_status, transaction_id, shipping_address)
             VALUES (?, ?, ?, 'stripe', 'completed', 'processing', ?, ?)"
        );
        $orderStatement->bind_param('isdss', $userId, $orderNumber, $totalAmount, $transactionId, $shippingAddress);
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

            $itemStatement = $mysqli->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)'
            );
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
    } catch (Throwable $error) {
        $mysqli->rollback();
        throw $error;
    }

    header('Location: ' . FRONT_URL . '/order-confirmation.php?order_number=' . urlencode($orderNumber));
    exit;
} catch (Throwable $error) {
    Session::setFlash('error', $error->getMessage());
    header('Location: ' . FRONT_URL . '/checkout.php?payment=failed');
    exit;
}
