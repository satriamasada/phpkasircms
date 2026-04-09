<?php
require_once __DIR__ . '/../includes/functions.php';
checkLogin();

try {
    // DataTables request parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Define columns mapping for sorting
$columns = [
    0 => 's.created_at',
    1 => 's.invoice_no',
    2 => 'c.name',
    3 => 'u.fullname',
    4 => 's.payment_type',
    5 => 's.total_amount'
];

$orderColumn = $columns[$orderColumnIndex] ?? 's.created_at';

// Base Query
$sql = "FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN branches b ON s.branch_id = b.id";

// Filtering & Branch Scope
$active_branch_id = $_SESSION['active_branch_id'] ?? 1;
$branchWhere = " s.branch_id = ? ";
$params = [$active_branch_id];
$where = "";

if (!empty($searchValue)) {
    $where = " AND (s.invoice_no LIKE ? OR c.name LIKE ? OR u.fullname LIKE ?) ";
    $params[] = "%$searchValue%";
    $params[] = "%$searchValue%";
    $params[] = "%$searchValue%";
}

// Total records without filtering (but within branch scope)
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE branch_id = ?");
$stmtTotal->execute([$active_branch_id]);
$totalRecords = $stmtTotal->fetchColumn();

// Total records with filtering
$stmtFiltered = $pdo->prepare("SELECT COUNT(*) $sql WHERE $branchWhere $where");
$stmtFiltered->execute($params);
$totalRecordsFiltered = $stmtFiltered->fetchColumn();

// Fetch Data
$query = "SELECT s.*, c.name as customer_name, u.fullname as cashier_name, b.name as branch_name
          $sql 
          WHERE $branchWhere $where 
          ORDER BY $orderColumn $orderDir 
          LIMIT $length OFFSET $start";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$data = [];
foreach ($sales as $s) {
    $data[] = [
        date('d/m/Y H:i', strtotime($s['created_at'])),
        '#<span class="fw-bold text-primary cursor-pointer" onclick="viewDetails('.$s['id'].', \''.$s['invoice_no'].'\')">'.$s['invoice_no'].'</span>',
        $s['branch_name'] ?? '<span class="text-muted">Utama</span>',
        $s['customer_name'] ?? '<span class="text-muted">Umum</span>',
        $s['cashier_name'] ?? 'Sistem',
        '<span class="badge rounded-pill bg-'.($s['payment_type'] == 'credit' ? 'warning text-dark' : 'secondary').' opacity-75">'.strtoupper($s['payment_type']).'</span>',
        formatRupiah($s['total_amount']),
        '<div class="text-end">
            <a href="print_invoice.php?id='.$s['id'].'" target="_blank" class="btn btn-sm btn-outline-secondary border-0"><i class="fas fa-print"></i></a>
            <button class="btn btn-sm btn-outline-primary border-0" onclick="viewDetails('.$s['id'].', \''.$s['invoice_no'].'\')"><i class="fas fa-eye"></i></button>
         </div>'
    ];
}

$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalRecordsFiltered),
    "data" => $data
];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "draw" => 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
?>
