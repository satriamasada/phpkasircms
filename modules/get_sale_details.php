<?php
require_once __DIR__ . '/../includes/functions.php';
checkLogin();

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT sd.*, p.name as product_name, s.invoice_no, b.name as branch_name
    FROM sales_details sd 
    JOIN products p ON sd.product_id = p.id 
    JOIN sales s ON sd.sale_id = s.id
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE sd.sale_id = ?
");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

if (empty($details)) {
    echo '<div class="p-3 text-center">No details found.</div>';
    exit;
}
$branch_name = $details[0]['branch_name'] ?? 'Utama';
?>
<div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="small fw-bold text-muted text-uppercase">Rincian Barang</span>
    <span class="badge bg-primary rounded-pill"><i class="fas fa-store me-1"></i> Cabang: <?php echo $branch_name; ?></span>
</div>
<table class="table mb-0">
    <thead class="bg-body-tertiary small fw-bold">
        <tr>
            <th class="ps-3 py-3">Item</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Price</th>
            <th class="pe-3 text-end">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total = 0;
        foreach ($details as $d): 
            $total += $d['subtotal'];
        ?>
            <tr class="small">
                <td class="ps-3"><?php echo $d['product_name']; ?></td>
                <td class="text-center"><?php echo $d['qty']; ?></td>
                <td class="text-end"><?php echo formatRupiah($d['unit_price']); ?></td>
                <td class="pe-3 text-end fw-medium"><?php echo formatRupiah($d['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="fw-bold bg-body-tertiary">
        <tr>
            <td colspan="3" class="ps-3 py-3">TOTAL TRANSKASI</td>
            <td class="pe-3 text-end text-primary py-3"><?php echo formatRupiah($total); ?></td>
        </tr>
    </tfoot>
</table>
<div class="p-3 bg-transparent border-top text-center small text-muted">
    Data Rincian Penjualan Berhasil Dimuat.
</div>
