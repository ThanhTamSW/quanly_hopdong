<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../vendor/autoload.php';
require_once '../includes/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    // Check if file uploaded
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Vui lòng chọn file Excel');
    }
    
    $file = $_FILES['excel_file'];
    $allowed = ['xlsx', 'xls'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($ext), $allowed)) {
        throw new Exception('File phải có định dạng .xlsx hoặc .xls');
    }
    
    // Load spreadsheet
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    $previewData = [];
    $statistics = [
        'total' => count($rows),
        'valid' => 0,
        'duplicates' => 0,
        'invalid' => 0
    ];
    
    $skip_duplicates = isset($_POST['skip_duplicates']);
    $existing_contracts = [];
    
    // Get existing contracts for duplicate check
    if ($skip_duplicates) {
        $result = $conn->query("
            SELECT CONCAT(u.phone_number, '-', c.start_date) as key_check
            FROM contracts c
            JOIN users u ON c.client_id = u.id
        ");
        while ($row = $result->fetch_assoc()) {
            $existing_contracts[$row['key_check']] = true;
        }
    }
    
    // Get all coaches for validation
    $coaches = [];
    $coach_result = $conn->query("SELECT id, full_name, phone_number FROM users WHERE role = 'coach'");
    while ($coach = $coach_result->fetch_assoc()) {
        $coaches[trim($coach['phone_number'])] = $coach;
    }
    
    // Process each row
    foreach ($rows as $index => $row) {
        // Skip empty rows
        if (empty($row[0]) && empty($row[1])) {
            continue;
        }
        
        $rowData = [
            'client_name' => trim($row[0] ?? ''),
            'client_phone' => trim($row[1] ?? ''),
            'coach_name' => trim($row[2] ?? ''),
            'coach_phone' => trim($row[3] ?? ''),
            'start_date' => trim($row[4] ?? ''),
            'total_sessions' => (int)($row[5] ?? 0),
            'total_price' => (float)($row[6] ?? 0),
            'discount_percentage' => (float)($row[7] ?? 0),
            'final_price' => (float)($row[8] ?? 0),
            'status' => 'valid',
            'error' => ''
        ];
        
        // Validate
        $errors = [];
        
        // Validate client name
        if (empty($rowData['client_name'])) {
            $errors[] = 'Thiếu tên học viên';
        }
        
        // Validate client phone
        if (empty($rowData['client_phone']) || !preg_match('/^0\d{9,10}$/', $rowData['client_phone'])) {
            $errors[] = 'Số điện thoại học viên không hợp lệ';
        }
        
        // Validate coach phone
        if (empty($rowData['coach_phone']) || !isset($coaches[$rowData['coach_phone']])) {
            $errors[] = 'Coach không tồn tại (SĐT: ' . $rowData['coach_phone'] . ')';
        } else {
            $rowData['coach_id'] = $coaches[$rowData['coach_phone']]['id'];
        }
        
        // Validate date
        if (!empty($rowData['start_date'])) {
            // Try to parse date in various formats
            $date = parseDate($rowData['start_date']);
            if ($date) {
                $rowData['start_date'] = $date;
            } else {
                $errors[] = 'Ngày không đúng định dạng (DD/MM/YYYY)';
            }
        } else {
            $errors[] = 'Thiếu ngày bắt đầu';
        }
        
        // Validate sessions
        if ($rowData['total_sessions'] <= 0) {
            $errors[] = 'Số buổi phải > 0';
        }
        
        // Validate price
        if ($rowData['total_price'] <= 0) {
            $errors[] = 'Giá gốc phải > 0';
        }
        
        // Calculate final price if not provided
        if ($rowData['final_price'] <= 0) {
            $rowData['final_price'] = $rowData['total_price'] * (1 - $rowData['discount_percentage'] / 100);
        }
        
        // Check duplicate
        $duplicate_key = $rowData['client_phone'] . '-' . $rowData['start_date'];
        if ($skip_duplicates && isset($existing_contracts[$duplicate_key])) {
            $rowData['status'] = 'duplicate';
            $rowData['error'] = 'Hợp đồng đã tồn tại';
            $statistics['duplicates']++;
        } else if (!empty($errors)) {
            $rowData['status'] = 'error';
            $rowData['error'] = implode(', ', $errors);
            $statistics['invalid']++;
        } else {
            $statistics['valid']++;
        }
        
        $previewData[] = $rowData;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $previewData,
        'statistics' => $statistics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function parseDate($dateStr) {
    // Try DD/MM/YYYY
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }
    
    // Try YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateStr)) {
        return $dateStr;
    }
    
    // Try Excel serial date
    if (is_numeric($dateStr)) {
        $unix_date = ($dateStr - 25569) * 86400;
        return date('Y-m-d', $unix_date);
    }
    
    return false;
}
?>

