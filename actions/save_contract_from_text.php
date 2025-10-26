<?php
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập!']);
    exit;
}

if (!in_array($_SESSION['role'], ['admin', 'coach'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn không có quyền thực hiện thao tác này!']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ!']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Validate required fields
$required = ['client_name', 'client_phone', 'start_date', 'total_sessions', 'total_price', 'coach_id', 'schedule'];
foreach ($required as $field) {
    if (!isset($input[$field]) || (is_string($input[$field]) && empty($input[$field]))) {
        echo json_encode(['success' => false, 'error' => "Thiếu thông tin: $field"]);
        exit;
    }
}

// Extract data
$client_name = trim($input['client_name']);
$client_phone = trim($input['client_phone']);
$start_date = $input['start_date'];
$total_sessions = intval($input['total_sessions']);
$total_price = floatval($input['total_price']);
$discount_percentage = intval($input['discount_percentage'] ?? 0);
$final_price = floatval($input['final_price']);
$coach_id = intval($input['coach_id']);
$schedule = $input['schedule'];

// Validate
if ($total_sessions <= 0) {
    echo json_encode(['success' => false, 'error' => 'Tổng số buổi phải lớn hơn 0!']);
    exit;
}

if ($total_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Giá gốc phải lớn hơn 0!']);
    exit;
}

if (!preg_match('/^0\d{9}$/', $client_phone)) {
    echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ!']);
    exit;
}

if (empty($schedule) || !is_array($schedule)) {
    echo json_encode(['success' => false, 'error' => 'Lịch tập không hợp lệ!']);
    exit;
}

// Recalculate final_price to be sure
if ($final_price == 0 && $total_price > 0) {
    $final_price = $total_price * (1 - $discount_percentage / 100);
}

try {
    $conn->begin_transaction();
    
    // 1. Kiểm tra xem client đã tồn tại chưa (theo SĐT)
    $stmt_check_client = $conn->prepare("SELECT id, full_name FROM users WHERE phone_number = ? AND role = 'client'");
    $stmt_check_client->bind_param("s", $client_phone);
    $stmt_check_client->execute();
    $client_result = $stmt_check_client->get_result();
    
    if ($client_result->num_rows > 0) {
        // Client đã tồn tại
        $client_data = $client_result->fetch_assoc();
        $client_id = $client_data['id'];
        
        // Cập nhật tên nếu khác
        if ($client_data['full_name'] !== $client_name) {
            $stmt_update_client = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt_update_client->bind_param("si", $client_name, $client_id);
            $stmt_update_client->execute();
            $stmt_update_client->close();
        }
    } else {
        // Tạo client mới
        $stmt_create_client = $conn->prepare("INSERT INTO users (full_name, phone_number, role) VALUES (?, ?, 'client')");
        $stmt_create_client->bind_param("ss", $client_name, $client_phone);
        $stmt_create_client->execute();
        $client_id = $stmt_create_client->insert_id;
        $stmt_create_client->close();
    }
    $stmt_check_client->close();
    
    // 2. Tạo package_name
    $package_name = "Gói $total_sessions buổi";
    
    // 3. Insert contract
    $payment_type = 'full';
    $paid_amount = 0;
    
    // Chỉ dùng new_coach_id (tham chiếu coaches table), coach_id = NULL
    $stmt_contract = $conn->prepare("INSERT INTO contracts (client_id, coach_id, new_coach_id, start_date, package_name, total_sessions, total_price, discount_percentage, final_price, payment_type, paid_amount, status) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt_contract->bind_param("iissiddisi", $client_id, $coach_id, $start_date, $package_name, $total_sessions, $total_price, $discount_percentage, $final_price, $payment_type, $paid_amount);
    
    if (!$stmt_contract->execute()) {
        throw new Exception("Lỗi khi tạo hợp đồng: " . $stmt_contract->error);
    }
    
    $contract_id = $stmt_contract->insert_id;
    $stmt_contract->close();
    
    // 4. Tạo lịch tập
    $day_map = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 0
    ];
    
    $stmt_session = $conn->prepare("INSERT INTO training_sessions (contract_id, session_datetime, status) VALUES (?, ?, 'scheduled')");
    
    $current_date = new DateTime($start_date);
    $sessions_created = 0;
    $max_iterations = $total_sessions * 10; // Prevent infinite loop
    $iterations = 0;
    
    while ($sessions_created < $total_sessions && $iterations < $max_iterations) {
        $iterations++;
        $current_day_of_week = (int)$current_date->format('w'); // 0 = Sunday, 1 = Monday, ...
        
        // Check if current day matches any schedule day
        foreach ($schedule as $schedule_item) {
            $schedule_day = $day_map[$schedule_item['day']];
            
            if ($current_day_of_week === $schedule_day) {
                $date_str = $current_date->format('Y-m-d');
                $time_str = $schedule_item['time'];
                $datetime_str = $date_str . ' ' . $time_str . ':00'; // YYYY-MM-DD HH:MM:SS
                
                $stmt_session->bind_param("is", $contract_id, $datetime_str);
                $stmt_session->execute();
                
                $sessions_created++;
                
                if ($sessions_created >= $total_sessions) {
                    break;
                }
            }
        }
        
        // Move to next day
        $current_date->modify('+1 day');
    }
    
    $stmt_session->close();
    
    if ($sessions_created < $total_sessions) {
        throw new Exception("Chỉ tạo được $sessions_created/$total_sessions buổi tập. Vui lòng kiểm tra lịch tập.");
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'contract_id' => $contract_id,
        'sessions_created' => $sessions_created,
        'message' => "Đã tạo hợp đồng và $sessions_created buổi tập thành công!"
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>

