<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('access_pos'); // Assuming pos permission is enough or add manage_debts

// Action handling for payments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_debt') {
    $sale_id = $_POST['sale_id'];
    $amount = $_POST['amount'];
    $notes = $_POST['notes'] ?? '';
    
    if ($amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO debt_payments (sale_id, amount_paid, notes) VALUES (?, ?, ?)");
        if ($stmt->execute([$sale_id, $amount, $notes])) {
            setFlashMessage("Pembayaran piutang sebesar " . formatRupiah($amount) . " berhasil dicatat.");
        }
    }
    header("Location: debts.php");
    exit;
}

include 'layouts/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* Premium Styling for Debt Table */
    .debt-card {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_length,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_processing,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate {
        color: var(--text-muted);
    }
    .dataTables_processing {
        background: rgba(var(--bs-body-bg-rgb), 0.8) !important;
        backdrop-filter: blur(5px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        z-index: 100 !important;
    }
    table.dataTable thead th {
        padding: 15px 12px !important;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border-color) !important;
    }
    .badge-paid {
        background-color: #d1fae5;
        color: #065f46;
    }
    .badge-pending {
        background-color: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h4 class="fw-bold mb-1"><i class="fas fa-hand-holding-usd me-2 text-primary"></i> Manajemen Hutang Piutang</h4>
        <div class="text-muted small">Pantau dan kelola angsuran piutang pelanggan secara real-time</div>
    </div>
</div>

<div class="card debt-card shadow-sm mb-5">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="debtsTable" class="table table-hover align-middle w-100">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase text-muted">
                        <th>Tgl Transaksi</th>
                        <th>No. Invoice</th>
                        <th>Pelanggan</th>
                        <th>Total Tagihan</th>
                        <th>Telah Dibayar</th>
                        <th>Sisa Piutang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Server-side loaded -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pay Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-cash-register me-2 text-success"></i> Catat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="pay_debt">
                <input type="hidden" name="sale_id" id="pay_sale_id">
                
                <div class="p-3 bg-primary bg-opacity-10 text-primary border-0 rounded-4 mb-4">
                    <div class="small fw-bold opacity-75">Invoice: <span id="pay_invoice_no"></span></div>
                    <div class="h4 mb-0 fw-bold">Sisa Hutang: <span id="pay_remaining_text"></span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Jumlah Bayar</label>
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-transparent ps-0 h4">Rp</span>
                        <input type="number" name="amount" id="pay_amount" class="form-control form-control-lg border-0 border-bottom rounded-0 shadow-none px-0 h4" required step="0.01">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Contoh: Titipan/Cicilan 1"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-body-tertiary border-0 py-3">
                <h5 class="modal-title fw-bold" id="historyTitle">Riwayat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="historyBody">
                <!-- Loaded via AJAX -->
            </div>
            <div class="modal-footer bg-body-tertiary border-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS & Dependencies (jQuery already in header) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#debtsTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "modules/get_debts_dt.php",
            "type": "POST",
            "error": function() {
                alert('Gagal mengambil data piutang. Pastikan koneksi stabil.');
            }
        },
        "order": [[0, "desc"]],
        "language": {
            "processing": '<div class="spinner-border text-primary me-2"></div> Memuat data piutang...',
            "search": "Cari Invoice/Pelanggan:",
            "lengthMenu": "_MENU_ data per hal",
            "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ piutang",
            "paginate": {
                "next": '<i class="fas fa-chevron-right"></i>',
                "previous": '<i class="fas fa-chevron-left"></i>'
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});

function openPayModal(saleId, remaining, invoice) {
    $('#pay_sale_id').val(saleId);
    $('#pay_invoice_no').text(invoice);
    $('#pay_remaining_text').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(remaining));
    $('#pay_amount').val(remaining).attr('max', remaining);
    new bootstrap.Modal(document.getElementById('payModal')).show();
}

function viewHistory(saleId, invoice) {
    $('#historyTitle').text('Riwayat Pembayaran: #' + invoice);
    $('#historyBody').html('<div class="p-5 text-center"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Mengambil data riwayat...</p></div>');
    new bootstrap.Modal(document.getElementById('historyModal')).show();
    
    fetch('modules/get_debt_history.php?id=' + saleId)
        .then(response => response.text())
        .then(html => {
            $('#historyBody').html(html);
        })
        .catch(err => {
            $('#historyBody').html('<div class="alert alert-danger m-3">Gagal memuat riwayat pembayaran.</div>');
        });
}
</script>

<?php include 'layouts/footer.php'; ?>
