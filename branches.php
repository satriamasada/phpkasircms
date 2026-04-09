<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_branches');

// Handle Actions (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $address = $_POST['address'];
            $phone = $_POST['phone'];
            
            if (!checkLicenseLimit('branches')) {
                setFlashMessage("Branch limit reached for your subscription!", "danger");
            } else {
                $stmt = $pdo->prepare("INSERT INTO branches (name, address, phone) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $address, $phone])) {
                    setFlashMessage("Branch added successfully!");
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $address = $_POST['address'];
            $phone = $_POST['phone'];
            
            $stmt = $pdo->prepare("UPDATE branches SET name = ?, address = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$name, $address, $phone, $id])) {
                setFlashMessage("Branch updated successfully!");
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            // Prevent deleting the last branch
            $count = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
            if ($count > 1) {
                $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
                if ($stmt->execute([$id])) {
                    setFlashMessage("Branch deleted successfully!");
                }
            } else {
                setFlashMessage("Cannot delete the only branch!", "warning");
            }
        }
    }
    header("Location: branches.php");
    exit;
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY name ASC")->fetchAll();
$info = getSubscriptionInfo();

include 'layouts/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h4 class="fw-bold mb-1"><i class="fas fa-store me-2 text-primary"></i> Toko & Cabang</h4>
        <div class="text-muted small">Kelola unit bisnis Anda sesuai limit paket <strong><?php echo $info['tier']; ?></strong></div>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <button class="btn btn-outline-secondary btn-sm disabled" style="opacity: 1;">
                Limit: <?php echo count($branches); ?> / <?php echo $info['branches']; ?>
            </button>
            <?php if (checkLicenseLimit('branches')): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-1"></i> Tambah Cabang
                </button>
            <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled>
                    <i class="fas fa-lock me-1"></i> Limit Cabang (Paket Standard)
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo displayFlashMessage(); ?>

<div class="row g-4">
    <?php foreach ($branches as $b): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-3">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link link-dark text-decoration-none py-0" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="#" onclick='editBranch(<?php echo json_encode($b); ?>)'>Edit Detail</a></li>
                            <?php if (count($branches) > 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $b['id']; ?>, '<?php echo addslashes($b['name']); ?>')">Hapus Cabang</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <h5 class="fw-bold mb-1"><?php echo $b['name']; ?></h5>
                <p class="text-muted small mb-3"><?php echo $b['address'] ?: 'Alamat belum diset'; ?></p>
                <div class="d-flex align-items-center text-primary small">
                    <i class="fas fa-phone-alt me-2"></i> <?php echo $b['phone'] ?: '-'; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow-lg" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Cabang Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Cabang</label>
                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Cabang Bandung">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alamat</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Telepon</label>
                    <input type="text" name="phone" class="form-control" placeholder="021-xxxxxx">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill">Simpan Cabang</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow-lg" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Edit Detail Cabang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Cabang</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alamat</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Telepon</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-dark px-4 rounded-pill">Update Cabang</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editBranch(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_address').value = data.address;
    document.getElementById('edit_phone').value = data.phone;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus cabang "' + name + '"? Semua data yang terkait mungkin akan terdampak.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'layouts/footer.php'; ?>
