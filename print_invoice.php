<?php
require_once 'includes/functions.php';
checkLogin();

$id = $_GET['id'] ?? null;
if (!$id) die("Missing sale ID");

// Fetch sale data with branch info
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, u.fullname as cashier_name, 
           b.name as branch_name, b.address as branch_address, b.phone as branch_phone
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) die("Sale not found");

// Fetch items
$stmt = $pdo->prepare("
    SELECT sd.*, p.name as product_name
    FROM sales_details sd
    JOIN products p ON sd.product_id = p.id
    WHERE sd.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $sale['invoice_no']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; width: 300px; margin: 0 auto; color: #000; line-height: 1.4; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .header { margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 2px 0; font-size: 15px; }
        .header p { margin: 0; font-size: 11px; }
        .item-row { margin-bottom: 5px; }
        .footer { margin-top: 20px; font-size: 11px; }
        @media print {
            .no-print { display: none; }
            body { width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()">Print Invoice</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="header text-center">
        <h1><?php echo strtoupper(getSetting('app_name', 'POS PREMIUM SYSTEM')); ?></h1>
        <p>Sistem Kasir v2 - Digital Receipt</p>
    </div>

    <div class="divider"></div>

    <table width="100%">
        <tr>
            <td width="35%">Invoice:</td>
            <td class="text-right text-bold">#<?php echo $sale['invoice_no']; ?></td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="text-right"><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
        </tr>
        <tr>
            <td>Cashier:</td>
            <td class="text-right"><?php echo $sale['cashier_name']; ?></td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td class="text-right"><?php echo $sale['customer_name'] ?? 'Umum'; ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table width="100%" style="font-size: 11px; color: #333;">
        <tr>
            <td width="35%">Toko/Cabang:</td>
            <td class="text-right"><?php echo $sale['branch_name'] ?? 'Pusat'; ?></td>
        </tr>
        <tr>
            <td>Alamat:</td>
            <td class="text-right"><?php echo $sale['branch_address'] ?? '-'; ?></td>
        </tr>
        <?php if ($sale['branch_phone']): ?>
        <tr>
            <td>Kontak:</td>
            <td class="text-right"><?php echo $sale['branch_phone']; ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="divider"></div>

    <table width="100%">
        <?php foreach ($items as $item): ?>
        <tr class="item-row">
            <td colspan="2"><?php echo $item['product_name']; ?></td>
        </tr>
        <tr>
            <td><?php echo $item['qty']; ?> x <?php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
            <td class="text-right"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="divider"></div>

    <table width="100%" style="font-weight: bold;">
        <tr>
            <td>TOTAL</td>
            <td class="text-right text-large">Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>PAYMENT</td>
            <td class="text-right text-uppercase"><?php echo $sale['payment_type'] == 'credit' ? 'PIUTANG' : $sale['payment_type']; ?></td>
        </tr>
    </table>

    <?php if ($sale['payment_type'] == 'credit'): 
        $stmt_p = $pdo->prepare("SELECT SUM(amount_paid) FROM debt_payments WHERE sale_id = ?");
        $stmt_p->execute([$id]);
        $total_paid = $stmt_p->fetchColumn() ?? 0;
        $remaining = $sale['total_amount'] - $total_paid;
    ?>
    <div class="divider"></div>
    <table width="100%">
        <tr>
            <td>TOTAL PAID:</td>
            <td class="text-right">Rp <?php echo number_format($total_paid, 0, ',', '.'); ?></td>
        </tr>
        <tr style="color: #d63031;">
            <td>REMAINING:</td>
            <td class="text-right">Rp <?php echo number_format($remaining, 0, ',', '.'); ?></td>
        </tr>
    </table>
    <?php endif; ?>

    <div class="divider"></div>

    <div class="footer text-center">
        <p>Terima Kasih Atas Kunjungan Anda</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan</p>
    </div>

</body>
</html>
