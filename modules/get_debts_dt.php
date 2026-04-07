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
        3 => 's.total_amount',
        4 => 'total_paid',
        5 => 'remaining',
        6 => 'status'
    ];

    $orderColumn = $columns[$orderColumnIndex] ?? 's.created_at';

    // Base query for calculations
    $subquery = "(SELECT IFNULL(SUM(amount_paid), 0) FROM debt_payments WHERE sale_id = s.id)";
    
    // SQL Parts
    $sqlFrom = "FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id";
    
    // Only fetch credit sales
    $where = " WHERE s.payment_type = 'credit' ";
    $params = [];
    
    if (!empty($searchValue)) {
        $where .= " AND (s.invoice_no LIKE ? OR c.name LIKE ?) ";
        $params[] = "%$searchValue%";
        $params[] = "%$searchValue%";
    }

    // Total records (always constant for credit sales)
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_type = 'credit'")->fetchColumn();

    // Total records with filtering
    $stmtFiltered = $pdo->prepare("SELECT COUNT(*) $sqlFrom $where");
    $stmtFiltered->execute($params);
    $totalRecordsFiltered = $stmtFiltered->fetchColumn();

    // Fetch Data with calculated fields
    $query = "SELECT s.*, c.name as customer_name, 
              $subquery as total_paid,
              (s.total_amount - $subquery) as remaining
              $sqlFrom 
              $where 
              ORDER BY $orderColumn $orderDir 
              LIMIT $length OFFSET $start";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $debts = $stmt->fetchAll();

    $data = [];
    foreach ($debts as $d) {
        $statusBadge = '';
        if ($d['remaining'] <= 0) {
            $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 fw-bold">LUNAS</span>';
        } else {
            $statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 fw-bold">PIUTANG</span>';
        }

        $data[] = [
            date('d/m/Y', strtotime($d['created_at'])),
            '<div class="fw-bold">#' . $d['invoice_no'] . '</div>',
            $d['customer_name'] ?? 'General',
            formatRupiah($d['total_amount']),
            '<span class="text-success fw-medium">' . formatRupiah($d['total_paid']) . '</span>',
            '<span class="text-danger fw-bold">' . formatRupiah($d['remaining']) . '</span>',
            $statusBadge,
            '<div class="text-end">
                <button class="btn btn-sm btn-outline-info border-0 me-1" onclick="viewHistory('.$d['id'].', \''.$d['invoice_no'].'\')" title="History Pembayaran">
                    <i class="fas fa-history"></i>
                </button>
                '.($d['remaining'] > 0 ? 
                    '<button class="btn btn-sm btn-primary px-3 rounded-pill" onclick="openPayModal('.$d['id'].', '.$d['remaining'].', \''.$d['invoice_no'].'\')">
                        <i class="fas fa-hand-holding-usd me-1"></i> Bayar
                     </button>' : 
                    '<button class="btn btn-sm btn-light border-0" disabled><i class="fas fa-check-double text-success"></i></button>'
                ).'
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
