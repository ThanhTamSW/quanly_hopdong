<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

// Cập nhật đường dẫn đến db.php
include 'includes/db.php';

if (!isset($_GET['coach_id'])) {
    die("Thiếu thông tin Coach.");
}

$coach_id = intval($_GET['coach_id']);

require 'vendor/autoload.php';

// Khai báo sử dụng các lớp của PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

if (!isset($_GET['coach_id'])) die("Thiếu thông tin Coach.");
$coach_id = intval($_GET['coach_id']);

// Lấy thông tin của Coach (bao gồm các trường mới)
$stmt_coach = $conn->prepare("SELECT full_name, base_salary, sales_target, lunch_allowance FROM users WHERE id = ?");
$stmt_coach->bind_param("i", $coach_id);
$stmt_coach->execute();
$coach = $stmt_coach->get_result()->fetch_assoc();
if (!$coach) die("Không tìm thấy Coach.");

// Lấy danh sách hợp đồng của Coach này
$stmt_contracts = $conn->prepare("SELECT client.full_name, c.package_name, c.total_sessions, c.final_price, (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') as sessions_completed FROM contracts c JOIN users client ON c.client_id = client.id WHERE c.coach_id = ?");
$stmt_contracts->bind_param("i", $coach_id);
$stmt_contracts->execute();
$contracts = $stmt_contracts->get_result()->fetch_all(MYSQLI_ASSOC);

// --- BẮT ĐẦU TẠO FILE EXCEL ---

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bảng lương ' . $coach['full_name']);

// --- PHẦN 1: TẠO KHỐI THÔNG TIN TỔNG QUAN ---

// Tính toán các giá trị
$total_revenue = array_sum(array_column($contracts, 'final_price'));
$target_percent = ($coach['sales_target'] > 0) ? ($total_revenue / $coach['sales_target']) * 100 : 0;
$commission_rate = 4; // Giả sử 4%
$commission_amount = $total_revenue * ($commission_rate / 100);
$total_salary = $coach['base_salary'] + $coach['lunch_allowance'] + $commission_amount;

// Merge và điền dữ liệu
$sheet->mergeCells('B2:C2')->setCellValue('B2', 'CÂU LẠC BỘ');
$sheet->mergeCells('B3:C3')->setCellValue('B3', 'THÁNG');
$sheet->setCellValue('B5', 'TÊN HUẤN LUYỆN VIÊN')->setCellValue('D5', $coach['full_name']);
$sheet->setCellValue('E4', 'Doanh số Target')->setCellValue('F4', $coach['sales_target']);
$sheet->setCellValue('G4', number_format($target_percent, 2) . '%');
$sheet->setCellValue('I3', 'Lương cứng')->setCellValue('J3', $coach['base_salary']);
$sheet->setCellValue('I4', 'Cơm trưa')->setCellValue('J4', $coach['lunch_allowance']);
$sheet->setCellValue('I5', 'Com Sale')->setCellValue('J5', $commission_amount);
$sheet->setCellValue('K5', $commission_rate . '%');
$sheet->setCellValue('I8', 'Tổng')->setCellValue('J8', $total_salary);

// --- PHẦN 2: TẠO BẢNG CHI TIẾT HỢP ĐỒNG ---
$sheet->setCellValue('B13', 'CƠM DẠY');
$sheet->setCellValue('B14', 'STT')->setCellValue('C14', 'TÊN HỌC VIÊN')->setCellValue('D14', 'LOẠI SẢN PHẨM')
      ->setCellValue('E14', 'SỐ BUỔI')->setCellValue('F14', 'SỐ TIỀN')->setCellValue('H14', 'SỐ BUỔI DẠY')
      ->setCellValue('J14', '% COM DẠY')->setCellValue('K14', 'CƠM DẠY');

$current_row = 15;
foreach ($contracts as $index => $contract) {
    $sheet->setCellValue('B'.$current_row, $index + 1);
    $sheet->setCellValue('C'.$current_row, $contract['full_name']);
    $sheet->setCellValue('D'.$current_row, $contract['package_name']);
    $sheet->setCellValue('E'.$current_row, $contract['total_sessions']);
    $sheet->setCellValue('F'.$current_row, $contract['final_price']);
    $sheet->setCellValue('H'.$current_row, $contract['sessions_completed']);
    $current_row++;
}

// --- XUẤT FILE ---
$filename = "Bang luong " . $coach['full_name'] . " " . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
// Lấy tên Coach để đặt tên file
$stmt_coach = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'coach'");
$stmt_coach->bind_param("i", $coach_id);
$stmt_coach->execute();
$coach_info = $stmt_coach->get_result()->fetch_assoc();
$stmt_coach->close();

if (!$coach_info) {
    die("Không tìm thấy Coach.");
}
$coach_name = $coach_info['full_name'];

// Lấy tất cả hợp đồng của Coach này
$sql = "
    SELECT 
        c.start_date,
        client.full_name AS client_name,
        c.package_name,
        c.total_sessions,
        c.final_price,
        (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') AS sessions_completed
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ?
    ORDER BY c.start_date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result();

// --- BẮT ĐẦU TẠO FILE EXCEL ---

// Đặt tên file
$filename = "Bang luong " . $coach_name . " " . date('Y-m-d') . ".xls";

// Thiết lập header để trình duyệt hiểu đây là một file Excel cần tải về
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Bắt đầu xuất nội dung dạng bảng HTML (Excel có thể đọc được)
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<h3>BẢNG TÍNH LƯƠNG CHI TIẾT CHO COACH: ' . htmlspecialchars($coach_name) . '</h3>';
echo '<table border="1">';
echo '<thead>';
echo '<tr>';
echo '<th>STT</th>';
echo '<th>Ngày bắt đầu</th>';
echo '<th>Tên Học viên</th>';
echo '<th>Gói sản phẩm</th>';
echo '<th>Tổng số buổi</th>';
echo '<th>Số buổi đã dạy</th>';
echo '<th>Thành tiền (VNĐ)</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$stt = 1;
$total_revenue = 0;
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $stt++ . '</td>';
    echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
    echo '<td>' . htmlspecialchars($row['client_name']) . '</td>';
    echo '<td>' . htmlspecialchars($row['package_name']) . '</td>';
    echo '<td>' . htmlspecialchars($row['total_sessions']) . '</td>';
    echo '<td>' . htmlspecialchars($row['sessions_completed']) . '</td>';
    echo '<td>' . htmlspecialchars($row['final_price']) . '</td>';
    echo '</tr>';
    $total_revenue += $row['final_price'];
}

echo '</tbody>';
echo '<tfoot>';
echo '<tr>';
echo '<td colspan="6" style="font-weight: bold; text-align: right;">TỔNG DOANH THU</td>';
echo '<td style="font-weight: bold;">' . $total_revenue . '</td>';
echo '</tr>';
echo '</tfoot>';
echo '</table>';

exit;
?>