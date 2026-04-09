<?php
require_once 'includes/functions.php';
require_once 'includes/functions.php';

// Special access for new installations
$setup_mode = !isSystemInstalled();

if (!$setup_mode) {
    checkLogin();
    requirePermission('manage_rbac'); // Once installed, only admin can access
}

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $sql_file = 'database_final.sql';

    if ($action === 'quick_deploy' || $action === 'reset') {
        // --- DEPLOY / RESET LOGIC ---
        if (!file_exists($sql_file)) {
            $message = "Error: File $sql_file tidak ditemukan!";
            $message_type = 'danger';
        } else {
            try {
                $sql_content = file_get_contents($sql_file);
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                
                // Drop existing tables
                $tables = ['settings', 'debt_payments', 'sales_details', 'sales', 'products', 'customers', 'suppliers', 'user_roles', 'role_permissions', 'users', 'permissions', 'roles', 'branches'];
                foreach ($tables as $table) { $pdo->exec("DROP TABLE IF EXISTS $table"); }
                
                // Execute clean SQL
                $queries = explode(';', $sql_content);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) $pdo->exec($query);
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                
                // If Quick Deploy (Setup Mode), also seed demo data immediately
                if ($action === 'quick_deploy') {
                    // Logic to jump to seeding
                    $_POST['action'] = 'seed';
                    $message = "Database berhasil dibuat. Memulai seeding data demo...";
                } else {
                    $message = "Database berhasil di-reset total ke kondisi awal.";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // Process Seeding (can be called by quick_deploy)
    if (isset($_POST['action']) && $_POST['action'] === 'seed') {
        // --- SEED DEMO DATA LOGIC ---
        try {
            // Fetch Master Data
            $products   = $pdo->query("SELECT id, price FROM products")->fetchAll();
            $customers  = $pdo->query("SELECT id FROM customers")->fetchAll();
            $branches   = $pdo->query("SELECT id FROM branches")->fetchAll();
            $users      = $pdo->query("SELECT id FROM users")->fetchAll();

            if (!empty($products) && !empty($customers) && !empty($branches)) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("TRUNCATE TABLE debt_payments;");
                $pdo->exec("TRUNCATE TABLE sales_details;");
                $pdo->exec("TRUNCATE TABLE sales;");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

                $total_to_generate = 500;
                $years_back = 3;
                $start_timestamp = strtotime("-$years_back years");
                $end_timestamp = time();
                
                $total_inserted = 0;
                for ($i = 0; $i < $total_to_generate; $i++) {
                    $branch    = $branches[array_rand($branches)]['id'];
                    $customer  = $customers[array_rand($customers)]['id'];
                    $user      = $users[array_rand($users)]['id'];
                    $timestamp = rand($start_timestamp, $end_timestamp);
                    $dt        = date('Y-m-d H:i:s', $timestamp);
                    $invoice_no = "INV-" . date('Ymd', $timestamp) . "-" . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
                    $payment_type = (rand(1, 10) > 8) ? 'credit' : 'cash';
                    
                    $num_items = rand(1, 4);
                    $total_amount = 0;
                    $items = [];
                    for ($j = 0; $j < $num_items; $j++) {
                        $p = $products[array_rand($products)];
                        $qty = rand(1, 3); $subtotal = $p['price'] * $qty; $total_amount += $subtotal;
                        $items[] = ['id' => $p['id'], 'qty' => $qty, 'price' => $p['price'], 'sub' => $subtotal];
                    }

                    $stmt = $pdo->prepare("INSERT INTO sales (branch_id, user_id, customer_id, invoice_no, total_amount, payment_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$branch, $user, $customer, $invoice_no, $total_amount, $payment_type, $dt]);
                    $sale_id = $pdo->lastInsertId();

                    $stmt_d = $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
                    foreach ($items as $it) { $stmt_d->execute([$sale_id, $it['id'], $it['qty'], $it['price'], $it['sub']]); }

                    if ($payment_type === 'credit' && rand(1, 10) > 4) {
                        $stmt_p = $pdo->prepare("INSERT INTO debt_payments (sale_id, amount_paid, payment_date, notes) VALUES (?, ?, ?, ?)");
                        $stmt_p->execute([$sale_id, $total_amount * 0.4, $dt, 'Setoran Awal']);
                    }
                    $total_inserted++;
                }
                $message = "Deployment Berhasil! Sistem siap digunakan dengan $total_inserted data demo.";
                $message_type = 'success';
                
                // If it was setup mode, redirect to login after a short delay
                if ($setup_mode) {
                    echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 3000);</script>";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }

    if ($action === 'backup') {
        // --- BACKUP LOGIC ---
        $tables = array();
        $result = $pdo->query('SHOW TABLES');
        while($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $return = "-- POS System Database Backup\n";
        $return .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
        $return .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $return .= "START TRANSACTION;\n\n";
        
        foreach($tables as $table) {
            $result = $pdo->query('SELECT * FROM `'.$table.'`');
            $num_fields = $result->columnCount();
            
            $return .= 'DROP TABLE IF EXISTS `'.$table.'`;';
            $row2 = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM);
            $return .= "\n\n".$row2[1].";\n\n";
            
            while($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $return .= 'INSERT INTO `'.$table.'` VALUES(';
                $idx = 0;
                foreach ($row as $val) {
                    if (is_null($val)) { $return .= 'NULL'; } 
                    else { 
                        $val = addslashes($val);
                        $val = str_replace("\n", "\\n", $val);
                        $return .= '"'.$val.'"' ; 
                    }
                    if ($idx < ($num_fields-1)) { $return .= ','; }
                    $idx++;
                }
                $return .= ");\n";
            }
            $return .= "\n\n";
        }
        
        $return .= "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;";
        
        $filename = 'pos_backup_' . date('Y-m-d_H-i-s') . '.sql';

        // --- SAVE TO SERVER ---
        $backup_dir = __DIR__ . '/backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        file_put_contents($backup_dir . '/' . $filename, $return);

        // Keep only the last 10 backups
        $files = glob($backup_dir . '/*.sql');
        if (is_array($files) && count($files) > 10) {
            $mtimes = array_map('filemtime', $files);
            array_multisort($mtimes, SORT_ASC, $files);
            unlink($files[0]);
        }
        // ----------------------

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $return;
        exit;

    } else if ($action === 'reset') {
        // --- RESET LOGIC ---
        if (!file_exists($sql_file)) {
            $message = "Error: File $sql_file tidak ditemukan!";
            $message_type = 'danger';
        } else {
            try {
                $sql_content = file_get_contents($sql_file);
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                
                // Drop existing tables
                $tables = ['settings', 'debt_payments', 'sales_details', 'sales', 'products', 'customers', 'suppliers', 'user_roles', 'role_permissions', 'users', 'permissions', 'roles', 'branches'];
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS $table");
                }
                
                // Execute clean SQL
                $queries = explode(';', $sql_content);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) $pdo->exec($query);
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                $message = "Database berhasil di-reset total ke kondisi awal (Default).";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } else if ($action === 'seed') {
        // --- SEED DEMO DATA LOGIC ---
        try {
            // Fetch Master Data
            $products   = $pdo->query("SELECT id, price FROM products")->fetchAll();
            $customers  = $pdo->query("SELECT id FROM customers")->fetchAll();
            $branches   = $pdo->query("SELECT id FROM branches")->fetchAll();
            $users      = $pdo->query("SELECT id FROM users")->fetchAll();

            if (empty($products) || empty($customers) || empty($branches)) {
                $message = "Data Master (Cabang/Produk/Pelanggan) kosong. Harap lakukan 'Reset Total' terlebih dahulu.";
                $message_type = 'warning';
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("TRUNCATE TABLE debt_payments;");
                $pdo->exec("TRUNCATE TABLE sales_details;");
                $pdo->exec("TRUNCATE TABLE sales;");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

                $total_to_generate = 500;
                $years_back = 3;
                $start_timestamp = strtotime("-$years_back years");
                $end_timestamp = time();
                
                $total_inserted = 0;
                for ($i = 0; $i < $total_to_generate; $i++) {
                    $branch    = $branches[array_rand($branches)]['id'];
                    $customer  = $customers[array_rand($customers)]['id'];
                    $user      = $users[array_rand($users)]['id'];
                    $timestamp = rand($start_timestamp, $end_timestamp);
                    $dt        = date('Y-m-d H:i:s', $timestamp);
                    $invoice_no = "INV-" . date('Ymd', $timestamp) . "-" . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
                    $payment_type = (rand(1, 10) > 8) ? 'credit' : 'cash';
                    
                    $num_items = rand(1, 4);
                    $total_amount = 0;
                    $items = [];
                    for ($j = 0; $j < $num_items; $j++) {
                        $p = $products[array_rand($products)];
                        $qty = rand(1, 3);
                        $subtotal = $p['price'] * $qty;
                        $total_amount += $subtotal;
                        $items[] = ['id' => $p['id'], 'qty' => $qty, 'price' => $p['price'], 'sub' => $subtotal];
                    }

                    $stmt = $pdo->prepare("INSERT INTO sales (branch_id, user_id, customer_id, invoice_no, total_amount, payment_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$branch, $user, $customer, $invoice_no, $total_amount, $payment_type, $dt]);
                    $sale_id = $pdo->lastInsertId();

                    $stmt_d = $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
                    foreach ($items as $it) { $stmt_d->execute([$sale_id, $it['id'], $it['qty'], $it['price'], $it['sub']]); }

                    if ($payment_type === 'credit' && rand(1, 10) > 4) {
                        $stmt_p = $pdo->prepare("INSERT INTO debt_payments (sale_id, amount_paid, payment_date, notes) VALUES (?, ?, ?, ?)");
                        $stmt_p->execute([$sale_id, $total_amount * 0.4, $dt, 'Setoran Awal']);
                    }
                    $total_inserted++;
                }
                $message = "Berhasil membuat $total_inserted transaksi demo lintas cabang untuk 3 tahun terakhir.";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = "Error saat menanam data: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

include 'layouts/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-white bg-opacity-10 text-white me-3">
                            <i class="fas fa-database"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">Database Management</h4>
                            <p class="mb-0 small text-white-50">Backup, Reset, dan Populasi Data Simulasi</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> shadow-sm border-0 mb-4 animate__animated animate__fadeIn">
                            <i class="fas fa-info-circle me-2"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($setup_mode): ?>
                        <!-- WIZARD MODE (First Time Setup) -->
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <div class="stats-icon bg-primary text-white mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <h3 class="fw-bold">Selamat Datang di KasirPOS</h3>
                                <p class="text-muted">Sistem belum terinstal. Mari siapkan segalanya dalam satu langkah mudah.</p>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="p-4 bg-light rounded-4 border mb-4 text-start">
                                        <h6 class="fw-bold mb-3">Apa yang akan dilakukan:</h6>
                                        <ul class="small text-muted mb-0">
                                            <li>Inisialisasi tabel database (Master Schema)</li>
                                            <li>Setting akun administrator awal (admin/admin123)</li>
                                            <li>Menanamkan 500 data transaksi demo untuk simulasi</li>
                                            <li>Konfigurasi multi-cabang default</li>
                                        </ul>
                                    </div>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="quick_deploy">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow">
                                            <i class="fas fa-magic me-2"></i> Auto Setup & Mulai Sekarang
                                        </button>
                                    </form>
                                    <p class="mt-3 small text-muted">Proses ini memakan waktu sekitar 10-20 detik.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- MAINTENANCE MODE (Standard View) -->
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card h-100 border-dashed p-4 text-center bg-light bg-opacity-50 border-primary">
                                    <div class="mb-3 text-primary fs-1">
                                        <i class="fas fa-file-download"></i>
                                    </div>
                                    <h6 class="fw-bold">Backup Database</h6>
                                    <p class="small text-muted mb-4">Unduh salinan data transaksi & pengaturan saat ini (.sql).</p>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="backup">
                                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 small">Unduh Backup</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-dashed p-4 text-center bg-light bg-opacity-50 border-danger border-opacity-25 shadow-sm">
                                    <div class="mb-3 text-danger fs-1">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                    <h6 class="fw-bold text-danger">Reset Total</h6>
                                    <p class="small text-muted mb-4">Hapus seluruh data dan buat database nol (Default Master).</p>
                                    <form method="POST" onsubmit="return confirm('PERINGATAN: Hapus SELURUH DATA. Anda disarankan BACKUP dulu. Lanjutkan?')">
                                        <input type="hidden" name="action" value="reset">
                                        <button type="submit" class="btn btn-outline-danger w-100 rounded-pill py-2 small">Reset Total</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-dashed p-4 text-center bg-light bg-opacity-50 border-success border-opacity-25">
                                    <div class="mb-3 text-success fs-1">
                                        <i class="fas fa-vial"></i>
                                    </div>
                                    <h6 class="fw-bold text-success">Isi Data Demo</h6>
                                    <p class="small text-muted mb-4">Tanam 500 transaksi acak lintas cabang (Histori 3 Tahun).</p>
                                    <form method="POST" onsubmit="return confirm('Tanam 500 data demo baru? Data transaksi lama akan dibersihkan.')">
                                        <input type="hidden" name="action" value="seed">
                                        <button type="submit" class="btn btn-success w-100 rounded-pill py-2 small">Generate Demo</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$setup_mode): ?>
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-link link-secondary text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-dashed {
    border: 2px dashed #dee2e6;
    transition: all 0.3s ease;
    border-radius: 1.5rem;
}
.border-dashed:hover {
    border-color: #764ba2;
    background-color: #fff !important;
}
.stats-icon {
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 12px;
}
</style>

<?php include 'layouts/footer.php'; ?>
