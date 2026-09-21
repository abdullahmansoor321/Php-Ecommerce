<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

$page_title = "Manage Orders";
require_once __DIR__ . '/../../includes/admin-header.php';

$db = Database::getInstance();

// Pagination setup
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalRows = (int)($db->fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$orders = $db->fetchAll("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset]);
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Order Tracking & Fulfillment</h6>
                </div>
            </div>
            
            <div class="card-body px-0 pb-2">
                <?php if ($success = Session::getFlash('success')): ?>
                    <div class="alert alert-success text-white mx-4" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error = Session::getFlash('error')): ?>
                    <div class="alert alert-danger text-white mx-4" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Order #</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Payment</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fulfillment</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No incoming customer orders yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($ord['order_number']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($ord['customer_name']) ?></h6>
                                            <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($ord['customer_email']) ?></p>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="text-secondary text-xs font-weight-bold">$<?= number_format((float)$ord['total_amount'], 2) ?></span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $ord['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= strtoupper($ord['payment_method']) ?> (<?= ucfirst($ord['payment_status']) ?>)
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <?php 
                                            $statusColor = match($ord['order_status']) {
                                                'delivered' => 'success',
                                                'shipped' => 'info',
                                                'cancelled' => 'danger',
                                                default => 'warning'
                                            };
                                            ?>
                                            <span class="badge badge-sm bg-gradient-<?= $statusColor ?>">
                                                <?= ucfirst($ord['order_status']) ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold"><?= $ord['created_at'] ?></span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= $ord['id'] ?>" class="btn btn-link text-dark px-3 mb-0 text-xs font-weight-bold">
                                                <i class="material-symbols-rounded text-sm me-1">visibility</i>View Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php require __DIR__ . '/../../includes/admin-pagination.php'; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
