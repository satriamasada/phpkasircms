<?php
require_once '../includes/functions.php';
checkLogin();

$sale_id = $_GET['id'] ?? null;
if (!$sale_id) die("Missing ID");

$stmt = $pdo->prepare("SELECT * FROM debt_payments WHERE sale_id = ? ORDER BY payment_date DESC");
$stmt->execute([$sale_id]);
$payments = $stmt->fetchAll();
?>

<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-3">Date</th>
                <th>Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="3" class="text-center py-3 text-muted">No payments recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td class="ps-3 small"><?php echo date('d M Y, H:i', strtotime($p['payment_date'])); ?></td>
                    <td class="fw-bold text-success"><?php echo formatRupiah($p['amount_paid']); ?></td>
                    <td class="small"><?php echo $p['notes']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
