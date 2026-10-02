<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ProductImage.php';

$db = Database::getInstance();

/* ------------------------------------------------------------------ *
 *  Headline KPIs
 *
 *  Revenue convention: a cancelled order is not a sale. Every figure that
 *  reports money, order counts or units excludes order_status = 'cancelled'.
 *  The status/payment breakdowns further down deliberately DO include it,
 *  because showing cancellations is the point of those charts.
 * ------------------------------------------------------------------ */
$totalProducts   = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM products")['c'] ?? 0);
$totalCategories = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM categories")['c'] ?? 0);
$totalOrders     = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE order_status <> 'cancelled'")['c'] ?? 0);
$totalRevenue    = (float)($db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) AS t FROM orders WHERE payment_status = 'completed' AND order_status <> 'cancelled'")['t'] ?? 0);

/* Revenue vs. yesterday */
$todayRevenue     = (float)($db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) AS t FROM orders WHERE payment_status = 'completed' AND order_status <> 'cancelled' AND DATE(created_at) = CURDATE()")['t'] ?? 0);
$yesterdayRevenue = (float)($db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) AS t FROM orders WHERE payment_status = 'completed' AND order_status <> 'cancelled' AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY")['t'] ?? 0);
if ($yesterdayRevenue > 0) {
    $revenueDelta = (int)round(($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue * 100);
} else {
    $revenueDelta = $todayRevenue > 0 ? 100 : 0;
}

/* Catalog & fulfilment */
$activeFulfillments = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE order_status IN ('processing', 'shipped')")['c'] ?? 0);
$lowStock           = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM products WHERE stock <= 5")['c'] ?? 0);
$activeCategories   = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM categories WHERE status = 1")['c'] ?? 0);

/* ------------------------------------------------------------------ *
 *  Secondary KPIs
 * ------------------------------------------------------------------ */
$completedOrders = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE payment_status = 'completed' AND order_status <> 'cancelled'")['c'] ?? 0);
$avgOrderValue   = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0.0;

$totalCustomers  = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")['c'] ?? 0);
$newCustomers    = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM users WHERE role = 'customer' AND created_at >= NOW() - INTERVAL 30 DAY")['c'] ?? 0);

$pendingPayments = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE payment_status = 'pending' AND order_status <> 'cancelled'")['c'] ?? 0);
$deliveredOrders = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'delivered'")['c'] ?? 0);

/* ------------------------------------------------------------------ *
 *  Sales — last 7 days (zero filled)
 * ------------------------------------------------------------------ */
$salesRows = $db->fetchAll("SELECT DATE(created_at) AS day, SUM(total_amount) AS sales FROM orders WHERE order_status <> 'cancelled' AND created_at >= NOW() - INTERVAL 7 DAY GROUP BY day");
$salesMap  = [];
foreach ($salesRows as $row) {
    $salesMap[$row['day']] = (float)$row['sales'];
}
$sales7Labels = [];
$sales7Data   = [];
for ($i = 6; $i >= 0; $i--) {
    $dayKey = date('Y-m-d', strtotime("-{$i} days"));
    $sales7Labels[] = date('D', strtotime($dayKey));
    $sales7Data[]   = round($salesMap[$dayKey] ?? 0, 2);
}
$weekTotal = array_sum($sales7Data);

/* ------------------------------------------------------------------ *
 *  Order pipeline breakdowns
 * ------------------------------------------------------------------ */
$orderStatusCounts = ['processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
foreach ($db->fetchAll("SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status") as $row) {
    $orderStatusCounts[$row['order_status']] = (int)$row['c'];
}
$orderStatusTotal = max(1, array_sum($orderStatusCounts));

$paymentStatusCounts = ['pending' => 0, 'completed' => 0, 'failed' => 0];
foreach ($db->fetchAll("SELECT payment_status, COUNT(*) AS c FROM orders GROUP BY payment_status") as $row) {
    $paymentStatusCounts[$row['payment_status']] = (int)$row['c'];
}
$paymentStatusTotal = max(1, array_sum($paymentStatusCounts));

/* ------------------------------------------------------------------ *
 *  Lists: top products, low stock, recent customers, recent orders
 * ------------------------------------------------------------------ */
$topProducts = $db->fetchAll("
    SELECT p.id, p.name, p.image, SUM(oi.quantity) AS units, SUM(oi.subtotal) AS revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    WHERE o.order_status <> 'cancelled'
    GROUP BY p.id, p.name, p.image
    ORDER BY units DESC, revenue DESC
    LIMIT 5
");

$lowStockProducts = $db->fetchAll("
    SELECT id, name, image, stock, status
    FROM products
    WHERE stock <= 5
    ORDER BY stock ASC, name ASC
    LIMIT 5
");

$recentCustomers = $db->fetchAll("
    SELECT id, name, email, is_active, created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
    LIMIT 5
");

$recentOrders = $db->fetchAll("
    SELECT o.*, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 6
");

$placeholderThumb = 'https://placehold.co/80x80?text=NA';

$page_title = "Dashboard";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<!-- KPI row -->
<div class="row">
    <!-- Metric 1: Total Revenue -->
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Total Revenue</p>
                        <h4 class="mb-0">$<?= number_format($totalRevenue, 2) ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">weekend</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm"><span class="text-<?= $revenueDelta >= 0 ? 'success' : 'danger' ?> font-weight-bolder"><?= ($revenueDelta >= 0 ? '+' : '') . $revenueDelta ?>% </span>than yesterday ($<?= number_format($todayRevenue, 2) ?> today)</p>
            </div>
        </div>
    </div>

    <!-- Metric 2: Total Orders -->
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Total Orders</p>
                        <h4 class="mb-0"><?= $totalOrders ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">receipt_long</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm"><span class="text-success font-weight-bolder"><?= $activeFulfillments ?> </span>active fulfillments</p>
            </div>
        </div>
    </div>

    <!-- Metric 3: Products Catalog -->
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Products</p>
                        <h4 class="mb-0"><?= $totalProducts ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">inventory_2</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm"><span class="text-<?= $lowStock > 0 ? 'danger' : 'success' ?> font-weight-bolder"><?= $lowStock ?> </span>low on stock (&le;5 units)</p>
            </div>
        </div>
    </div>

    <!-- Metric 4: Categories -->
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Categories</p>
                        <h4 class="mb-0"><?= $totalCategories ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">category</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm"><span class="text-success font-weight-bolder"><?= $activeCategories ?> </span>active right now</p>
            </div>
        </div>
    </div>
</div>
<!-- Secondary KPI row -->
<div class="row">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Avg. Order Value</p>
                        <h4 class="mb-0">$<?= number_format($avgOrderValue, 2) ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-info shadow-info shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">payments</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm">across <span class="font-weight-bolder"><?= $completedOrders ?></span> paid orders</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Customers</p>
                        <h4 class="mb-0"><?= $totalCustomers ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-primary shadow-primary shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">group</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm"><span class="text-success font-weight-bolder">+<?= $newCustomers ?></span> new in last 30 days</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Pending Payments</p>
                        <h4 class="mb-0"><?= $pendingPayments ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-warning shadow-warning shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">hourglass_top</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm">awaiting customer payment</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-sm mb-0 text-capitalize">Delivered Orders</p>
                        <h4 class="mb-0"><?= $deliveredOrders ?></h4>
                    </div>
                    <div class="icon icon-md icon-shape bg-gradient-success shadow-success shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">local_shipping</i>
                    </div>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-2 ps-3">
                <p class="mb-0 text-sm">successfully shipped to customers</p>
            </div>
        </div>
    </div>
</div>
<!-- Sales chart + order pipeline -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header p-3 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Sales &mdash; Last 7 Days</h6>
                        <p class="text-sm mb-0">$<?= number_format($weekTotal, 2) ?> collected this week</p>
                    </div>
                    <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline-dark btn-sm mb-0">Full report</a>
                </div>
            </div>
            <div class="card-body p-3">
                <div style="height: 300px; position: relative;">
                    <canvas id="chart-sales-7d"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header p-3 pb-0">
                <h6 class="mb-0">Order Pipeline</h6>
                <p class="text-sm mb-0"><?= $totalOrders ?> orders in total</p>
            </div>
            <div class="card-body p-3">
                <?php
                $fulfillmentMeta = [
                    'processing' => ['Processing', 'warning'],
                    'shipped'    => ['Shipped', 'info'],
                    'delivered'  => ['Delivered', 'success'],
                    'cancelled'  => ['Cancelled', 'danger'],
                ];
                foreach ($fulfillmentMeta as $key => [$label, $color]):
                    $count = $orderStatusCounts[$key] ?? 0;
                    $pct = (int)round($count / $orderStatusTotal * 100);
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-sm font-weight-bold text-<?= $color ?>"><?= $label ?></span>
                            <span class="text-sm text-secondary"><?= $count ?> &middot; <?= $pct ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-gradient-<?= $color ?>" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <hr class="horizontal dark my-3">

                <h6 class="text-uppercase text-xs font-weight-bolder opacity-7 mb-2">Payment Status</h6>
                <?php
                $paymentMeta = [
                    'completed' => ['Completed', 'success'],
                    'pending'   => ['Pending', 'warning'],
                    'failed'    => ['Failed', 'danger'],
                ];
                foreach ($paymentMeta as $key => [$label, $color]):
                    $count = $paymentStatusCounts[$key] ?? 0;
                    $pct = (int)round($count / $paymentStatusTotal * 100);
                ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-sm text-<?= $color ?> font-weight-bold"><?= $label ?></span>
                        <span class="text-sm text-secondary"><?= $count ?> (<?= $pct ?>%)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<!-- Top products + low stock + recent customers -->
<div class="row">
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header p-3 pb-0">
                <h6 class="mb-0">Top Selling Products</h6>
                <p class="text-sm mb-0">By units sold</p>
            </div>
            <div class="card-body p-3">
                <?php if (empty($topProducts)): ?>
                    <p class="text-sm text-muted mb-0">No product sales recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($topProducts as $tp):
                        $tpThumb = ProductImage::exists((int)$tp['id'], $tp['image']) ? ProductImage::url((int)$tp['id'], ProductImage::first($tp['image'])) : $placeholderThumb;
                    ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= htmlspecialchars($tpThumb) ?>" alt="<?= htmlspecialchars($tp['name']) ?>" style="width: 44px; height: 44px; object-fit: contain; background: #f8f9fa; border-radius: .5rem;" class="me-3">
                            <div class="flex-grow-1 text-truncate">
                                <h6 class="mb-0 text-sm text-truncate"><?= htmlspecialchars($tp['name']) ?></h6>
                                <p class="text-xs text-secondary mb-0"><?= (int)$tp['units'] ?> sold &middot; $<?= number_format((float)$tp['revenue'], 2) ?></p>
                            </div>
                            <a href="<?= APP_URL ?>/admin/products/edit.php?id=<?= (int)$tp['id'] ?>" class="text-secondary"><i class="material-symbols-rounded">chevron_right</i></a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header p-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Low Stock Alerts</h6>
                    <p class="text-sm mb-0">5 units or fewer</p>
                </div>
                <span class="badge badge-sm bg-gradient-<?= $lowStock > 0 ? 'danger' : 'success' ?>"><?= $lowStock ?></span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($lowStockProducts)): ?>
                    <p class="text-sm text-muted mb-0">Every product is well stocked. Nice!</p>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $ls):
                        $lsThumb = ProductImage::exists((int)$ls['id'], $ls['image']) ? ProductImage::url((int)$ls['id'], ProductImage::first($ls['image'])) : $placeholderThumb;
                        $lsBadge = $ls['stock'] <= 0 ? 'danger' : 'warning';
                    ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= htmlspecialchars($lsThumb) ?>" alt="<?= htmlspecialchars($ls['name']) ?>" style="width: 44px; height: 44px; object-fit: contain; background: #f8f9fa; border-radius: .5rem;" class="me-3">
                            <div class="flex-grow-1 text-truncate">
                                <h6 class="mb-0 text-sm text-truncate"><?= htmlspecialchars($ls['name']) ?></h6>
                                <p class="text-xs text-secondary mb-0">
                                    <span class="badge badge-sm bg-gradient-<?= $lsBadge ?>"><?= (int)$ls['stock'] ?> left</span>
                                    <?= $ls['status'] ? '' : ' <span class="text-danger">inactive</span>' ?>
                                </p>
                            </div>
                            <a href="<?= APP_URL ?>/admin/products/edit.php?id=<?= (int)$ls['id'] ?>" class="btn btn-link text-dark btn-sm mb-0 p-1">Restock</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header p-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Newest Customers</h6>
                    <p class="text-sm mb-0">Latest registrations</p>
                </div>
                <a href="<?= APP_URL ?>/admin/users/index.php" class="text-sm">View all</a>
            </div>
            <div class="card-body p-3">
                <?php if (empty($recentCustomers)): ?>
                    <p class="text-sm text-muted mb-0">No customers have registered yet.</p>
                <?php else: ?>
                    <?php foreach ($recentCustomers as $customer):
                        $initial = strtoupper(substr(trim((string)$customer['name']), 0, 1));
                        if ($initial === '') {
                            $initial = '?';
                        }
                    ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon icon-sm icon-shape bg-gradient-dark text-white border-radius-circle text-center me-3" style="border-radius: 50% !important;">
                                <span class="text-white text-sm font-weight-bold" style="line-height: 2.2rem;"><?= htmlspecialchars($initial) ?></span>
                            </div>
                            <div class="flex-grow-1 text-truncate">
                                <h6 class="mb-0 text-sm text-truncate"><?= htmlspecialchars($customer['name']) ?></h6>
                                <p class="text-xs text-secondary mb-0 text-truncate"><?= htmlspecialchars($customer['email']) ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-sm bg-gradient-<?= $customer['is_active'] ? 'success' : 'secondary' ?>"><?= $customer['is_active'] ? 'Active' : 'Inactive' ?></span>
                                <p class="text-xs text-secondary mb-0"><?= date('d M Y', strtotime((string)$customer['created_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Recent orders + quick actions -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100 my-0">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg d-flex justify-content-between align-items-center pt-4 pb-3 px-4">
                    <h6 class="text-white text-capitalize m-0">Recent Orders</h6>
                    <a href="<?= APP_URL ?>/admin/orders/index.php" class="text-white text-sm">View all</a>
                </div>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Order Number</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Payment</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No orders found yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order):
                                    $orderStatusColor = match ($order['order_status']) {
                                        'delivered' => 'success',
                                        'shipped'   => 'info',
                                        'cancelled' => 'danger',
                                        default     => 'warning',
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($order['order_number']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?= date('d M Y', strtotime((string)$order['created_at'])) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold"><?= htmlspecialchars($order['customer_name']) ?></span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="text-secondary text-xs font-weight-bold">$<?= number_format((float)$order['total_amount'], 2) ?></span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $order['payment_status'] === 'completed' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($order['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-<?= $orderStatusColor ?>">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= (int)$order['id'] ?>" class="text-secondary font-weight-bold text-xs">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100 my-0">
            <div class="card-header p-3 pb-2">
                <h6 class="mb-0">Quick Actions</h6>
                <p class="text-sm mb-0">Jump straight into common tasks</p>
            </div>
            <div class="card-body p-3 pt-0">
                <div class="d-grid gap-2">
                    <a href="<?= APP_URL ?>/admin/products/create.php" class="btn btn-outline-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">add_box</i> Add New Product
                    </a>
                    <a href="<?= APP_URL ?>/admin/categories/create.php" class="btn btn-outline-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">create_new_folder</i> Add New Category
                    </a>
                    <a href="<?= APP_URL ?>/admin/orders/index.php" class="btn btn-outline-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">receipt_long</i> Fulfil Orders
                    </a>
                    <a href="<?= APP_URL ?>/admin/users/index.php" class="btn btn-outline-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">group</i> Manage Users
                    </a>
                    <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">bar_chart</i> Sales Reports
                    </a>
                    <a href="<?= APP_URL ?>" target="_blank" class="btn btn-dark btn-sm text-start mb-0">
                        <i class="material-symbols-rounded align-middle me-1">storefront</i> Visit Storefront
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= ADMIN_ASSETS ?>/js/plugins/chartjs.min.js"></script>
<script>
    new Chart(document.getElementById('chart-sales-7d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($sales7Labels) ?>,
            datasets: [{
                label: 'Sales',
                data: <?= json_encode($sales7Data) ?>,
                backgroundColor: 'rgba(52, 71, 103, 0.85)',
                hoverBackgroundColor: 'rgba(52, 71, 103, 1)',
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { callback: value => '$' + value }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?> 
