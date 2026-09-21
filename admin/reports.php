<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();

/* ---------- Weekly: sales per day for the last 7 days (zero-filled) ---------- */
$weeklyRows = $db->fetchAll(
    "SELECT DATE(created_at) as day, SUM(total_amount) as sales, COUNT(*) as orders_count
     FROM orders
     WHERE created_at >= NOW() - INTERVAL 7 DAY
     GROUP BY day"
);
$weeklyMap = [];
foreach ($weeklyRows as $row) {
    $weeklyMap[$row['day']] = (float)$row['sales'];
}
$weeklyLabels = [];
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $weeklyLabels[] = date('D', strtotime($date));
    $weeklyData[] = round($weeklyMap[$date] ?? 0, 2);
}
$weekTotal = array_sum($weeklyData);

/* ---------- Monthly: sales per month for the current year (zero-filled) ---------- */
$monthlyRows = $db->fetchAll(
    "SELECT MONTH(created_at) as m, SUM(total_amount) as sales, COUNT(*) as orders_count
     FROM orders
     WHERE YEAR(created_at) = YEAR(CURDATE())
     GROUP BY m"
);
$monthlyMap = [];
$monthlyOrdersMap = [];
foreach ($monthlyRows as $row) {
    $monthlyMap[(int)$row['m']] = (float)$row['sales'];
    $monthlyOrdersMap[(int)$row['m']] = (int)$row['orders_count'];
}
$monthlyLabels = [];
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $monthlyLabels[] = date('M', mktime(0, 0, 0, $m, 1));
    $monthlyData[] = round($monthlyMap[$m] ?? 0, 2);
}
$currentMonth = (int)date('n');
$monthTotal = round($monthlyMap[$currentMonth] ?? 0, 2);
$yearTotal = array_sum($monthlyData);

/* ---------- Yearly: sales per year ---------- */
$yearlyRows = $db->fetchAll(
    "SELECT YEAR(created_at) as y, SUM(total_amount) as sales, COUNT(*) as orders_count
     FROM orders
     GROUP BY y
     ORDER BY y ASC"
);
$yearlyLabels = [];
$yearlyData = [];
foreach ($yearlyRows as $row) {
    $yearlyLabels[] = $row['y'];
    $yearlyData[] = round((float)$row['sales'], 2);
}

$allTime = $db->fetchOne("SELECT SUM(total_amount) as total, COUNT(*) as orders_count FROM orders");
$allTimeTotal = round((float)($allTime['total'] ?? 0), 2);
$allTimeOrders = (int)($allTime['orders_count'] ?? 0);

$page_title = "Sales Reports";
require_once __DIR__ . '/../includes/admin-header.php';

// Summary cards config: label, value, icon, footer text
$cards = [
    ['This Week', $weekTotal, 'date_range', 'last 7 days'],
    ['This Month', $monthTotal, 'calendar_month', date('F Y')],
    ['This Year', $yearTotal, 'bar_chart', date('Y') . ' total'],
    ['All Time', $allTimeTotal, 'paid', $allTimeOrders . ' orders total'],
];
?>

<!-- Summary metric cards -->
<div class="row">
    <?php foreach ($cards as $card): ?>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-header p-2 ps-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-sm mb-0 text-capitalize"><?= $card[0] ?></p>
                            <h4 class="mb-0">$<?= number_format($card[1], 2) ?></h4>
                        </div>
                        <div class="icon icon-md icon-shape bg-gradient-dark shadow-dark shadow text-center border-radius-lg">
                            <i class="material-symbols-rounded opacity-10"><?= $card[2] ?></i>
                        </div>
                    </div>
                </div>
                <hr class="dark horizontal my-0">
                <div class="card-footer p-2 ps-3">
                    <p class="mb-0 text-sm"><span class="text-success font-weight-bolder"><?= $card[3] ?></span></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>


<!-- Sales charts (markup pattern from material-dashboard pages/dashboard.html) -->
<div class="row">
    <div class="col-lg-4 col-md-6 mt-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-0">Weekly Sales</h6>
                <p class="text-sm">Revenue per day — last 7 days</p>
                <div class="pe-2">
                    <div class="chart">
                        <canvas id="chart-weekly" class="chart-canvas" height="170"></canvas>
                    </div>
                </div>
                <hr class="dark horizontal">
                <div class="d-flex">
                    <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                    <p class="mb-0 text-sm">updated just now</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mt-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-0">Monthly Sales</h6>
                <p class="text-sm">Revenue per month — <?= date('Y') ?></p>
                <div class="pe-2">
                    <div class="chart">
                        <canvas id="chart-monthly" class="chart-canvas" height="170"></canvas>
                    </div>
                </div>
                <hr class="dark horizontal">
                <div class="d-flex">
                    <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                    <p class="mb-0 text-sm">updated just now</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mt-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-0">Yearly Sales</h6>
                <p class="text-sm">Revenue per year — all time</p>
                <div class="pe-2">
                    <div class="chart">
                        <canvas id="chart-yearly" class="chart-canvas" height="170"></canvas>
                    </div>
                </div>
                <hr class="dark horizontal">
                <div class="d-flex">
                    <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                    <p class="mb-0 text-sm">updated just now</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly breakdown table -->
<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4">
                    <h6 class="text-white text-capitalize m-0">Monthly Breakdown — <?= date('Y') ?></h6>
                </div>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Month</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Orders</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <tr>
                                    <td><h6 class="mb-0 text-sm ps-3"><?= date('F', mktime(0, 0, 0, $m, 1)) ?></h6></td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary text-xs font-weight-bold"><?= $monthlyOrdersMap[$m] ?? 0 ?></span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary text-xs font-weight-bold">$<?= number_format($monthlyMap[$m] ?? 0, 2) ?></span>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= ADMIN_ASSETS ?>/js/plugins/chartjs.min.js"></script>
<script>
const chartMoney = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: v => '$' + v } },
        x: { grid: { display: false } }
    }
};

new Chart(document.getElementById('chart-weekly'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($weeklyLabels) ?>,
        datasets: [{ label: 'Sales', data: <?= json_encode($weeklyData) ?>, backgroundColor: 'rgba(67, 160, 71, 0.8)', borderRadius: 4, maxBarThickness: 24 }]
    },
    options: chartMoney
});

new Chart(document.getElementById('chart-monthly'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthlyLabels) ?>,
        datasets: [{ label: 'Sales', data: <?= json_encode($monthlyData) ?>, tension: 0.4, borderWidth: 3, pointRadius: 3, borderColor: '#1A73E8', backgroundColor: 'rgba(26, 115, 232, 0.15)', fill: true }]
    },
    options: chartMoney
});

new Chart(document.getElementById('chart-yearly'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($yearlyLabels) ?>,
        datasets: [{ label: 'Sales', data: <?= json_encode($yearlyData) ?>, tension: 0.4, borderWidth: 3, pointRadius: 3, borderColor: '#e91e63', backgroundColor: 'rgba(233, 30, 99, 0.15)', fill: true }]
    },
    options: chartMoney
});
</script>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?>

