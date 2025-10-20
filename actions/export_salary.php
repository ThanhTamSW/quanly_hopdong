<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

include '../includes/db.php';

if (!isset($_GET['coach_id'])) {
    die("Thiếu thông tin Coach.");
}

$coach_id = intval($_GET['coach_id']);

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Lấy thông tin của Coach
$stmt_coach = $conn->prepare("SELECT full_name, base_salary, sales_target, lunch_allowance FROM users WHERE id = ? AND role = 'coach'");
$stmt_coach->bind_param("i", $coach_id);
$stmt_coach->execute();
$coach = $stmt_coach->get_result()->fetch_assoc();
$stmt_coach->close();

if (!$coach) {
    die("Không tìm thấy Coach.");
}

// Lấy danh sách hợp đồng của Coach này
$stmt_contracts = $conn->prepare("
    SELECT 
        client.full_name, 
        c.package_name, 
        c.total_sessions, 
        c.final_price, 
        (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') as sessions_completed 
    FROM contracts c 
    JOIN users client ON c.client_id = client.id 
    WHERE c.coach_id = ?
    ORDER BY c.start_date ASC
");
$stmt_contracts->bind_param("i", $coach_id);
$stmt_contracts->execute();
$contracts = $stmt_contracts->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_contracts->close();

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bảng lương ' . $coach['full_name']);

// Tính toán các giá trị
$total_revenue = array_sum(array_column($contracts, 'final_price'));
$target_percent = ($coach['sales_target'] > 0) ? ($total_revenue / $coach['sales_target']) * 100 : 0;
$commission_rate = 4; // 4% hoa hồng bán hàng
$commission_amount = $total_revenue * ($commission_rate / 100);
$total_salary = $coach['base_salary'] + $coach['lunch_allowance'] + $commission_amount;

// Điền dữ liệu vào Excel
$sheet->setCellValue('A1', 'BẢNG LƯƠNG THÁNG ' . date('m/Y'));
$sheet->setCellValue('A3', 'Tên HLV: ' . $coach['full_name']);
$sheet->setCellValue('A4', 'Doanh số Target: ' . number_format($coach['sales_target'], 0, ',', '.') . 'đ');
$sheet->setCellValue('A5', 'Doanh số thực tế: ' . number_format($total_revenue, 0, ',', '.') . 'đ');
$sheet->setCellValue('A6', 'Đạt target: ' . number_format($target_percent, 1) . '%');

$sheet->setCellValue('A8', 'Lương cứng: ' . number_format($coach['base_salary'], 0, ',', '.') . 'đ');
$sheet->setCellValue('A9', 'Phụ cấp ăn trưa: ' . number_format($coach['lunch_allowance'], 0, ',', '.') . 'đ');
$sheet->setCellValue('A10', 'Hoa hồng bán hàng (' . $commission_rate . '%): ' . number_format($commission_amount, 0, ',', '.') . 'đ');
$sheet->setCellValue('A12', 'TỔNG LƯƠNG: ' . number_format($total_salary, 0, ',', '.') . 'đ');

// Bảng chi tiết hợp đồng
$sheet->setCellValue('A15', 'CHI TIẾT HỢP ĐỒNG');
$sheet->setCellValue('A16', 'STT');
$sheet->setCellValue('B16', 'Tên Học viên');
$sheet->setCellValue('C16', 'Gói sản phẩm');
$sheet->setCellValue('D16', 'Tổng buổi');
$sheet->setCellValue('E16', 'Đã dạy');
$sheet->setCellValue('F16', 'Thành tiền');

$current_row = 17;
foreach ($contracts as $index => $contract) {
    $sheet->setCellValue('A' . $current_row, $index + 1);
    $sheet->setCellValue('B' . $current_row, $contract['full_name']);
    $sheet->setCellValue('C' . $current_row, $contract['package_name']);
    $sheet->setCellValue('D' . $current_row, $contract['total_sessions']);
    $sheet->setCellValue('E' . $current_row, $contract['sessions_completed']);
    $sheet->setCellValue('F' . $current_row, number_format($contract['final_price'], 0, ',', '.'));
    $current_row++;
}

// Xuất file
$filename = "Bang luong " . $coach['full_name'] . " " . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>