<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}
include '../includes/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_GET['coach_id'])) die("Thiếu thông tin Coach.");
$coach_id = intval($_GET['coach_id']);
$month_filter = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$date_obj = DateTime::createFromFormat('Y-m', $month_filter);
$year_filter_val = $date_obj->format('Y');
$month_filter_val = $date_obj->format('m');

$stmt_coach = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'coach'");
$stmt_coach->bind_param("i", $coach_id);
$stmt_coach->execute();
$coach = $stmt_coach->get_result()->fetch_assoc();
if (!$coach) die("Không tìm thấy Coach.");

// CẬP NHẬT: Thay đổi cách lấy dữ liệu để tương thích hơn
$stmt_revenue = $conn->prepare("
    SELECT DISTINCT c.id, c.final_price 
    FROM contracts c
    JOIN training_sessions ts ON c.id = ts.contract_id
    WHERE c.coach_id = ? 
      AND ts.status = 'completed'
      AND YEAR(ts.action_timestamp) = ? 
      AND MONTH(ts.action_timestamp) = ?
");
$stmt_revenue->bind_param("iii", $coach_id, $year_filter_val, $month_filter_val);
$stmt_revenue->execute();
$revenue_result = $stmt_revenue->get_result();
$revenue_contracts = ($revenue_result) ? $revenue_result->fetch_all(MYSQLI_ASSOC) : [];
$stmt_revenue->close();
$total_revenue_from_contracts = array_sum(array_column($revenue_contracts, 'final_price'));


// CẬP NHẬT: Thay đổi cách lấy dữ liệu để tương thích hơn
$stmt_log = $conn->prepare("
    SELECT 
        pl.id, pl.session_id, pl.contract_id, pl.coach_id, pl.completion_timestamp, pl.commission_earned, 
        c.package_name, c.total_sessions, c.final_price,
        u.full_name as client_name
    FROM payroll_log pl
    JOIN contracts c ON pl.contract_id = c.id
    JOIN users u ON c.client_id = u.id
    WHERE pl.coach_id = ? 
      AND YEAR(pl.completion_timestamp) = ? 
      AND MONTH(pl.completion_timestamp) = ?
    ORDER BY pl.completion_timestamp ASC
");
$stmt_log->bind_param("iii", $coach_id, $year_filter_val, $month_filter_val);
$stmt_log->execute();
$log_result = $stmt_log->get_result();
$completed_sessions_log = ($log_result) ? $log_result->fetch_all(MYSQLI_ASSOC) : [];
$stmt_log->close();


// --- BẮT ĐẦU TÍNH TOÁN ---

$is_official_coach = false;
if (!empty($coach['start_work_date'])) {
    $start_work_date = new DateTime($coach['start_work_date']);
    $today = new DateTime('now');
    $interval = $start_work_date->diff($today);
    $months_worked = $interval->y * 12 + $interval->m;
    $is_official_coach = ($months_worked >= 2);
}

$sales_target = $coach['sales_target'] ?? 0;
$target_percent = ($sales_target > 0) ? ($total_revenue_from_contracts / $sales_target) : 0;

$base_salary = 0;
$commission_sale_rate = 0;
$commission_session_rate = 0;

if ($is_official_coach) {
    $base_salary = $coach['base_salary'] ?? 0;
    if ($target_percent >= 0.8) {
        $commission_sale_rate = 4;
        $commission_session_rate = 26;
    } else {
        $commission_sale_rate = 0;
        $commission_session_rate = 26;
    }
} else {
    $base_salary = 0;
    $commission_sale_rate = 0;
    $commission_session_rate = 26;
}

$commission_sale_amount = $total_revenue_from_contracts * ($commission_sale_rate / 100);

$total_session_commission = 0;
if ($commission_session_rate > 0) {
    foreach($completed_sessions_log as $log_item) {
        $price_per_session = ($log_item['total_sessions'] > 0) ? ($log_item['final_price'] / $log_item['total_sessions']) : 0;
        $total_session_commission += $price_per_session * ($commission_session_rate / 100);
    }
}

$lunch_allowance = $coach['lunch_allowance'] ?? 0;
$monthly_bonus = $coach['monthly_bonus'] ?? 0;
$monthly_penalty = $coach['monthly_penalty'] ?? 0;

$total_salary = $base_salary + $lunch_allowance + $commission_sale_amount + $total_session_commission + $monthly_bonus - $monthly_penalty;

// --- BẮT ĐẦU TẠO FILE ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// ... (Phần code định dạng và điền dữ liệu vào Excel của PhpSpreadsheet giữ nguyên)

// --- XUẤT FILE ---
$filename = "Bang luong " . $coach['full_name'] . " - Thang " . $date_obj->format('m-Y') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max_age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>