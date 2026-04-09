<?php
/**
 * KASIRPOS PERSONALIZED INSTALLER
 * Handles .env configuration, custom admin registration, and data selection
 */
session_start();

// 1. Setup minimal environment for installer
$envPath = __DIR__ . '/.env';
$db_file = __DIR__ . '/includes/db.php';
$functions_file = __DIR__ . '/includes/functions.php';
$sql_file = __DIR__ . '/database_final.sql';

// 2. Security Check - If already installed, don't allow access
if (file_exists($functions_file)) {
    require_once $db_file;
    function isInstalledCheck() {
        global $pdo;
        if (!$pdo) return false;
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            return $count > 0;
        } catch (Exception $e) { return false; }
    }
    if (isInstalledCheck()) {
        header('Location: login.php');
        exit;
    }
}

$message = '';
$step = $_GET['step'] ?? 1;

// --- STEP 1: SAVE DB SETTINGS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_db'])) {
    $host = $_POST['db_host'];
    $name = $_POST['db_name'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];

    try {
        $test_pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
        $env_content = "# DATABASE CONFIGURATION\nDB_HOST=$host\nDB_NAME=$name\nDB_USER=$user\nDB_PASS=$pass\n\n# KASIRPOS SUBSCRIPTION CONFIGURATION\nLICENSE_KEY=UFJPRkVTU0lPTkFMfDIwMjctMDQtMTB8NjgwZjU2M2Q=\nSTORE_NAME=\"KasirPOS Deployment\"\n";
        if (file_put_contents($envPath, $env_content)) {
            header('Location: install.php?step=2');
            exit;
        } else { $message = "Gagal menulis file .env. Cek izin tulis folder."; }
    } catch (PDOException $e) { $message = "Koneksi Gagal: " . $e->getMessage(); }
}

// --- STEP 2: RUN CUSTOM INSTALLATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    require_once $db_file;
    global $pdo;
    
    $adm_name = $_POST['admin_fullname'];
    $adm_user = $_POST['admin_username'];
    $adm_pass = $_POST['admin_password'];
    $type     = $_POST['install_type'];

    try {
        if (!file_exists($sql_file)) throw new Exception("File SQL tidak ditemukan!");
        
        $sql = file_get_contents($sql_file);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $queries = explode(';', $sql);
        foreach ($queries as $q) {
            $q = trim($q);
            if (!empty($q)) $pdo->exec($q);
        }

        // --- UPDATE CUSTOM ADMIN (User ID 1) ---
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, password = ? WHERE id = 1");
        $stmt->execute([$adm_name, $adm_user, $adm_pass]);

        // --- HANDLE INSTALLATION TYPE ---
        if ($type === 'clean') {
            // Remove other demo users, products, suppliers, customers
            $pdo->exec("DELETE FROM users WHERE id > 1");
            $pdo->exec("DELETE FROM debt_payments");
            $pdo->exec("DELETE FROM sales_details");
            $pdo->exec("DELETE FROM sales");
            $pdo->exec("DELETE FROM products");
            $pdo->exec("DELETE FROM suppliers");
            $pdo->exec("DELETE FROM customers");
            $message = "Instalasi Bersih Berhasil!";
        } else {
            // RUN DEMO SEEDER (500 Transactions)
            $products   = $pdo->query("SELECT id, price FROM products")->fetchAll(PDO::FETCH_ASSOC);
            $customers  = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_ASSOC);
            $branches   = $pdo->query("SELECT id FROM branches")->fetchAll(PDO::FETCH_ASSOC);
            
            $start = strtotime("-3 years");
            $end = time();
            for ($i = 0; $i < 500; $i++) {
                $b = $branches[array_rand($branches)]['id'];
                $c = $customers[array_rand($customers)]['id'];
                $dt = date('Y-m-d H:i:s', rand($start, $end));
                $inv = "INV-" . date('Ymd-His', strtotime($dt)) . "-$i";
                $amt = 0;
                
                $pdo->prepare("INSERT INTO sales (branch_id, user_id, customer_id, invoice_no, total_amount, payment_type, created_at) VALUES (?, 1, ?, ?, 0, 'cash', ?)")
                    ->execute([$b, $c, $inv, $dt]);
                $sid = $pdo->lastInsertId();
                
                for($j=0; $j<rand(1,4); $j++) {
                    $p = $products[array_rand($products)];
                    $q = rand(1,3); $sub = $p['price'] * $q; $amt += $sub;
                    $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?,?,?,?,?)")
                        ->execute([$sid, $p['id'], $q, $p['price'], $sub]);
                }
                $pdo->prepare("UPDATE sales SET total_amount = ? WHERE id = ?")->execute([$amt, $sid]);
            }
            $message = "Instalasi Demo Berhasil dengan 500 Transaksi!";
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $_SESSION['installed_admin'] = ['user' => $adm_user, 'pass' => $adm_pass];
        header('Location: install.php?step=3');
        exit;
    } catch (Exception $e) { $message = "Instalasi Gagal: " . $e->getMessage(); }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - KasirPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); min-height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .installer-card { border: none; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5); background: #ffffff; }
        .step-dot { width: 40px; height: 40px; border-radius: 12px; background: #f8f9fa; border: 2px solid #e9ecef; display: flex; align-items: center; justify-content: center; z-index: 2; font-weight: bold; color: #adb5bd; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .step-indicator::before { content: ''; position: absolute; top: 20px; left: 0; right: 0; height: 2px; background: #e9ecef; z-index: 1; }
        .step-dot.active { background: #764ba2; border-color: #764ba2; color: #fff; transform: scale(1.15); box-shadow: 0 0 20px rgba(118, 75, 162, 0.4); }
        .step-dot.completed { background: #00b894; border-color: #00b894; color: #fff; }
        .nav-pills-custom .nav-link { border-radius: 15px; padding: 15px; border: 2px solid #f1f3f5; margin-bottom: 10px; transition: all 0.3s; text-align: left; background: #f8f9fa; color: #495057; }
        .nav-pills-custom .nav-link.active { background: #fff; border-color: #764ba2; box-shadow: 0 10px 20px rgba(0,0,0,0.05); color: #764ba2; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card installer-card animate__animated animate__fadeInUp">
                <div class="card-header bg-white p-4 text-center border-0">
                    <div class="mb-2">
                        <i class="fas fa-shopping-basket text-primary" style="font-size: 2.2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-0">KasirPOS Setup</h4>
                    <p class="text-muted small mb-0">Initialization Wizard</p>
                </div>
                
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="step-indicator position-relative d-flex justify-content-between mb-4">
                        <div class="step-dot <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                            <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                        </div>
                        <div class="step-dot <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                            <?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?>
                        </div>
                        <div class="step-dot <?php echo $step == 3 ? 'active completed' : ''; ?>">3</div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-danger border-0 rounded-3 p-2 small mb-3 animate__animated animate__shakeX">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($step == 1): ?>
                        <h6 class="fw-bold mb-3"><i class="fas fa-database me-1 text-primary"></i>Koneksi Database</h6>
                        <form method="POST">
                            <div class="mb-2">
                                <label class="form-label x-small text-uppercase fw-bold text-muted mb-1">Host</label>
                                <input type="text" name="db_host" class="form-control form-control-sm rounded-3" value="localhost" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label x-small text-uppercase fw-bold text-muted mb-1">Nama Database</label>
                                <input type="text" name="db_name" class="form-control form-control-sm rounded-3" value="belajarphpkasir" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label x-small text-uppercase fw-bold text-muted mb-1">User</label>
                                        <input type="text" name="db_user" class="form-control form-control-sm rounded-3" value="root" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <label class="form-label x-small text-uppercase fw-bold text-muted mb-1">Password</label>
                                        <input type="password" name="db_pass" class="form-control form-control-sm rounded-3" placeholder="...">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="save_db" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mt-3 shadow-sm small">
                                Lanjut <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </form>

                    <?php elseif ($step == 2): ?>
                        <form method="POST">
                            <h6 class="fw-bold mb-2 text-uppercase text-primary x-small">1. Kredensial Admin</h6>
                            <div class="mb-2">
                                <input type="text" name="admin_fullname" class="form-control form-control-sm rounded-3" placeholder="Nama Lengkap" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <input type="text" name="admin_username" class="form-control form-control-sm rounded-3" placeholder="User" required>
                                </div>
                                <div class="col-6">
                                    <input type="password" name="admin_password" class="form-control form-control-sm rounded-3" placeholder="Pass" required>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-2 text-uppercase text-primary x-small">2. Opsi Data</h6>
                            <div class="form-check p-0 mb-1">
                                <input class="btn-check" type="radio" name="install_type" id="type_clean" value="clean" checked>
                                <label class="btn btn-outline-secondary w-100 text-start p-2 rounded-3 mb-1" for="type_clean">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-broom me-2 text-muted"></i>
                                        <div>
                                            <div class="small fw-bold">Instalasi Bersih</div>
                                            <div class="x-small opacity-75">Tanpa data demo</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="form-check p-0 mb-3">
                                <input class="btn-check" type="radio" name="install_type" id="type_demo" value="demo">
                                <label class="btn btn-outline-primary w-100 text-start p-2 rounded-3" for="type_demo">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-vial me-2 text-primary"></i>
                                        <div>
                                            <div class="small fw-bold">Instalasi Demo</div>
                                            <div class="x-small opacity-75">+500 transaksi</div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <button type="submit" name="run_install" class="btn btn-dark w-100 rounded-pill py-2 fw-bold shadow-sm small">
                                <i class="fas fa-magic me-1"></i> Instal Sekarang
                            </button>
                        </form>

                    <?php elseif ($step == 3): ?>
                        <div class="text-center">
                            <div class="mb-3">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto shadow" style="width: 70px; height: 70px;">
                                    <i class="fas fa-check fa-2x"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1">Instalasi Selesai!</h5>
                            <p class="text-muted mb-3 x-small">Sistem siap digunakan dengan akun:</p>
                            
                            <div class="bg-light p-3 rounded-3 text-start mb-3 border">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted x-small">Username:</span>
                                    <span class="small fw-bold"><?php echo $_SESSION['installed_admin']['user'] ?? 'admin'; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted x-small">Password:</span>
                                    <span class="small fw-bold"><?php echo $_SESSION['installed_admin']['pass'] ?? '******'; ?></span>
                                </div>
                            </div>

                            <a href="login.php" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow">
                                Konfirmasi & Masuk <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.x-small { font-size: 0.75rem; }
.form-control:focus { box-shadow: 0 0 0 0.25rem rgba(118, 75, 162, 0.15); border-color: #764ba2; }
</style>

</body>
</html>
