<?php
require_once 'includes/functions.php';
checkLogin();

// Basic Auth & Branch Scope
$active_branch_id = $_SESSION['active_branch_id'] ?? 1;
$branch_filter = " WHERE branch_id = $active_branch_id ";

// 1. Basic Counts
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); // Products are global usually, or we can filter if needed. Let's keep global for now.
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_sales_count = $pdo->query("SELECT COUNT(*) FROM sales $branch_filter")->fetchColumn();

// 2. Revenue Analytics
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM sales $branch_filter")->fetchColumn() ?? 0;

// 3. Debt (Piutang) Analytics
$total_credit_sales = $pdo->query("SELECT SUM(total_amount) FROM sales $branch_filter AND payment_type = 'credit'")->fetchColumn() ?? 0;
$total_debt_paid = $pdo->query("SELECT SUM(dp.amount_paid) FROM debt_payments dp JOIN sales s ON dp.sale_id = s.id WHERE s.branch_id = $active_branch_id")->fetchColumn() ?? 0;
$total_piutang = $total_credit_sales - $total_debt_paid;

// 4. Gross Profit (Laba Bruto)
$gross_profit = $pdo->query("
    SELECT SUM(sd.qty * (sd.unit_price - p.cost_price)) 
    FROM sales_details sd 
    JOIN products p ON sd.product_id = p.id
    JOIN sales s ON sd.sale_id = s.id
    WHERE s.branch_id = $active_branch_id
")->fetchColumn() ?? 0;

// 5. Net Income (CASH only estimation + debt payments)
$cash_sales = $pdo->query("SELECT SUM(total_amount) FROM sales $branch_filter AND payment_type != 'credit'")->fetchColumn() ?? 0;
$total_cash_in = $cash_sales + $total_debt_paid;

// 6. Chart Data - Monthly Revenue (Current Year)
$monthly_stmt = $pdo->query("
    SELECT 
        MONTH(created_at) as month, 
        SUM(total_amount) as revenue,
        SUM(total_amount * 0.3) as profit -- Using 30% margin as avg for chart
    FROM sales 
    WHERE YEAR(created_at) = YEAR(CURDATE()) AND branch_id = $active_branch_id
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
");
$monthly_data = array_fill(1, 12, ['revenue' => 0, 'profit' => 0]);
while ($row = $monthly_stmt->fetch()) {
    $monthly_data[$row['month']] = ['revenue' => $row['revenue'], 'profit' => $row['profit']];
}

// 7. Chart Data - Yearly Revenue (5 Years)
$yearly_stmt = $pdo->query("
    SELECT 
        YEAR(created_at) as year, 
        SUM(total_amount) as revenue
    FROM sales 
    WHERE branch_id = $active_branch_id
    GROUP BY YEAR(created_at)
    ORDER BY YEAR(created_at) DESC
    LIMIT 5
");
$yearly_data = array_reverse($yearly_stmt->fetchAll());

include 'layouts/header.php';
?>

<!-- Script for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Enhanced Analytic Header -->
<div class="row g-4 mb-4">
    <!-- Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Total Pendapatan</h6>
                </div>
                <h3 class="card-title mb-1 fw-bold"><?php echo formatRupiah($total_revenue); ?></h3>
                <small class="text-muted">Total nilai seluruh invoice</small>
            </div>
        </div>
    </div>

    <!-- Gross Profit -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card h-100 border-start border-success border-4 <?php echo !hasFullReports() ? 'locked-feature' : ''; ?>">
            <?php if (!hasFullReports()): ?>
                <div class="lock-overlay"><i class="fas fa-lock me-2"></i> Professional Only</div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Laba Bruto</h6>
                </div>
                <h3 class="card-title mb-1 fw-bold text-success"><?php echo formatRupiah($gross_profit); ?></h3>
                <small class="text-muted">Estimasi keuntungan kotor</small>
            </div>
        </div>
    </div>

    <!-- Piutang (Debt) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Total Piutang</h6>
                </div>
                <h3 class="card-title mb-1 fw-bold text-danger"><?php echo formatRupiah($total_piutang); ?></h3>
                <small class="text-muted">Hutang pelanggan belum lunas</small>
            </div>
        </div>
    </div>

    <!-- Cash In -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Kas Diterima</h6>
                </div>
                <h3 class="card-title mb-1 fw-bold text-info"><?php echo formatRupiah($total_cash_in); ?></h3>
                <small class="text-muted">Total uang tunai masuk</small>
            </div>
        </div>
    </div>
</div>

<!-- Analytics Charts -->
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100 <?php echo !hasFullReports() ? 'locked-feature' : ''; ?>">
            <?php if (!hasFullReports()): ?>
                <div class="lock-overlay" style="padding: 15px 30px;"><i class="fas fa-lock me-2"></i> Laporan Laba/Rugi Khusus Paket Professional</div>
            <?php endif; ?>
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-area me-2 text-primary"></i> Penjualan & Laba Bulanan (<?php echo date('Y'); ?>)</h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-info"></i> Perbandingan Tahunan</h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="yearlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Chart
    const ctxMonthly = document.getElementById('monthlyChart');
    if (ctxMonthly) {
        const monthlyData = <?php echo json_encode(array_values($monthly_data)); ?>;
        new Chart(ctxMonthly, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: monthlyData.map(d => d.revenue || 0),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Laba (Est 30%)',
                        data: monthlyData.map(d => d.profit || 0),
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => 'Rp ' + value.toLocaleString() }
                    }
                }
            }
        });
    }

    // 2. Yearly Chart
    const ctxYearly = document.getElementById('yearlyChart');
    if (ctxYearly) {
        const yearlyRaw = <?php echo json_encode($yearly_data); ?>;
        new Chart(ctxYearly, {
            type: 'bar',
            data: {
                labels: yearlyRaw.map(d => d.year),
                datasets: [{
                    label: 'Revenue',
                    data: yearlyRaw.map(d => d.revenue || 0),
                    backgroundColor: 'rgba(56, 178, 172, 0.7)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => 'Rp ' + value.toLocaleString() }
                    }
                }
            }
        });
    }
});
</script>

<div class="row g-4">
    <!-- Summary Counts -->
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Total Produk</div>
                    <div class="fw-bold fs-5"><?php echo $total_products; ?></div>
                </div>
                <i class="fas fa-box text-muted opacity-50"></i>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Total Pelanggan</div>
                    <div class="fw-bold fs-5"><?php echo $total_customers; ?></div>
                </div>
                <i class="fas fa-users text-muted opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center" style="min-height: 58px;">
                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i> Transaksi Terakhir</h6>
                <a href="reports.php" class="btn btn-sm btn-link text-decoration-none py-0">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary">
                            <tr class="small text-uppercase text-muted">
                                <th class="ps-4">Invoice</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th class="pe-4 text-end">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_stmt = $pdo->query("SELECT * FROM sales WHERE branch_id = $active_branch_id ORDER BY created_at DESC LIMIT 5");
                            while($rs = $recent_stmt->fetch()):
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-primary small">#<?php echo $rs['invoice_no']; ?></div>
                                </td>
                                <td class="fw-bold small"><?php echo formatRupiah($rs['total_amount']); ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?php echo $rs['payment_type'] == 'credit' ? 'warning text-dark' : 'secondary'; ?> opacity-75 small">
                                        <?php echo strtoupper($rs['payment_type']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end text-muted x-small"><?php echo date('H:i', strtotime($rs['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center" style="min-height: 58px;">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2 text-warning"></i> Stok Menipis</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    $low_stmt = $pdo->query("SELECT name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 6");
                    while($ls = $low_stmt->fetch()):
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3">
                        <span class="small text-truncate" style="max-width: 150px;"><?php echo $ls['name']; ?></span>
                        <span class="badge bg-danger rounded-pill"><?php echo $ls['stock']; ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
