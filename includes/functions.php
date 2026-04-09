<?php
session_start();
require_once 'db.php';

// --- UNIVERSAL INSTALLER CHECK ---
global $pdo, $pdo_error;
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'install.php') {
    // If connection failed OR tables don't exist
    if ($pdo_error || !isSystemInstalled()) {
        header('Location: install.php');
        exit;
    }
}

// 1. Simple .env Loader
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, ' "');
    }
}
loadEnv(__DIR__ . '/../.env');

// 2. License & Subscription Helpers
function validateLicense($key) {
    $salt = "KASIR_SECRET_2026";
    $decoded = base64_decode($key);
    if (!$decoded) return ['valid' => false, 'tier' => 'STANDARD', 'error' => 'invalid_format'];

    $parts = explode('|', $decoded);
    if (count($parts) !== 3) return ['valid' => false, 'tier' => 'STANDARD', 'error' => 'invalid_parts'];

    list($tier, $expiry, $hash) = $parts;
    $expectedHash = substr(md5($tier . $expiry . $salt), 0, 8);

    if ($hash !== $expectedHash) return ['valid' => false, 'tier' => 'STANDARD', 'error' => 'invalid_hash'];
    
    $expired = (strtotime($expiry) < time());
    
    return [
        'valid' => true,
        'tier' => strtoupper($tier),
        'expiry' => $expiry,
        'expired' => $expired
    ];
}

function getSubscriptionInfo() {
    $key = $_ENV['LICENSE_KEY'] ?? '';
    $result = validateLicense($key);
    
    // Fallback if invalid
    if (!$result['valid']) {
        return [
            'valid' => false,
            'tier' => 'INVALID',
            'users' => 0, 
            'branches' => 0,
            'reports' => false,
            'expired' => false,
            'error' => $result['error']
        ];
    }

    $limits = [
        'STANDARD' => ['users' => 2, 'branches' => 1, 'reports' => false],
        'PROFESSIONAL' => ['users' => 999, 'branches' => 5, 'reports' => true]
    ];
    
    $config = $limits[$result['tier']] ?? $limits['STANDARD'];
    return array_merge(['valid' => true, 'tier' => $result['tier'], 'expiry' => $result['expiry'], 'expired' => $result['expired']], $config);
}

function checkLicenseLimit($type) {
    global $pdo;
    $info = getSubscriptionInfo();
    
    if ($type === 'users') {
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        return $count < $info['users'];
    }
    
    if ($type === 'branches') {
        $count = $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();
        return $count < $info['branches'];
    }
    
    return true;
}

function hasFullReports() {
    return getSubscriptionInfo()['reports'];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSystemInstalled() {
    global $pdo;
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        return ($count > 0);
    } catch (Exception $e) {
        return false;
    }
}

function checkLogin() {
    if (!isSystemInstalled()) {
        header('Location: setup_database.php');
        exit;
    }
    
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Global License Lock - Run on every page load
$license_info = getSubscriptionInfo();
$current_page = basename($_SERVER['PHP_SELF']);
$bypass_pages = ['license_expired.php', 'logout.php', 'tools_license_gen.php'];

if ((!$license_info['valid'] || $license_info['expired']) && !in_array($current_page, $bypass_pages)) {
    header('Location: license_expired.php');
    exit;
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
        header('Location: dashboard.php?error=unauthorized');
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

function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        return '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i> ' . $flash['message'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

// 4. Global Settings Helper
$app_settings_cache = null;

function getSetting($key, $default = '') {
    global $pdo, $app_settings_cache;
    
    // Load all settings once if cache is empty
    if ($app_settings_cache === null) {
        $app_settings_cache = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $app_settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Table might not exist yet during setup
        }
    }
    
    return $app_settings_cache[$key] ?? $default;
}
?>
