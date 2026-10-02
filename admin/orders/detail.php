<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/AdminAuth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/ProductImage.php';

Session::startAdmin();
AdminAuth::requireAdmin();

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
    SELECT oi.*, p.id as product_id, p.name as product_name, p.image as product_image 
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

    // Fulfillment moves forward only: processing -> shipped -> delivered.
    $flow = ['processing' => 0, 'shipped' => 1, 'delivered' => 2];
    $previousOrderStatus = $order['order_status'];
    $previousPaymentStatus = $order['payment_status'];
    $reject = static function (string $message) use ($id): void {
        Session::setFlash('error', $message);
        header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $id);
        exit;
    };

    // Cancellation is valid only while the parcel is still in the warehouse.
    // Once shipped a courier holds it, and once delivered it is a return.
    if ($orderStatus === 'cancelled' && $previousOrderStatus !== 'processing') {
        $reject('Only an order still being processed can be cancelled. A shipped order is with the courier and a delivered order needs a return.');
    }

    // Cancelled is terminal: an order cannot be brought back from it.
    if ($previousOrderStatus === 'cancelled' && $orderStatus !== 'cancelled') {
        $reject('A cancelled order cannot be reopened. Place a new order instead.');
    }

    // No rolling backwards through the fulfillment sequence.
    if ($orderStatus !== 'cancelled' && $previousOrderStatus !== 'cancelled'
        && $flow[$orderStatus] < $flow[$previousOrderStatus]) {
        $reject('An order cannot move backwards from ' . ucfirst($previousOrderStatus) . ' to ' . ucfirst($orderStatus) . '.');
    }

    // Payment is derived rather than free-standing, which is what keeps the two
    // statuses from ever disagreeing:
    //   stripe - settled at checkout, so it never changes; a successful payment
    //            is already completed and every other option stays locked.
    //   cod    - collected by the courier at the door, so it is pending until
    //            the order is delivered and completed from that point on.
    if ($order['payment_method'] === 'stripe') {
        $paymentStatus = $previousPaymentStatus;
    } else {
        $paymentStatus = $orderStatus === 'delivered' ? 'completed' : 'pending';
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

        // One email per real transition. Each branch compares against the status
        // before this save, so re-sending the same form sends nothing and moving
        // backwards (which the guard above already forbids) is never announced.
        // Cancellation is included: a customer whose order is cancelled and who
        // is not told will keep waiting for a parcel that is never coming, and a
        // card customer needs to know their payment is on its way back.
        $becameShipped = $orderStatus === 'shipped' && $previousOrderStatus !== 'shipped';
        $becameDelivered = $orderStatus === 'delivered' && $previousOrderStatus !== 'delivered';
        $becameCancelled = $orderStatus === 'cancelled' && $previousOrderStatus !== 'cancelled';

        if ($becameShipped || $becameDelivered || $becameCancelled) {
            $amount = '$' . number_format((float)$order['total_amount'], 2);
            $isCod = $order['payment_method'] === 'cod';

            $orderBlock = [
                'order_number'     => $order['order_number'],
                'total_amount'     => $order['total_amount'],
                'payment_method'   => $isCod ? 'Cash on Delivery' : 'Card (Stripe)',
                'created_at'       => $order['created_at'],
                'shipping_address' => $order['shipping_address'],
            ];

            $itemsBlock = array_map(static function (array $item): array {
                return [
                    'name'       => (string)$item['product_name'],
                    'quantity'   => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'subtotal'   => (float)$item['subtotal'],
                ];
            }, $orderItems);

            if ($becameCancelled) {
                // A card order was charged at checkout, so the money is already
                // gone and the customer must be told it is coming back. Nothing
                // was ever collected on a COD order, so it only needs the news.
                if ($isCod) {
                    $message = 'We are sorry, but your order has been cancelled and will not be delivered. '
                        . 'Nothing was charged, as this was a cash on delivery order and no payment was ever taken. '
                        . 'If you would like to order again, our shop is always open to you.';
                } else {
                    $message = 'We are sorry, but your order has been cancelled and will not be delivered. '
                        . 'Your payment of ' . $amount . ' has already been taken, and it will be refunded in full to the card you used. '
                        . 'Most banks post a refund within 5 to 10 working days, depending on their policy. '
                        . 'You do not need to do anything, and you will not be charged again.';
                }

                Mailer::send(
                    (string)$order['customer_name'],
                    (string)$order['customer_email'],
                    'Order cancelled - ' . $order['order_number'],
                    Mailer::renderOrder('Order cancelled', $message, $orderBlock, $itemsBlock)
                );
            } elseif ($becameShipped) {
                // Both methods: this is the moment the courier takes possession,
                // and it is the one thing the customer cannot learn any other way.
                $message = 'Good news, your order is on its way. We have handed it over to our courier '
                    . 'and it will be delivered to the address below. '
                    . ($isCod
                        ? 'Please keep ' . $amount . ' ready in cash for the courier, as they may not be able to provide change.'
                        : 'It has already been paid for, so nothing is due on delivery.');

                Mailer::send(
                    (string)$order['customer_name'],
                    (string)$order['customer_email'],
                    'Your order is on its way - ' . $order['order_number'],
                    Mailer::renderOrder('Order shipped', $message, $orderBlock, $itemsBlock)
                );
            } else {
                // Delivered. COD doubles as the payment receipt because the
                // courier collected the cash at the door. A card order was paid
                // at checkout, so it must not claim money arrived now.
                if ($isCod) {
                    $message = 'Thank you for shopping with us. Your payment of ' . $amount
                        . ' has been received, and your order has been delivered. '
                        . 'We hope you love it. This email is a receipt for your records, '
                        . 'so please keep it safe.';
                    $heading = 'Payment received';
                } else {
                    $message = 'Your order has been delivered. We hope you love it. '
                        . 'This is the final email for this order, as your payment of ' . $amount
                        . ' was taken when the order was placed.';
                    $heading = 'Order delivered';
                }

                Mailer::send(
                    (string)$order['customer_name'],
                    (string)$order['customer_email'],
                    'Order delivered - ' . $order['order_number'],
                    Mailer::renderOrder($heading, $message, $orderBlock, $itemsBlock)
                );
            }
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        Session::setFlash('error', 'Failed to update order status. Please try again.');
    }

    header('Location: ' . APP_URL . '/admin/orders/detail.php?id=' . $id);
    exit;
}

// Forward-only rules for the two dropdowns. Options stay visible but are
// disabled when selecting them would be invalid, which is what the admin sees
// as "unclickable" rather than the option simply vanishing.
$flow = ['processing' => 0, 'shipped' => 1, 'delivered' => 2];
$isCancelled = $order['order_status'] === 'cancelled';
$currentPos = $isCancelled ? null : ($flow[$order['order_status']] ?? null);

// An order option is selectable when it is the current value, a forward step
// from processing, or cancellation of an order that is still processing.
$orderOptionDisabled = static function (string $value) use ($order, $isCancelled, $currentPos, $flow): bool {
    if ($value === $order['order_status']) {
        return false;
    }
    if ($value === 'cancelled') {
        return $isCancelled || $order['order_status'] !== 'processing';
    }
    if ($isCancelled || $currentPos === null) {
        return true;
    }
    return ($flow[$value] ?? -1) < $currentPos;
};

// Payment is derived, so only the value the server would accept is selectable:
// stripe is locked to what checkout already settled, COD unlocks on delivery.
$paymentOptionDisabled = static function (string $value) use ($order): bool {
    if ($value === $order['payment_status']) {
        return false;
    }
    if ($order['payment_method'] === 'stripe') {
        return true;
    }
    if ($value === 'completed') {
        return $order['order_status'] !== 'delivered';
    }
    return $order['payment_status'] === 'completed' || $value === 'failed';
};

// Display helpers for the header badges and the progress strip below. Kept out
// of the markup so the colour rules read in one place.
$paymentBadge = match ($order['payment_status']) {
    'completed' => ['success', 'Paid'],
    'failed'    => ['danger',  'Payment failed'],
    default     => ['warning', 'Payment pending'],
};

$fulfilmentBadge = match ($order['order_status']) {
    'shipped'   => ['info',    'Shipped'],
    'delivered' => ['success', 'Delivered'],
    'cancelled' => ['danger',  'Cancelled'],
    default     => ['warning', 'Processing'],
};

$stepPos = $isCancelled ? -1 : ($currentPos ?? -1);

$steps = [
    'processing' => ['Processing', 'inventory_2'],
    'shipped'    => ['Shipped',    'local_shipping'],
    'delivered'  => ['Delivered',  'check_circle'],
];

// The one action the admin is expected to take next, or null when there is none.
$nextAction = match ($order['order_status']) {
    'processing' => ['Mark as Shipped',   'local_shipping'],
    'shipped'    => ['Mark as Delivered', 'check_circle'],
    default      => null,
};

$page_title = "Order Details - " . $order['order_number'];
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <h6 class="text-white m-0">Order <?= htmlspecialchars($order['order_number']) ?></h6>
                        <span class="badge badge-sm bg-gradient-<?= $fulfilmentBadge[0] ?>"><?= $fulfilmentBadge[1] ?></span>
                        <span class="badge badge-sm bg-gradient-<?= $paymentBadge[0] ?>"><?= $paymentBadge[1] ?></span>
                        <span class="badge badge-sm bg-gradient-light text-dark"><?= strtoupper($order['payment_method']) ?></span>
                    </div>
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

                <?php if ($isCancelled): ?>
                    <div class="alert alert-danger text-white mb-4 d-flex align-items-center" role="alert">
                        <i class="material-symbols-rounded me-2">cancel</i>
                        <span class="text-sm">This order was cancelled and the items were returned to stock.</span>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center mb-4 flex-wrap">
                        <?php foreach ($steps as $key => [$label, $icon]): $pos = $flow[$key];
                            $done = $stepPos > $pos;
                            $active = $stepPos === $pos; ?>
                            <div class="d-flex align-items-center <?= $active ? '' : 'opacity-6' ?>">
                                <div class="icon icon-shape icon-sm border-radius-md d-flex align-items-center justify-content-center <?= $done ? 'bg-gradient-success' : ($active ? 'bg-gradient-dark' : 'bg-gray-200') ?>">
                                    <i class="material-symbols-rounded text-white" style="font-size: 16px;"><?= $done ? 'check' : $icon ?></i>
                                </div>
                                <span class="text-xs ms-2 <?= $active ? 'font-weight-bold text-dark' : 'text-secondary' ?>"><?= $label ?></span>
                            </div>
                            <?php if ($key !== 'delivered'): ?>
                                <div class="flex-fill mx-2 border-top <?= $done ? 'border-success' : '' ?>" style="opacity: .6;"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <!-- Customer & Shipping Info -->
                    <div class="col-lg-6 mb-3 mb-lg-0">
                        <div class="card border border-radius-lg p-3 h-100 mb-0">
                            <h6 class="text-dark font-weight-bold mb-3 d-flex align-items-center">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">person</i>
                                Customer &amp; Shipping
                            </h6>

                            <div class="d-flex align-items-start mb-3">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">badge</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-0">Customer</p>
                                    <p class="text-sm text-dark mb-0"><?= htmlspecialchars($order['customer_name']) ?></p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">mail</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-0">Email</p>
                                    <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>" class="text-sm text-dark mb-0"><?= htmlspecialchars($order['customer_email']) ?></a>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">credit_card</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-0">Payment method</p>
                                    <p class="text-sm text-dark mb-0">
                                        <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card (Stripe)' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">receipt_long</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-0">Transaction</p>
                                    <p class="text-sm text-dark mb-0 text-break">
                                        <?= $order['transaction_id'] ? htmlspecialchars($order['transaction_id']) : 'Not applicable (paid on delivery)' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start mb-3">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">event</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-0">Placed on</p>
                                    <p class="text-sm text-dark mb-0"><?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime((string)$order['created_at']))) ?></p>
                                </div>
                            </div>

                            <div class="d-flex align-items-start">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">location_on</i>
                                <div class="flex-grow-1">
                                    <p class="text-xxs text-secondary text-uppercase font-weight-bold mb-1">Shipping address</p>
                                    <div class="bg-gray-100 border-radius-md p-2 text-sm text-dark" style="white-space: pre-line;"><?= htmlspecialchars($order['shipping_address']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fulfillment & Status Update Form -->
                    <div class="col-lg-6">
                        <div class="card border border-radius-lg p-3 h-100 mb-0">
                            <h6 class="text-dark font-weight-bold mb-3 d-flex align-items-center">
                                <i class="material-symbols-rounded text-secondary me-2" style="font-size: 18px;">tune</i>
                                Update Status
                            </h6>

                            <div class="bg-gray-100 border-radius-md p-3 mb-3">
                                <p class="text-xs text-secondary mb-0">
                                    <?php if ($isCancelled): ?>
                                        This order is closed. No further status changes are possible, and the items have been returned to stock.
                                    <?php elseif ($order['payment_method'] === 'stripe'): ?>
                                        Paid by card at checkout, so payment is settled and locked.
                                        An order only moves forward: <strong>Processing</strong> &#8594; <strong>Shipped</strong> &#8594; <strong>Delivered</strong>, and can be cancelled only while still processing.
                                    <?php else: ?>
                                        Cash is collected by the courier, so payment unlocks to <strong>Completed</strong> once the order is delivered.
                                        An order only moves forward: <strong>Processing</strong> &#8594; <strong>Shipped</strong> &#8594; <strong>Delivered</strong>, and can be cancelled only while still processing.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <form action="" method="POST">
                                <?= Csrf::field() ?>

                                <div class="input-group input-group-outline mb-1 is-filled">
                                    <label class="form-label">Order Fulfillment Status</label>
                                    <select name="order_status" class="form-control">
                                        <?php foreach (['processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $value => $label): ?>
                                            <option value="<?= $value ?>"
                                                <?= $value === $order['order_status'] ? 'selected' : '' ?>
                                                <?= $orderOptionDisabled($value) ? 'disabled' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <p class="text-xs text-secondary d-flex align-items-start mb-3">
                                    <i class="material-symbols-rounded me-1" style="font-size: 15px;"><?= $order['order_status'] === 'processing' && !$isCancelled ? 'info' : 'lock' ?></i>
                                    <span>
                                        <?php if ($isCancelled): ?>
                                            This order was cancelled and cannot be reopened.
                                        <?php elseif ($order['order_status'] === 'processing'): ?>
                                            Still in the warehouse, so every option is open.
                                        <?php elseif ($order['order_status'] === 'shipped'): ?>
                                            The courier has it, so it can only be marked Delivered now.
                                        <?php else: ?>
                                            Delivered, so nothing further can be selected.
                                        <?php endif; ?>
                                    </span>
                                </p>

                                <div class="input-group input-group-outline mb-1 is-filled">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-control">
                                        <?php foreach (['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed'] as $value => $label): ?>
                                            <option value="<?= $value ?>"
                                                <?= $value === $order['payment_status'] ? 'selected' : '' ?>
                                                <?= $paymentOptionDisabled($value) ? 'disabled' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <p class="text-xs text-secondary d-flex align-items-start mb-3">
                                    <i class="material-symbols-rounded me-1" style="font-size: 15px;"><?= $order['payment_status'] === 'completed' ? 'check_circle' : 'lock' ?></i>
                                    <span>
                                        <?php if ($isCancelled): ?>
                                            Nothing was collected, as the order was cancelled before delivery.
                                        <?php elseif ($order['payment_method'] === 'stripe'): ?>
                                            Settled at checkout, so this cannot be changed.
                                        <?php elseif ($order['payment_status'] === 'completed'): ?>
                                            Collected by the courier on delivery.
                                        <?php else: ?>
                                            Stays pending until the order is marked Delivered.
                                        <?php endif; ?>
                                    </span>
                                </p>

                                <button type="submit" class="btn bg-gradient-dark w-100 mb-0" <?= $isCancelled ? 'disabled' : '' ?>>
                                    <?= $nextAction ? 'Save and ' . htmlspecialchars($nextAction[0]) : 'Save Status' ?>
                                </button>
                                <p class="text-xxs text-secondary text-center mt-2 mb-0">
                                    <?php if ($isCancelled): ?>
                                        This order is closed.
                                    <?php else: ?>
                                        The customer is emailed when the order is marked Shipped or Delivered.
                                    <?php endif; ?>
                                </p>
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
                                                $itemImageUrl = ProductImage::exists((int)($item['product_id'] ?? 0), $item['product_image'] ?? null) ? ProductImage::url((int)$item['product_id'], ProductImage::first($item['product_image'])) : '';
                                                if ($itemImageUrl !== ''):
                                                ?>
                                                    <img src="<?= htmlspecialchars($itemImageUrl) ?>" class="avatar avatar-sm me-3 border-radius-lg" alt="product image" style="object-fit: cover;">
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
