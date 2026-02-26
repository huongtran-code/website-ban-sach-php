<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: ../login.php");
    exit;
}

include "../db_conn.php";
include "func-transaction.php";
include "func-book.php";

// Parameters
$type_filter = $_GET['type'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

// Type filter
if ($type_filter !== '' && $type_filter !== 'all') {
    if ($type_filter === 'group_revenue') {
        $where_clauses[] = "t.type IN ('revenue', 'revenue_order', 'revenue_rental')";
    } elseif ($type_filter === 'group_rental') {
        $where_clauses[] = "t.type IN ('rental', 'rental_extend', 'rental_penalty')";
    } else {
        $where_clauses[] = "t.type = ?";
        $params[] = $type_filter;
    }
}

// Date filter
if ($from_date !== '') {
    $where_clauses[] = "DATE(t.created_at) >= ?";
    $params[] = $from_date;
}
if ($to_date !== '') {
    $where_clauses[] = "DATE(t.created_at) <= ?";
    $params[] = $to_date;
}

// Search filter
if ($search !== '') {
    $where_clauses[] = "(t.description LIKE ? OR u.full_name LIKE ? OR t.type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$stmt = $conn->prepare("SELECT t.*, u.full_name as user_name FROM transactions t 
                        LEFT JOIN users u ON t.user_id = u.id 
                        $where
                        ORDER BY t.created_at DESC");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_revenue = 0;
$total_expense = 0;
foreach ($transactions as $t) {
    if (in_array($t['type'], ['revenue', 'revenue_order', 'revenue_rental', 'deposit'])) {
        $total_revenue += $t['amount'];
    }
    if (in_array($t['type'], ['expense', 'purchase', 'rental', 'rental_extend', 'rental_penalty'])) {
        $total_expense += $t['amount'];
    }
}

// Generate filename
$filename = 'bao_cao_giao_dich';
if ($from_date) $filename .= '_tu_' . $from_date;
if ($to_date) $filename .= '_den_' . $to_date;
$filename .= '_' . date('Ymd_His') . '.csv';

// Set headers for Excel download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// BOM for UTF-8 (Excel compatibility)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Report header info
fputcsv($output, ['BÁO CÁO GIAO DỊCH - NHÀ SÁCH ONLINE']);
fputcsv($output, ['Ngày xuất:', date('d/m/Y H:i:s')]);
if ($from_date || $to_date) {
    $period = '';
    if ($from_date) $period .= 'Từ: ' . date('d/m/Y', strtotime($from_date));
    if ($to_date) $period .= ' Đến: ' . date('d/m/Y', strtotime($to_date));
    fputcsv($output, ['Khoảng thời gian:', $period]);
}
if ($type_filter) {
    $ti = get_transaction_type_info($type_filter);
    fputcsv($output, ['Loại giao dịch:', $ti['label']]);
}
if ($search) {
    fputcsv($output, ['Tìm kiếm:', $search]);
}
fputcsv($output, []);

// Summary
fputcsv($output, ['TỔNG KẾT']);
fputcsv($output, ['Tổng thu:', number_format($total_revenue, 0, ',', '.') . 'đ']);
fputcsv($output, ['Tổng chi:', number_format($total_expense, 0, ',', '.') . 'đ']);
fputcsv($output, ['Lợi nhuận:', number_format($total_revenue - $total_expense, 0, ',', '.') . 'đ']);
fputcsv($output, ['Tổng giao dịch:', count($transactions)]);
fputcsv($output, []);

// Table header
fputcsv($output, ['STT', 'Loại', 'Người dùng', 'Mô tả', 'Số tiền', 'Thu/Chi', 'Thời gian']);

// Data rows
$i = 0;
foreach ($transactions as $t) {
    $i++;
    $ti = get_transaction_type_info($t['type']);
    $direction = is_expense_type($t['type']) || in_array($t['type'], ['purchase', 'rental', 'rental_extend', 'rental_penalty']) ? 'Chi' : 'Thu';
    
    fputcsv($output, [
        $i,
        $ti['label'],
        $t['user_name'] ?? 'Hệ thống',
        $t['description'] ?? 'N/A',
        number_format($t['amount'], 0, ',', '.') . 'đ',
        $direction,
        date('d/m/Y H:i', strtotime($t['created_at']))
    ]);
}

fclose($output);
exit;
