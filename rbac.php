<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_rbac');

// Handle dynamic permission updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_rbac') {
    $matrix = $_POST['matrix'] ?? []; // format: [role_id][permission_id]
    
    try {
        $pdo->beginTransaction();
        $pdo->query("DELETE FROM role_permissions");
        
        foreach ($matrix as $role_id => $perms) {
            foreach ($perms as $perm_id => $val) {
                $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)")->execute([$role_id, $perm_id]);
            }
        }
        
        $pdo->commit();
        setFlashMessage("RBAC Matrix updated successfully!");
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage("Update failed: " . $e->getMessage(), "danger");
    }
    header("Location: rbac.php");
    exit;
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY id ASC")->fetchAll();

// Get role permissions mapping
$role_perms = [];
$stmt = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
while ($row = $stmt->fetch()) {
    $role_perms[$row['role_id']][] = $row['permission_id'];
}

include 'layouts/header.php';
?>

<form method="POST">
    <input type="hidden" name="action" value="sync_rbac">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Dynamic RBAC Matrix</h5>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Save Changes
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 250px;" class="ps-4">Feature Permissions</th>
                            <?php foreach ($roles as $r): ?>
                                <th class="text-center">
                                    <div class="fw-bold"><?php echo strtoupper($r['name']); ?></div>
                                    <small class="text-muted fw-normal">ID: <?php echo $r['id']; ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $p['label']; ?></div>
                                    <code class="small text-muted"><?php echo $p['name']; ?></code>
                                </td>
                                <?php foreach ($roles as $r): ?>
                                    <td class="text-center align-middle">
                                        <?php $has = in_array($p['id'], $role_perms[$r['id']] ?? []); ?>
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="matrix[<?php echo $r['id']; ?>][<?php echo $p['id']; ?>]" 
                                                   value="1" <?php echo $has ? 'checked' : ''; ?>
                                                   <?php echo $r['name'] == 'admin' ? 'disabled checked' : ''; ?>>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light p-3">
            <div class="small text-muted">
                <i class="fas fa-info-circle me-1"></i> <strong>Note:</strong> Admin permissions are hardlocked and cannot be modified via UI for safety.
            </div>
        </div>
    </div>
</form>

<?php include 'layouts/footer.php'; ?>
