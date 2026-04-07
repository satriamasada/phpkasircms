<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_suppliers');

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, address) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $phone, $address])) {
                setFlashMessage("Supplier added successfully!");
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            
            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?");
            if ($stmt->execute([$name, $phone, $address, $id])) {
                setFlashMessage("Supplier updated successfully!");
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            if ($stmt->execute([$id])) {
                setFlashMessage("Supplier deleted successfully!", "danger");
            }
        }
        header("Location: suppliers.php");
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC");
$suppliers = $stmt->fetchAll();

include 'layouts/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Supplier Management</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i> Add Supplier
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No suppliers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td class="ps-4"><?php echo $s['id']; ?></td>
                                <td class="fw-bold"><?php echo $s['name']; ?></td>
                                <td><?php echo $s['phone']; ?></td>
                                <td class="text-muted small"><?php echo $s['address']; ?></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-info me-1" 
                                            onclick='editSupplier(<?php echo json_encode($s); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label">Supplier Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Supplier Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_address').value = data.address;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include 'layouts/footer.php'; ?>
