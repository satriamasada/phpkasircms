<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('view_reports');

include 'layouts/header.php';
?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* Dark Mode Compatibility for DataTables */
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_length,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_processing,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate {
        color: #adb5bd;
    }
    [data-bs-theme="dark"] .page-link {
        background-color: #2b3035;
        border-color: #495057;
        color: #adb5bd;
    }
    [data-bs-theme="dark"] .active > .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    .dataTables_processing {
        background: rgba(var(--bs-body-bg-rgb), 0.7) !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-radius: 12px;
        font-weight: bold;
    }
    table.dataTable thead th {
        border-bottom: 2px solid var(--bs-border-color) !important;
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
    .card {
        border-radius: 16px;
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h4 class="fw-bold mb-1"><i class="fas fa-file-invoice me-2 text-primary"></i> Laporan Penjualan Premium</h4>
        <div class="text-muted small">Server-side processed via DataTables</div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="salesTable" class="table table-hover align-middle w-100">
                <thead class="bg-body-tertiary">
                    <tr class="text-uppercase small fw-bold text-muted">
                        <th>Tanggal & Waktu</th>
                        <th>No. Invoice</th>
                        <th>Pelanggan</th>
                        <th>Kasir</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loaded via DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-body-tertiary border-0 py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="modalBody">
                <!-- Loaded via AJAX -->
            </div>
            <div class="modal-footer bg-body-tertiary border-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#salesTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "modules/get_sales_dt.php",
            "type": "POST",
            "error": function (xhr, error, thrown) {
                console.error('DataTables Error:', thrown);
                alert('Gagal memuat data dari server. Pastikan Anda sudah login.');
            }
        },
        "order": [[0, "desc"]], // Default sort by date desc
        "language": {
            "processing": '<div class="spinner-border text-primary me-2"></div> Memproses data...',
            "search": "Cari Cepat:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ transaksi",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": '<i class="fas fa-chevron-right"></i>',
                "previous": '<i class="fas fa-chevron-left"></i>'
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Action column not sortable
        ]
    });
});

function viewDetails(saleId, invoice) {
    document.getElementById('modalTitle').innerText = 'Rincian Invoice: #' + invoice;
    document.getElementById('modalBody').innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Sedang mengambil data...</p></div>';
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
    
    fetch('modules/get_sale_details.php?id=' + saleId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('modalBody').innerHTML = '<div class="alert alert-danger m-3">Gagal memuat rincian transaksi.</div>';
        });
}
</script>

<?php include 'layouts/footer.php'; ?>
