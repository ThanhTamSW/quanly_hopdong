<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập.");
}

include '../includes/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Lấy tham số
$coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;
$month_filter = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($coach_id == 0) {
    die("Thiếu thông tin Coach.");
}

// Parse tháng/năm
$date_obj = DateTime::createFromFormat('Y-m', $month_filter);
$year = $date_obj->format('Y');
$month = $date_obj->format('m');
$month_name = $date_obj->format('m/Y');

// Lấy thông tin Coach
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'coach'");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$coach = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$coach) {
    die("Không tìm thấy Coach.");
}

// Tính doanh số (từ hợp đồng bắt đầu trong tháng)
$stmt_revenue = $conn->prepare("
    SELECT SUM(final_price) as total
    FROM contracts
    WHERE coach_id = ?
      AND YEAR(start_date) = ?
      AND MONTH(start_date) = ?
");
$stmt_revenue->bind_param("iii", $coach_id, $year, $month);
$stmt_revenue->execute();
$revenue_result = $stmt_revenue->get_result()->fetch_assoc();
$doanh_so = $revenue_result['total'] ?? 0;
$stmt_revenue->close();

// Lấy danh sách buổi đã dạy (COM DAY)
$stmt_sessions = $conn->prepare("
    SELECT 
        client.full_name AS client_name,
        c.package_name,
        c.total_sessions,
        c.final_price,
        COUNT(DISTINCT ts.id) as buoi_da_day,
        (c.final_price / c.total_sessions) as gia_per_buoi
    FROM payroll_log pl
    JOIN contracts c ON pl.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    JOIN training_sessions ts ON pl.session_id = ts.id
    WHERE pl.coach_id = ?
      AND YEAR(pl.completion_timestamp) = ?
      AND MONTH(pl.completion_timestamp) = ?
    GROUP BY c.id, client.full_name, c.package_name, c.total_sessions, c.final_price
    ORDER BY client.full_name ASC
");
$stmt_sessions->bind_param("iii", $coach_id, $year, $month);
$stmt_sessions->execute();
$sessions = $stmt_sessions->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_sessions->close();

// Tính toán lương
$target = $coach['sales_target'] ?? 0;
$percent_hoan_thanh = ($target > 0) ? ($doanh_so / $target) * 100 : 0;

// Com bán (4% nếu đạt >= 80% target)
$com_sale_rate = ($percent_hoan_thanh >= 80) ? 4 : 0;
$com_sale = $doanh_so * ($com_sale_rate / 100);

// Com dạy (26% trên giá mỗi buổi)
$com_day_rate = 26;
$com_day_total = 0;
$so_buoi_day_total = 0;

foreach ($sessions as $session) {
    $so_buoi = $session['buoi_da_day'];
    $gia_per_buoi = $session['gia_per_buoi'];
    $com_day_total += $so_buoi * $gia_per_buoi * ($com_day_rate / 100);
    $so_buoi_day_total += $so_buoi;
}

// Các khoản khác
$base_salary = $coach['base_salary'] ?? 0;
$lunch_allowance = $coach['lunch_allowance'] ?? 0;
$bonus = $coach['monthly_bonus'] ?? 0;
$penalty = $coach['monthly_penalty'] ?? 0;

// Tổng lương
$tong_luong = $com_day_total + $com_sale + $lunch_allowance + $bonus - $penalty;

// === TẠO FILE EXCEL ===
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bảng lương');

// HEADER SECTION
$sheet->mergeCells('B3:B4');
$sheet->setCellValue('B3', 'CẤU LẠC BỘ THÁNG');
$sheet->mergeCells('D3:D4');
$sheet->setCellValue('D3', 'TRANSFORM ' . $month);
$sheet->mergeCells('E3:E4');
$sheet->setCellValue('E3', 'Doanh số');
$sheet->setCellValue('E5', 'Target');
$sheet->mergeCells('B5:B5');
$sheet->setCellValue('B5', 'TÊN HUẤN LUYỆN VIÊN');
$sheet->mergeCells('D5:D5');
$sheet->setCellValue('D5', $coach['full_name']);
$sheet->setCellValue('F3', number_format($target, 0, ',', '.'));
$sheet->setCellValue('G3', number_format($percent_hoan_thanh, 1) . '%');
$sheet->mergeCells('H3:H4');
$sheet->setCellValue('H3', 'Lương cứng');
$sheet->mergeCells('I3:I4');
$sheet->setCellValue('I3', number_format($base_salary + $lunch_allowance, 0, ',', '.'));
$sheet->setCellValue('H5', 'Com dạy');
$sheet->setCellValue('I5', number_format($com_day_total, 0, ',', '.'));
$sheet->mergeCells('J3:J4');
$sheet->setCellValue('J3', 'Com sale');
$sheet->setCellValue('J5', number_format($com_sale, 0, ',', '.'));
$sheet->setCellValue('K3', 'Thưởng');
$sheet->setCellValue('K5', number_format($bonus, 0, ',', '.'));
$sheet->setCellValue('K4', 'Phạt');
$sheet->setCellValue('L3', 'Tổng');
$sheet->setCellValue('L5', number_format($tong_luong, 0, ',', '.'));

// BẢNG COM DAY
$row = 13;
$sheet->setCellValue('B' . $row, 'COM DAY');
$sheet->setCellValue('H' . $row, $so_buoi_day_total);
$sheet->setCellValue('K' . $row, number_format($com_day_total, 0, ',', '.'));

$row++;
$sheet->setCellValue('B' . $row, 'STT');
$sheet->setCellValue('C' . $row, 'TÊN HỌC VIÊN');
$sheet->setCellValue('D' . $row, 'LOẠI SẢN PHẨM');
$sheet->setCellValue('E' . $row, 'SỐ BUỔI');
$sheet->setCellValue('F' . $row, 'SỐ TIỀN');
$sheet->setCellValue('G' . $row, 'SỐ BUỔI ĐÃ DẠY');
$sheet->setCellValue('H' . $row, 'SỐ TIỀN MỖI BUỔI');
$sheet->setCellValue('I' . $row, '% COM DẠY');
$sheet->setCellValue('J' . $row, 'SỐ TIỀN DẠY');

// Fill header background
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFB6C1']
    ],
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle('B' . $row . ':J' . $row)->applyFromArray($headerStyle);

$row++;
$stt = 1;
foreach ($sessions as $session) {
    $sheet->setCellValue('B' . $row, $stt++);
    $sheet->setCellValue('C' . $row, $session['client_name']);
    $sheet->setCellValue('D' . $row, $session['package_name']);
    $sheet->setCellValue('E' . $row, $session['total_sessions']);
    $sheet->setCellValue('F' . $row, number_format($session['final_price'], 0, ',', '.'));
    $sheet->setCellValue('G' . $row, $session['buoi_da_day']);
    $sheet->setCellValue('H' . $row, number_format($session['gia_per_buoi'], 0, ',', '.'));
    $sheet->setCellValue('I' . $row, $com_day_rate . '%');
    $sheet->setCellValue('J' . $row, number_format($session['buoi_da_day'] * $session['gia_per_buoi'] * 0.26, 0, ',', '.'));
    $row++;
}

// Auto-size columns
foreach (range('B', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// XUẤT FILE
$filename = "Bang_luong_" . $coach['full_name'] . "_" . $month_name . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
