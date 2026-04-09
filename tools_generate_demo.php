<?php
/**
 * POS DATA GENERATOR v2
 * Digunakan untuk membuat data simulasi transaksi historis
 * Mendukung multi-cabang dan volume data kustom.
 */

require_once 'includes/functions.php';

// --- CONFIGURATION ---
$years_back = isset($_GET['years']) ? (int)$_GET['years'] : 3; // Default 3 tahun
$total_to_generate = isset($_GET['count']) ? (int)$_GET['count'] : 500; // Default 500 transaksi
// ---------------------

// Cek apakah user admin
if (!isLoggedIn() || !in_array('admin', $_SESSION['role_names'] ?? [])) {
    if (php_sapi_name() !== 'cli') {
        die("Hanya Administrator yang dapat menjalankan skrip ini.");
    }
}

echo "Memulai sinkronisasi data demo ($years_back Tahun, $total_to_generate Transaksi)...\n";

// Fetch Master Data
$products   = $pdo->query("SELECT id, price, cost_price FROM products")->fetchAll();
$customers  = $pdo->query("SELECT id FROM customers")->fetchAll();
$branches   = $pdo->query("SELECT id FROM branches")->fetchAll();
$users      = $pdo->query("SELECT id FROM users")->fetchAll();

if (empty($products) || empty($customers) || empty($branches)) {
    die("Data Master (Produk/Pelanggan/Cabang) kosong. Silakan isi data master terlebih dahulu.");
}

// Clear old demo data
echo "Membersihkan data transaksi lama...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE debt_payments;");
$pdo->exec("TRUNCATE TABLE sales_details;");
$pdo->exec("TRUNCATE TABLE sales;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

$start_timestamp = strtotime("-$years_back years");
$end_timestamp   = time();

$total_inserted = 0;
for ($i = 0; $i < $total_to_generate; $i++) {
    // Randomize Data
    $branch    = $branches[array_rand($branches)]['id'];
    $customer  = $customers[array_rand($customers)]['id'];
    $user      = $users[array_rand($users)]['id'];
    $timestamp = rand($start_timestamp, $end_timestamp);
    $dt        = date('Y-m-d H:i:s', $timestamp);
    $invoice_no = "INV-" . date('Ymd', $timestamp) . "-" . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
    
    // Payment Type: 80% cash, 20% credit
    $payment_type = (rand(1, 10) > 8) ? 'credit' : 'cash';
    
    // Items: 1-5 random items
    $num_items = rand(1, 5);
    $total_amount = 0;
    $items_to_insert = [];
    
    for ($j = 0; $j < $num_items; $j++) {
        $p = $products[array_rand($products)];
        $qty = rand(1, 5);
        $subtotal = $p['price'] * $qty;
        $total_amount += $subtotal;
        $items_to_insert[] = [
            'product_id' => $p['id'],
            'qty' => $qty,
            'price' => $p['price'],
            'subtotal' => $subtotal
        ];
    }

    try {
        // Insert Sale
        $stmt = $pdo->prepare("INSERT INTO sales (branch_id, user_id, customer_id, invoice_no, total_amount, payment_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch, $user, $customer, $invoice_no, $total_amount, $payment_type, $dt]);
        $sale_id = $pdo->lastInsertId();

        // Insert Details
        $stmt_d = $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items_to_insert as $item) {
            $stmt_d->execute([$sale_id, $item['product_id'], $item['qty'], $item['price'], $item['subtotal']]);
        }

        // Debt Logic
        if ($payment_type === 'credit') {
            // Random partial payment
            $down_payment = (rand(1, 10) > 5) ? ($total_amount * 0.5) : 0;
            if ($down_payment > 0) {
                $stmt_p = $pdo->prepare("INSERT INTO debt_payments (sale_id, amount_paid, payment_date, notes) VALUES (?, ?, ?, ?)");
                $stmt_p->execute([$sale_id, $down_payment, $dt, 'Setoran Awal']);
            }
        }
        
        $total_inserted++;
    } catch (Exception $e) {
        // Skip on duplicate invoice or other error
        continue;
    }
}

echo "Selesai! Berhasil membuat $total_inserted transaksi demo lintas cabang.\n";
echo "Parameter yang digunakan: years=$years_back, count=$total_to_generate\n";
echo "Gunakan URL: tools_generate_demo.php?years=5&count=1000 untuk kustomisasi.\n";
