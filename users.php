<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_users');

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = $_POST['username'];
            $fullname = $_POST['fullname'];
            $password = $_POST['password'];
            $role_ids = $_POST['role_ids'] ?? [];
            
            if (!checkLicenseLimit('users')) {
                setFlashMessage("User limit reached for your subscription!", "danger");
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $password, $fullname])) {
                    $user_id = $pdo->lastInsertId();
                    foreach($role_ids as $rid) {
                        $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$user_id, $rid]);
                    }
                    setFlashMessage("User created successfully!");
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $username = $_POST['username'];
            $fullname = $_POST['fullname'];
            $role_ids = $_POST['role_ids'] ?? [];
            
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $fullname, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ? WHERE id = ?");
                $stmt->execute([$username, $fullname, $id]);
            }
            
            // Sync roles
            $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$id]);
            foreach($role_ids as $rid) {
                $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$id, $rid]);
            }
            
            setFlashMessage("User updated successfully!");
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            if ($id == $_SESSION['user_id']) {
                setFlashMessage("You cannot delete yourself!", "danger");
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage("User deleted successfully!", "danger");
            }
        }
        header("Location: users.php");
        exit;
    }
}

// Fetch users with their roles
$stmt = $pdo->query("
    SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as role_names, GROUP_CONCAT(r.id) as role_ids
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    GROUP BY u.id
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

include 'layouts/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">User Management</h5>
        <?php if (checkLicenseLimit('users')): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i> Tambah User
            </button>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-lock me-2"></i> Limit Akun (Max 2 Paket Standard)
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Full Name</th>
                        <th>Username</th>
                        <th>Roles</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo $u['fullname']; ?></div>
                                <small class="text-muted">ID: #<?php echo $u['id']; ?></small>
                            </td>
                            <td><?php echo $u['username']; ?></td>
                            <td>
                                <?php 
                                $r_names = explode(', ', $u['role_names']);
                                foreach($r_names as $rn): 
                                    if(empty($rn)) continue;
                                ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary small me-1">
                                        <?php echo strtoupper($rn); ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-outline-info me-1" 
                                        onclick='editUser(<?php echo json_encode($u); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Roles (Select Multiple)</label>
                    <?php foreach ($roles as $r): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="role_ids[]" value="<?php echo $r['id']; ?>" id="role_add_<?php echo $r['id']; ?>">
                            <label class="form-check-label" for="role_add_<?php echo $r['id']; ?>"><?php echo ucfirst($r['name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password (Leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Roles (Select Multiple)</label>
                    <?php foreach ($roles as $r): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input role-checkbox" type="checkbox" name="role_ids[]" value="<?php echo $r['id']; ?>" id="role_edit_<?php echo $r['id']; ?>">
                            <label class="form-check-label" for="role_edit_<?php echo $r['id']; ?>"><?php echo ucfirst($r['name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_fullname').value = data.fullname;
    document.getElementById('edit_username').value = data.username;
    
    // Reset checkboxes
    document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
    
    // Check relevant boxes
    if(data.role_ids) {
        const ids = data.role_ids.split(',');
        ids.forEach(id => {
            const cb = document.getElementById('role_edit_' + id);
            if(cb) cb.checked = true;
        });
    }
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include 'layouts/footer.php'; ?>
