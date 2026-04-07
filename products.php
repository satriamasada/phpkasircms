<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_products');

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            $cost_price = $_POST['cost_price'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];
            $unit = $_POST['unit'];
            
            $stmt = $pdo->prepare("INSERT INTO products (name, supplier_id, cost_price, price, stock, unit) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $supplier_id, $cost_price, $price, $stock, $unit])) {
                setFlashMessage("Product added successfully!");
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
            $cost_price = $_POST['cost_price'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];
            $unit = $_POST['unit'];
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, supplier_id = ?, cost_price = ?, price = ?, stock = ?, unit = ? WHERE id = ?");
            if ($stmt->execute([$name, $supplier_id, $cost_price, $price, $stock, $unit, $id])) {
                setFlashMessage("Product updated successfully!");
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$id])) {
                setFlashMessage("Product deleted successfully!", "danger");
            }
        }
        header("Location: products.php");
        exit;
    }
}

// Fetch Products with Supplier Info
$stmt = $pdo->query("
    SELECT p.*, s.name as supplier_name 
    FROM products p 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

// Fetch Suppliers for dropdown
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

include 'layouts/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Product Inventory</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i> Add Product
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Product</th>
                        <th>Supplier</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Profit / unit</th>
                        <th>Stock</th>
                        <th>Unit</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No products found. Add your first product!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $p['name']; ?></div>
                                    <small class="text-muted">ID: #<?php echo $p['id']; ?></small>
                                </td>
                                <td><?php echo $p['supplier_name'] ?? '<span class="text-muted">None</span>'; ?></td>
                                <td class="text-muted small"><?php echo formatRupiah($p['cost_price']); ?></td>
                                <td class="fw-medium text-primary"><?php echo formatRupiah($p['price']); ?></td>
                                <td class="text-success small fw-bold">+<?php echo formatRupiah($p['price'] - $p['cost_price']); ?></td>
                                <td>
                                    <?php if ($p['stock'] <= 5): ?>
                                        <span class="badge bg-danger"><?php echo $p['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success"><?php echo $p['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $p['unit']; ?></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-info me-1" 
                                            onclick='editProduct(<?php echo json_encode($p); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Arabica Coffee" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">-- No Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (Modal)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="cost_price" class="form-control" value="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (Jual)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="price" class="form-control" value="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <option value="pcs">Pcs (Pieces)</option>
                        <option value="kg">Kg (Kilogram)</option>
                        <option value="box">Box</option>
                        <option value="btl">Btl (Bottle)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" id="edit_supplier_id" class="form-select">
                        <option value="">-- No Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price (Modal)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="cost_price" id="edit_cost_price" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Selling Price (Jual)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" id="edit_stock" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <select name="unit" id="edit_unit" class="form-select">
                        <option value="pcs">Pcs (Pieces)</option>
                        <option value="kg">Kg (Kilogram)</option>
                        <option value="box">Box</option>
                        <option value="btl">Btl (Bottle)</option>
                    </select>
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
function editProduct(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_supplier_id').value = data.supplier_id || '';
    document.getElementById('edit_cost_price').value = data.cost_price;
    document.getElementById('edit_price').value = data.price;
    document.getElementById('edit_stock').value = data.stock;
    document.getElementById('edit_unit').value = data.unit;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include 'layouts/footer.php'; ?>
