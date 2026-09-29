<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Mailer.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$order = $db->fetchOne("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$id]);

if (!$order) {
    Session::setFlash('error', 'Order not found.');
    header('Location: ' . APP_URL . '/admin/orders/index.php');
    exit;
}

// Fetched before the POST handler because the payment-received email below
// needs the line items at send time, and the handler exits via redirect.
$orderItems = $db->fetchAll("
    SELECT oi.*, p.name as product_name, p.image as product_image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
", [$id]);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Session::setFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $id);
        exit;
    }
    $allowedOrderStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
    $allowedPaymentStatuses = ['pending', 'completed', 'failed'];

    $orderStatus = trim($_POST['order_status'] ?? '');
    $paymentStatus = trim($_POST['payment_status'] ?? '');

    if (!in_array($orderStatus, $allowedOrderStatuses, true) || !in_array($paymentStatus, $allowedPaymentStatuses, true)) {
        Session::setFlash('error', 'Invalid order or payment status value submitted.');
        header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $id);
        exit;
    }

    // The two toggles drive each other, because cash on delivery is collected
    // at the door: the money and the parcel always change hands together.
    // Marking payment completed sets delivered, and marking delivered sets
    // payment completed, so a single save can never leave the order claiming
    // to be paid but undelivered (or the reverse).
    if ($order['payment_method'] === 'cod') {
        if ($paymentStatus === 'completed') {
            $orderStatus = 'delivered';
        } elseif ($orderStatus === 'delivered') {
            $paymentStatus = 'completed';
        }
    }

    $conn = $db->getConnection();
    $conn->begin_transaction();

    try {
        // Stock restock/re-deduct on cancel transitions is handled by
        // DB trigger trg_restock_on_cancel (fires on this UPDATE).
        $db->query(
            "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?",
            [$orderStatus, $paymentStatus, $id]
        );

        $conn->commit();
        Session::setFlash('success', 'Order status updated successfully!' . ($orderStatus === 'cancelled' ? ' Items restocked to inventory.' : ''));

        // One email reports both statuses exactly as saved, rather than
        // paraphrasing them. Cash on delivery is collected at the door, and
        // admins commonly tick payment and delivered in separate saves, so any
        // narrative copy here ("on its way", "being prepared") becomes wrong the
        // moment the next toggle is saved. Listing the two recorded values is
        // never wrong. Sent only when something actually changed, so re-saving
        // the page does not resend, and never for cancelled orders.
        $statusChanged = $paymentStatus !== $order['payment_status']
            || $orderStatus !== $order['order_status'];

        if ($statusChanged && $orderStatus !== 'cancelled' && $order['payment_method'] === 'cod') {
            // Payment and delivery are joined for COD (see above), so a completed
            // payment always means the order was handed over. That lets the copy
            // state both as plain fact and stay warm, with nothing to hedge.
            $message = 'Thank you for shopping with us. Your payment of $' . number_format((float)$order['total_amount'], 2)
                . ' has been received, and your order has been delivered. '
                . 'We hope you love it. This email is a receipt for your records, '
                . 'so please keep it safe.';

            Mailer::send(
                (string)$order['customer_name'],
                (string)$order['customer_email'],
                'Order delivered - ' . $order['order_number'],
                Mailer::renderOrder(
                    'Payment received',
                    $message,
                    [
                        'order_number'     => $order['order_number'],
                        'total_amount'     => $order['total_amount'],
                        'payment_method'   => 'Cash on Delivery',
                        'created_at'       => $order['created_at'],
                        'shipping_address' => $order['shipping_address'],
                    ],
                    array_map(static function (array $item): array {
                        return [
                            'name'       => (string)$item['product_name'],
                            'quantity'   => (int)$item['quantity'],
                            'unit_price' => (float)$item['unit_price'],
                            'subtotal'   => (float)$item['subtotal'],
                        ];
                    }, $orderItems)
                )
            );
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        Session::setFlash('error', 'Failed to update order status. Please try again.');
    }

    header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $id);
    exit;
}

$page_title = "Order Details - " . $order['order_number'];
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Order: <?= htmlspecialchars($order['order_number']) ?></h6>
                    <a href="<?= APP_URL ?>/admin/orders/index.php" class="btn btn-sm bg-gradient-light mb-0">Back to Orders</a>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <?php if ($success = Session::getFlash('success')): ?>
                    <div class="alert alert-success text-white mb-4" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error = Session::getFlash('error')): ?>
                    <div class="alert alert-danger text-white mb-4" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="row mb-4">
                    <!-- Customer & Shipping Info -->
                    <div class="col-md-6">
                        <div class="card border p-3 h-100">
                            <h6 class="text-dark font-weight-bold mb-3">Customer & Shipping Information</h6>
                            <p class="text-sm mb-1"><strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="text-sm mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                            <p class="text-sm mb-1"><strong>Payment Method:</strong> <?= strtoupper($order['payment_method']) ?></p>
                            <p class="text-sm mb-1"><strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id'] ?? 'N/A (COD)') ?></p>
                            <p class="text-sm mb-0"><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        </div>
                    </div>

                    <!-- Fulfillment & Status Update Form -->
                    <div class="col-md-6">
                        <div class="card border p-3 h-100">
                            <h6 class="text-dark font-weight-bold mb-3">Update Order Status</h6>
                            <?php if ($order['payment_method'] === 'cod'): ?>
                                <p class="text-xs text-secondary mb-3">
                                    Cash on delivery is collected at the door, so payment and delivery move together.
                                    Marking the payment <strong>Completed</strong> also marks the order
                                    <strong>Delivered</strong>, and marking it <strong>Delivered</strong> also marks the payment
                                    <strong>Completed</strong>.
                                </p>
                            <?php endif; ?>
                            <form action="" method="POST">
                                <?= Csrf::field() ?>
                                <div class="input-group input-group-outline mb-3 is-filled">
                                    <label class="form-label">Order Fulfillment Status</label>
                                    <select name="order_status" class="form-control">
                                        <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="input-group input-group-outline mb-3 is-filled">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-control">
                                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="completed" <?= $order['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn bg-gradient-dark w-100 mb-0">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Line Items Table -->
                <h6 class="text-dark font-weight-bold mb-3">Ordered Items</h6>
                <div class="table-responsive p-0 border border-radius-lg">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Product</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Unit Price</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Quantity</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-3 py-1 align-items-center">
                                            <div>
                                                <?php
                                                $itemImageFile = !empty($item['product_image']) ? basename($item['product_image']) : '';
                                                if ($itemImageFile !== '' && is_file(BASE_PATH . '/public/uploads/products/' . $itemImageFile)):
                                                ?>
                                                    <img src="<?= UPLOADS_URL ?>/products/<?= rawurlencode($itemImageFile) ?>" class="avatar avatar-sm me-3 border-radius-lg" alt="product image" style="object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($item['product_name']) ?></h6>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary text-xs font-weight-bold">$<?= number_format((float)$item['unit_price'], 2) ?></span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary text-xs font-weight-bold"><?= $item['quantity'] ?></span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary text-xs font-weight-bold">$<?= number_format((float)$item['subtotal'], 2) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end font-weight-bold">Total Amount:</td>
                                <td class="text-center font-weight-bold text-dark">$<?= number_format((float)$order['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
