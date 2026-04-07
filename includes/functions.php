<?php
session_start();
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasPermission($permissionName) {
    global $pdo;
    
    if (!isLoggedIn()) return false;
    
    $role_names = $_SESSION['role_names'] ?? [];
    $roles = $_SESSION['roles'] ?? [];
    
    // Admin always has all permissions
    if (in_array('admin', $role_names)) return true;
    
    $role_ids = array_column($roles, 'id');
    if (empty($role_ids)) return false;
    
    $in = implode(',', array_fill(0, count($role_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id IN ($in) AND p.name = ?
    ");
    
    $params = $role_ids;
    $params[] = $permissionName;
    
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function requirePermission($permissionName) {
    if (!hasPermission($permissionName)) {
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

function formatRupiah($amount) {
    return "Rp " . number_format($amount, 0, ',', '.');
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}
?>
