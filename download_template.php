<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'vendor/autoload.php';
require_once 'includes/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set title
$sheet->setTitle('Template Import');

// Header style
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

// Headers
$headers = [
    'A1' => 'Tên Học Viên',
    'B1' => 'SĐT Học Viên',
    'C1' => 'Tên Coach',
    'D1' => 'SĐT Coach',
    'E1' => 'Ngày Bắt Đầu (DD/MM/YYYY)',
    'F1' => 'Số Buổi',
    'G1' => 'Giá Gốc',
    'H1' => 'Giảm Giá (%)',
    'I1' => 'Giá Cuối',
    'J1' => 'Loại TT (full/installment)',
    'K1' => 'Số Đợt Trả',
    'L1' => 'Tiền Đặt Cọc/Trả Trước'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
    $sheet->getStyle($cell)->applyFromArray($headerStyle);
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(12);
$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(22);
$sheet->getColumnDimension('K')->setWidth(12);
$sheet->getColumnDimension('L')->setWidth(20);

// Add sample data with coaches from database
$coaches_result = $conn->query("SELECT full_name, phone_number FROM users WHERE role = 'coach' LIMIT 3");
$sample_coaches = [];
while ($coach = $coaches_result->fetch_assoc()) {
    $sample_coaches[] = $coach;
}

$sampleData = [
    ['Nguyễn Văn A', '0901234567', '', '', '01/01/2025', 12, 6000000, 10, 5400000, 'full', 1, 0],
    ['Trần Thị B', '0912345678', '', '', '05/01/2025', 24, 11000000, 15, 9350000, 'installment', 3, 3000000],
    ['Lê Văn C', '0923456789', '', '', '10/01/2025', 36, 15000000, 20, 12000000, 'installment', 4, 4000000]
];

// Fill in coach data for samples
foreach ($sampleData as $i => $row) {
    if (isset($sample_coaches[$i])) {
        $row[2] = $sample_coaches[$i]['full_name'];
        $row[3] = $sample_coaches[$i]['phone_number'];
    }
    
    $rowNum = $i + 2;
    $sheet->fromArray($row, null, 'A' . $rowNum);
    
    // Style data rows
    $sheet->getStyle('A' . $rowNum . ':L' . $rowNum)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ]);
}

// Add instruction sheet
$instructionSheet = $spreadsheet->createSheet();
$instructionSheet->setTitle('Hướng Dẫn');

$instructions = [
    ['🎯 HƯỚNG DẪN SỬ DỤNG TEMPLATE'],
    [''],
    ['1. CẤU TRÚC FILE:'],
    ['   - Sheet 1 (Template Import): Điền thông tin hợp đồng'],
    ['   - Sheet 2 (Hướng Dẫn): Sheet này'],
    [''],
    ['2. CÁC CỘT BẮT BUỘC:'],
    ['   ✅ Tên Học Viên: Họ tên đầy đủ'],
    ['   ✅ SĐT Học Viên: 10-11 số, bắt đầu bằng 0'],
    ['   ✅ SĐT Coach: Phải tồn tại trong hệ thống'],
    ['   ✅ Ngày Bắt Đầu: Định dạng DD/MM/YYYY (ví dụ: 25/12/2024)'],
    ['   ✅ Số Buổi: Số nguyên dương'],
    ['   ✅ Giá Gốc: Số tiền (VNĐ)'],
    [''],
    ['3. CÁC CỘT TÙY CHỌN:'],
    ['   - Tên Coach: Chỉ để tham khảo'],
    ['   - Giảm Giá (%): Mặc định 0'],
    ['   - Giá Cuối: Tự động tính nếu để trống'],
    ['   - Loại TT: "full" (trả 1 lần) hoặc "installment" (trả góp), mặc định "full"'],
    ['   - Số Đợt Trả: Số lần trả (2, 3, 4...), mặc định 1'],
    ['   - Tiền Đặt Cọc: Tiền trả trước (nếu trả góp), mặc định 0'],
    [''],
    ['4. LƯU Ý QUAN TRỌNG:'],
    ['   ⚠️ Số điện thoại coach PHẢI tồn tại trong hệ thống'],
    ['   ⚠️ Hệ thống sẽ tự động tạo tài khoản cho học viên mới'],
    ['   ⚠️ Mật khẩu mặc định = Số điện thoại'],
    ['   ⚠️ Hợp đồng trùng lặp sẽ được bỏ qua'],
    ['   ⚠️ Nếu chọn trả góp, hệ thống sẽ tự động tạo lịch trả góp'],
    ['   ⚠️ Số đợt trả > 1 thì Loại TT phải là "installment"'],
    [''],
    ['5. VÍ DỤ TRẢ GÓP:'],
    ['   - Giá cuối: 12,000,000 VNĐ'],
    ['   - Số đợt trả: 4'],
    ['   - Tiền đặt cọc: 4,000,000 VNĐ'],
    ['   → Còn lại: 8,000,000 VNĐ chia 3 đợt = 2,666,667 VNĐ/đợt'],
    ['   → Đợt 1: Ngày ký (4,000,000)'],
    ['   → Đợt 2-4: Mỗi tháng (2,666,667)'],
    [''],
    ['6. DANH SÁCH COACH HIỆN TẠI:'],
    ['   Tên Coach | SĐT Coach']
];

$instructionSheet->fromArray($instructions, null, 'A1');
$instructionSheet->getColumnDimension('A')->setWidth(60);

// Add coach list
$row = count($instructions) + 1;
$coaches_all = $conn->query("SELECT full_name, phone_number FROM users WHERE role = 'coach' ORDER BY full_name");
while ($coach = $coaches_all->fetch_assoc()) {
    $instructionSheet->setCellValue('A' . $row, '   ' . $coach['full_name'] . ' | ' . $coach['phone_number']);
    $row++;
}

// Style instruction sheet
$instructionSheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4472C4']]
]);

// Set active sheet back to template
$spreadsheet->setActiveSheetIndex(0);

// Output file
$filename = 'Template_Import_HopDong_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>

