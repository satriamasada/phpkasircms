<?php
/**
 * POS DATA GENERATOR
 * Digunakan untuk membuat data simulasi transaksi historis (5 Tahun)
 * Cara penggunaan: Jalankan file ini via browser atau CLI
 */

require_once 'includes/functions.php';

// Cek apakah user admin
if (!isLoggedIn() || !in_array('admin', $_SESSION['role_names'] ?? [])) {
    // Jika dijalankan via CLI, abaikan pengecekan session
    if (php_sapi_name() !== 'cli') {
        die("Hanya Administrator yang dapat menjalankan skrip ini.");
    }
}

// Kosongkan data lama sebelum regenerasi agar tidak terjadi duplikasi key
echo "Membersihkan data lama...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE debt_payments;");
$pdo->exec("TRUNCATE TABLE sales_details;");
$pdo->exec("TRUNCATE TABLE sales;");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

$products = $pdo->query("SELECT id, price FROM products")->fetchAll();
$customers = $pdo->query("SELECT id FROM customers")->fetchAll();
$user_id = 1; // ID User Admin default

if (empty($products) || empty($customers)) {
    die("Data Master (Produk/Pelanggan) kosong. Silakan isi data master terlebih dahulu.");
}

echo "Memulai pembuatan data demo historis (5 Tahun ke belakang)...\n";

$end_date = new DateTime();
$start_date = clone $end_date;
$start_date->modify('-5 years');

$current = clone $start_date;
$invoice_count = 1000;
$total_inserted = 0;

while ($current <= $end_date) {
    // Volume transaksi per hari dibuat acak
    // Tahun-tahun lama dibuat lebih sedikit transaksinya dibanding tahun baru (tren naik)
    $years_ago = $end_date->diff($current)->y;
    $max_sales = (5 - $years_ago) * 2 + 2; 
    $num_sales = rand(0, $max_sales);

    for ($i = 0; $i < $num_sales; $i++) {
        $customer = $customers[array_rand($customers)];
        $invoice_no = "INV-" . $current->format('Ymd') . str_pad($invoice_count++, 4, '0', STR_PAD_LEFT);
        
        $created_at = clone $current;
        $created_at->setTime(rand(9, 21), rand(0, 59)); // Jam operasional 09:00 - 21:00
        
        // 20% kemungkinan transaksi piutang (credit)
        $payment_type = (rand(1, 10) > 8) ? 'credit' : 'cash'; 
        
        // Jumlah item per transaksi (1-4 produk)
        $num_items = rand(1, 4);
        $total_amount = 0;
        $sale_items = [];
        
        for ($j = 0; $j < $num_items; $j++) {
            $p = $products[array_rand($products)];
            $qty = rand(1, 3);
            $subtotal = $qty * $p['price'];
            $total_amount += $subtotal;
            $sale_items[] = [
                'id' => $p['id'], 
                'qty' => $qty, 
                'price' => $p['price'], 
                'subtotal' => $subtotal
            ];
        }

        // Insert ke tabel sales
        $stmt = $pdo->prepare("INSERT INTO sales (user_id, customer_id, invoice_no, total_amount, payment_type, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $customer['id'], $invoice_no, $total_amount, $payment_type, $created_at->format('Y-m-d H:i:s')]);
        $sale_id = $pdo->lastInsertId();

        // Insert ke tabel sales_details
        $stmt_d = $pdo->prepare("INSERT INTO sales_details (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($sale_items as $si) {
            $stmt_d->execute([$sale_id, $si['id'], $si['qty'], $si['price'], $si['subtotal']]);
        }
        
        // Jika pembayarannya Kredit, buat beberapa data cicilan (70% kemungkinan bayar sebagian)
        if ($payment_type === 'credit' && rand(1, 10) > 3) {
            $paid = $total_amount * (rand(2, 6) / 10); // Bayar 20% - 60% dimuka
            $stmt_p = $pdo->prepare("INSERT INTO debt_payments (sale_id, amount_paid, payment_date, notes) VALUES (?, ?, ?, ?)");
            $stmt_p->execute([$sale_id, $paid, $created_at->format('Y-m-d H:i:s'), 'Uang muka / Cicilan awal']);
        }
        
        $total_inserted++;
    }
    
    $current->modify('+1 day');
}

echo "Selesai! Berhasil membuat $total_inserted transaksi demo.\n";
