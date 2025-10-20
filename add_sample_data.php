<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$coach_id = $_SESSION['user_id'];

// Kiểm tra xem đã có dữ liệu chưa
$check_sql = "SELECT COUNT(*) as count FROM contracts WHERE coach_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($result['count'] > 0) {
    echo "<h2>Đã có dữ liệu rồi!</h2>";
    echo "<p>Số hợp đồng: " . $result['count'] . "</p>";
    echo "<a href='test_data.php'>Xem dữ liệu</a>";
    exit;
}

// Tạo dữ liệu mẫu
try {
    $conn->begin_transaction();
    
    // Tạo học viên mẫu
    $client_sql = "INSERT INTO users (full_name, phone_number, role) VALUES (?, ?, 'client')";
    $stmt = $conn->prepare($client_sql);
    
    $clients = [
        ['Lý Nguyên Khang', '0123456789'],
        ['Lê Nguyên Khang', '0987654321'],
        ['Gia Huy', '0111222333'],
        ['Huy Khải', '0444555666'],
        ['Minh Quân', '0777888999']
    ];
    
    $client_ids = [];
    foreach ($clients as $client) {
        $stmt->bind_param("ss", $client[0], $client[1]);
        $stmt->execute();
        $client_ids[] = $conn->insert_id;
    }
    $stmt->close();
    
    // Tạo hợp đồng mẫu
    $contract_sql = "INSERT INTO contracts (client_id, coach_id, package_name, total_sessions, final_price, status, start_date) VALUES (?, ?, ?, ?, ?, 'active', ?)";
    $stmt = $conn->prepare($contract_sql);
    
    $contracts = [
        [$client_ids[0], $coach_id, 'Gói cơ bản', 8, 2000000, date('Y-m-d')],
        [$client_ids[1], $coach_id, 'Gói nâng cao', 12, 3000000, date('Y-m-d')],
        [$client_ids[2], $coach_id, 'Gói VIP', 20, 5000000, date('Y-m-d')],
        [$client_ids[3], $coach_id, 'Gói premium', 15, 4000000, date('Y-m-d')],
        [$client_ids[4], $coach_id, 'Gói standard', 10, 2500000, date('Y-m-d')]
    ];
    
    $contract_ids = [];
    foreach ($contracts as $contract) {
        $stmt->bind_param("iisdis", $contract[0], $contract[1], $contract[2], $contract[3], $contract[4], $contract[5]);
        $stmt->execute();
        $contract_ids[] = $conn->insert_id;
    }
    $stmt->close();
    
    // Tạo buổi tập mẫu cho hôm nay
    $session_sql = "INSERT INTO training_sessions (contract_id, session_datetime, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($session_sql);
    
    $today = date('Y-m-d');
    $sessions_today = [
        [$contract_ids[0], $today . ' 11:00:00', 'completed'],
        [$contract_ids[1], $today . ' 16:00:00', 'scheduled']
    ];
    
    foreach ($sessions_today as $session) {
        $stmt->bind_param("iss", $session[0], $session[1], $session[2]);
        $stmt->execute();
    }
    
    // Tạo buổi tập mẫu cho ngày mai
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $sessions_tomorrow = [
        [$contract_ids[2], $tomorrow . ' 16:30:00', 'scheduled'],
        [$contract_ids[3], $tomorrow . ' 17:00:00', 'scheduled'],
        [$contract_ids[4], $tomorrow . ' 18:00:00', 'scheduled']
    ];
    
    foreach ($sessions_tomorrow as $session) {
        $stmt->bind_param("iss", $session[0], $session[1], $session[2]);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->commit();
    
    echo "<h2>✅ Đã tạo dữ liệu mẫu thành công!</h2>";
    echo "<p>Đã tạo " . count($clients) . " học viên và " . count($contracts) . " hợp đồng</p>";
    echo "<p>Đã tạo " . (count($sessions_today) + count($sessions_tomorrow)) . " buổi tập</p>";
    echo "<a href='test_data.php'>Xem dữ liệu</a>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<h2>❌ Lỗi: " . $e->getMessage() . "</h2>";
}

$conn->close();
?>
