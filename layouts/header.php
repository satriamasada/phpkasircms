<?php
require_once __DIR__ . '/../includes/functions.php';
checkLogin();

// Handle Branch Switch
if (isset($_GET['set_branch'])) {
    $new_branch_id = (int)$_GET['set_branch'];
    // Allow any logged-in user to switch their active session branch
    $_SESSION['active_branch_id'] = $new_branch_id;
    $_SESSION['flash'] = ['message' => 'Cabang aktif berhasil diubah.', 'type' => 'success'];
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $current_url");
    exit;
}

// Fetch active branch info
$active_branch_id = $_SESSION['active_branch_id'] ?? 1;
$stmt_b = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
$stmt_b->execute([$active_branch_id]);
$active_branch_name = $stmt_b->fetchColumn() ?: 'Utama';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('app_name', 'POS PREMIUM'); ?> - Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Theme Variables */
        :root {
            --primary-color: #764ba2;
            --secondary-color: #667eea;
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --bg-light: #f8f9fa;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --border-color: #eeeeee;
        }

        [data-bs-theme="dark"] {
            --bg-light: #1a1a2e;
            --sidebar-bg: #16213e;
            --card-bg: #16213e;
            --text-main: #e9ecef;
            --text-muted: #adb5bd;
            --border-color: #2d3436;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            overflow-x: hidden;
            transition: all 0.3s;
        }

        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--sidebar-bg);
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all 0.3s;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            height: var(--topbar-height);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        /* Sidebar & Menu Styles */
        .nav-menu {
            padding: 20px 0;
            overflow-y: auto;
            flex: 1;
        }

        .nav-menu::-webkit-scrollbar {
            width: 5px;
        }

        .nav-menu::-webkit-scrollbar-thumb {
            background-color: var(--border-color);
            border-radius: 5px;
        }

        .nav-item {
            padding: 2px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-muted);
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
        }

        .nav-link i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 10px;
        }

        .nav-link:hover {
            background: rgba(118, 75, 162, 0.1);
            color: var(--primary-color);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .nav-section {
            padding: 15px 25px 5px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* Main Content Styles */
        #main-content {
            margin-left: var(--sidebar-width);
            padding: 25px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .topbar {
            height: var(--topbar-height);
            background: var(--sidebar-bg);
            margin: -25px -25px 25px -25px;
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid var(--border-color);
        }

        /* Card Styles */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: none;
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            font-weight: 600;
            color: var(--text-main);
        }

        .stats-card {
            padding: 20px;
            display: flex;
            align-items: center;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .table {
            color: var(--text-main);
        }

        .table thead th {
            background-color: var(--bg-light);
            border-bottom-color: var(--border-color);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-main);
            border-color: var(--border-color);
            border-radius: 15px;
        }

        /* Locked Feature Overlay */
        .locked-feature {
            position: relative;
            cursor: not-allowed !important;
        }
        .locked-feature > *:not(.lock-overlay) {
            filter: grayscale(100%);
            opacity: 0.3;
            pointer-events: none;
        }
        .lock-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            background: rgba(255,255,255,0.9);
            padding: 8px 16px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            color: #764ba2;
            font-weight: 700;
            font-size: 0.75rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            border: 1px solid rgba(118, 75, 162, 0.2);
        }
        [data-bs-theme="dark"] .lock-overlay {
            background: rgba(43, 48, 53, 0.9);
            color: #a78bfa;
        }
    </style>
    <script>
        // Theme Management
        const getPreferredTheme = () => {
            const storedTheme = localStorage.getItem('theme')
            if (storedTheme) return storedTheme
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }

        const setTheme = theme => {
            document.documentElement.setAttribute('data-bs-theme', theme)
        }

        setTheme(getPreferredTheme())
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
</head>

<body>


    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <i class="fas fa-shopping-cart me-2"></i> <?php echo getSetting('app_name', 'POS PREMIUM'); ?>
            </a>
        </div>

        <div class="nav-menu">
            <div class="sidebar-user mb-4 p-3 bg-white bg-opacity-10 rounded-4 mx-3">
                <!-- <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <div class="stats-icon bg-white text-dark rounded-circle" style="width: 40px; height: 40px; font-size: 1rem;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3 overflow-hidden">
                        <div class="fw-bold small text-truncate text-white"><?php echo $_SESSION['fullname']; ?></div>
                        <div class="text-white-50 x-small text-truncate"><?php echo implode(', ', $_SESSION['role_names']); ?></div>
                    </div>
                </div> -->
                <div class="">
                    <span
                        class="badge w-100 py-2 <?php echo getSubscriptionInfo()['tier'] === 'PROFESSIONAL' ? 'bg-warning text-dark' : 'bg-light text-dark'; ?> shadow-sm">
                        <i class="fas fa-crown me-1"></i> <?php echo getSubscriptionInfo()['tier']; ?>
                    </span>
                </div>
            </div>

            <div class="nav-item">
                <a href="dashboard.php"
                    class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>

            <?php if (hasPermission('access_pos')): ?>
                <div class="nav-section">Sales</div>
                <div class="nav-item">
                    <a href="pos.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cash-register"></i> Cashier POS
                    </a>
                </div>
                <div class="nav-item">
                    <a href="debts.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'debts.php' ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd"></i> Debt Management
                    </a>
                </div>
            <?php endif; ?>

            <div class="nav-section">Master Data</div>

            <?php if (hasPermission('manage_products')): ?>
                <div class="nav-item">
                    <a href="products.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                </div>
            <?php endif; ?>

            <?php if (hasPermission('manage_suppliers')): ?>
                <div class="nav-item">
                    <a href="suppliers.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </div>
            <?php endif; ?>

            <?php if (hasPermission('manage_customers')): ?>
                <div class="nav-item">
                    <a href="customers.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </div>
            <?php endif; ?>

            <?php if (hasPermission('view_reports')): ?>
                <div class="nav-section">Reports & Outlets</div>
                <div class="nav-item">
                    <a href="reports.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Sales Report
                    </a>
                </div>
                <?php if (hasPermission('manage_branches')): ?>
                    <div class="nav-item">
                        <a href="branches.php"
                            class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : ''; ?>">
                            <i class="fas fa-store"></i> Kelola Cabang
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (hasPermission('manage_rbac') || hasPermission('manage_users')): ?>
                <div class="nav-section">System</div>
                <?php if (hasPermission('manage_users')): ?>
                    <div class="nav-item">
                        <a href="users.php"
                            class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield"></i> User Management
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (hasPermission('manage_rbac')): ?>
                    <div class="nav-item">
                        <a href="rbac.php"
                            class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rbac.php' ? 'active' : ''; ?>">
                            <i class="fas fa-key"></i> Roles & Permissions
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="setup_database.php"
                            class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'setup_database.php' ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i> Database Setup
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="settings.php"
                            class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i> System Settings
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>


            <div class="nav-item mb-3">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div id="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 fw-bold text-muted me-3">
                    <?php
                    $page = basename($_SERVER['PHP_SELF']);
                    $page_titles = [
                        'dashboard.php' => 'Dashboard Overview',
                        'products.php' => 'Product Inventory',
                        'suppliers.php' => 'Suppliers',
                        'customers.php' => 'Customers',
                        'pos.php' => 'Point of Sale',
                        'reports.php' => 'Sales Reports',
                        'branches.php' => 'Branch Management',
                        'users.php' => 'User Administration',
                        'rbac.php' => 'Roles & Permissions',
                        'setup_database.php' => 'Database Management',
                        'settings.php' => 'System Settings',
                        'debts.php' => 'Debt & Receivables'
                    ];
                    
                    if (isset($page_titles[$page])) {
                        echo $page_titles[$page];
                    } else {
                        echo ucwords(str_replace(['.php', '_'], ['', ' '], $page));
                    }
                    ?>
                </h4>
                <button class="btn btn-sm btn-outline-secondary rounded-circle me-3" id="themeToggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>

                <!-- Branch Selector -->
                <div class="dropdown me-3">
                    <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <span class="small fw-bold">Cabang: <?php echo $active_branch_name; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <li><h6 class="dropdown-header">Pilih Cabang Aktif</h6></li>
                        <?php 
                        $all_branches = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC")->fetchAll();
                        foreach ($all_branches as $branch): ?>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center py-2 <?php echo $active_branch_id == $branch['id'] ? 'active bg-primary' : ''; ?>" 
                                   href="?set_branch=<?php echo $branch['id']; ?>">
                                    <span><?php echo $branch['name']; ?></span>
                                    <?php if ($active_branch_id == $branch['id']): ?>
                                        <i class="fas fa-check-circle small"></i>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="me-3 text-end d-none d-md-block">
                    <div class="fw-bold"><?php echo $_SESSION['fullname']; ?></div>
                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;">
                        <?php echo implode(', ', $_SESSION['role_names']); ?>
                    </small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['fullname']); ?>&background=764ba2&color=fff"
                    class="rounded-circle" width="40" height="40">
            </div>
        </div>

        <?php
        $flash = getFlashMessage();
        if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                activeLink.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        });
    </script>