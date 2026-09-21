<?php
require_once __DIR__ . '/../config/constants.php';

    require_once __DIR__ . '/../core/Session.php';
    require_once __DIR__ . '/../core/Auth.php';
    require_once __DIR__ . '/../core/Database.php';

    Session::start();
    if (!Auth::check() || Auth::isAdmin()) {
        Session::setFlash('error', 'Please sign in as a customer to view your order.');
        header('Location: ' . FRONT_URL . '/login.php');
        exit;
    }

    $orderNumber = trim($_GET['order_number'] ?? '');
    $db = Database::getInstance();
    $order = $db->fetchOne(
        'SELECT id, order_number, total_amount, payment_method, payment_status, created_at
         FROM orders WHERE order_number = ? AND user_id = ?',
        [$orderNumber, (int)$_SESSION['user_id']]
    );

    if (!$order) {
        Session::setFlash('error', 'Order not found.');
        header('Location: ' . FRONT_URL . '/shop.php');
        exit;
    }

    $paymentLabel = strtoupper((string)$order['payment_method']);
        $confirmationMessage = $order['payment_method'] === 'cod'
            ? 'Your order has been received. Payment will be collected on delivery.'
            : 'Your payment was received and your order is now being processed.';
    $page_title = 'Order Confirmation - Molla eCommerce';

    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="main">
    <div class="page-header text-center" style="background-image: url('<?= FRONT_ASSETS ?>/images/page-header-bg.jpg')">
        <div class="container">
            <h1 class="page-title">Order Received<span>Thank You</span></h1>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/shop.php">Shop</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Success</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="card p-5 border shadow-sm rounded">
                        <div class="mb-4"><i class="icon-check-circle text-success" style="font-size: 4rem;"></i></div>
                        <h2 class="title mb-2">Thank you for your order!</h2>
                            <p class="text-secondary mb-4"><?= htmlspecialchars($confirmationMessage) ?></p>

                        <div class="bg-light p-4 rounded mb-4 text-left">
                            <div class="row mb-2">
                                <div class="col-6"><strong>Order Number:</strong></div>
                                <div class="col-6 text-right text-primary">#<?= htmlspecialchars($order['order_number']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Date:</strong></div>
                                <div class="col-6 text-right"><?= htmlspecialchars(date('F j, Y', strtotime($order['created_at']))) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Payment Method:</strong></div>
                                <div class="col-6 text-right"><?= htmlspecialchars($paymentLabel) ?> (<?= htmlspecialchars($order['payment_status']) ?>)</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Total Amount:</strong></div>
                                <div class="col-6 text-right font-weight-bold text-dark">$<?= number_format((float)$order['total_amount'], 2) ?></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="<?= FRONT_URL ?>/shop.php" class="btn btn-outline-primary-2 btn-round"><span>Continue Shopping</span><i class="icon-long-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
