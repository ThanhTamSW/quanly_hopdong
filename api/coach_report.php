<?php
// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh làm hỏng JSON

session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    include '../includes/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Lấy thông tin Coach đang đăng nhập
    $coach_id = $_SESSION['user_id'];
    $coach_name = $_SESSION['full_name'];

    // Lấy tham số ngày
    $date_param = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    try {
        $current_date = new DateTime($date_param);
    } catch (Exception $e) {
        $current_date = new DateTime('now');
    }

$current_date_str = $current_date->format('Y-m-d');
$next_date = clone $current_date;
$next_date->modify('+1 day');
$next_date_str = $next_date->format('Y-m-d');

// Bước 1: Lấy lịch dạy ngày hiện tại (đã xác nhận/hoàn thành)
$sql_today = "
    SELECT 
        ts.session_datetime,
        ts.status,
        client.full_name AS client_name,
        c.package_name,
        c.total_sessions,
        GREATEST(
            (SELECT COUNT(*) FROM training_sessions ts2 WHERE ts2.contract_id = c.id AND ts2.status = 'completed'),
            (SELECT COUNT(*) FROM payroll_log pl WHERE pl.contract_id = c.id)
        ) AS sessions_completed
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ? 
    AND DATE(ts.session_datetime) = ?
    ORDER BY ts.session_datetime ASC
";

$stmt_today = $conn->prepare($sql_today);
if (!$stmt_today) {
    die("Prepare failed: " . $conn->error);
}

$stmt_today->bind_param("is", $coach_id, $current_date_str);
if (!$stmt_today->execute()) {
    die("Execute failed: " . $stmt_today->error);
}
$result_today = $stmt_today->get_result();

$today_sessions = [];
while ($row = $result_today->fetch_assoc()) {
    $datetime = new DateTime($row['session_datetime']);
    $time = $datetime->format('H:i');
    
    $today_sessions[] = [
        'time' => $time,
        'client' => $row['client_name'],
        'package' => $row['package_name'],
        'status' => $row['status'],
        'sessions_completed' => $row['sessions_completed'],
        'total_sessions' => $row['total_sessions']
    ];
}

// Bước 2: Lấy lịch dạy ngày kế tiếp
$sql_next = "
    SELECT 
        ts.session_datetime,
        client.full_name AS client_name,
        c.package_name,
        c.total_sessions,
        GREATEST(
            (SELECT COUNT(*) FROM training_sessions ts2 WHERE ts2.contract_id = c.id AND ts2.status = 'completed'),
            (SELECT COUNT(*) FROM payroll_log pl WHERE pl.contract_id = c.id)
        ) AS sessions_completed
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ? 
    AND DATE(ts.session_datetime) = ?
    AND ts.status = 'scheduled'
    ORDER BY ts.session_datetime ASC
";

$stmt_next = $conn->prepare($sql_next);
$stmt_next->bind_param("is", $coach_id, $next_date_str);
$stmt_next->execute();
$result_next = $stmt_next->get_result();

$next_sessions = [];
while ($row = $result_next->fetch_assoc()) {
    $datetime = new DateTime($row['session_datetime']);
    $time = $datetime->format('H:i');
    
    $next_sessions[] = [
        'time' => $time,
        'client' => $row['client_name'],
        'package' => $row['package_name'],
        'sessions_completed' => $row['sessions_completed'],
        'total_sessions' => $row['total_sessions']
    ];
}

// Bước 3: Tạo báo cáo theo format
$report_text = "";

// Header ngày hiện tại
$report_text .= "Report " . $current_date->format('d/m') . "\n";

// Luôn hiển thị "Bài MKT cá nhân : done" mặc định
$report_text .= "Bài MKT cá nhân : done\n\n";

// Kết show ngày hiện tại
$report_text .= "Kết show " . $current_date->format('d/m') . "\n";
if (!empty($today_sessions)) {
    // Sắp xếp theo giờ
    usort($today_sessions, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
    
    foreach ($today_sessions as $session) {
        $status_text = "";
        switch ($session['status']) {
            case 'completed':
                $status_text = " (done)";
                break;
            case 'scheduled':
                $status_text = " (scheduled)";
                break;
            case 'cancelled':
                $status_text = " (cancelled)";
                break;
        }
        $report_text .= $session['time'] . " " . $session['client'] . $status_text . "\n";
    }
} else {
    $report_text .= "Không có buổi tập nào\n";
}


// Timeline ngày kế tiếp
$report_text .= "\ntimeline " . $next_date->format('d/m') . "\n";
if (!empty($next_sessions)) {
    foreach ($next_sessions as $session) {
        $remaining = $session['total_sessions'] - $session['sessions_completed'];
        $report_text .= $session['time'] . " " . $session['client'] . " - " . $remaining . " buổi còn lại\n";
    }
} else {
    $report_text .= "Không có buổi tập nào\n";
}

// Trả về kết quả
$response = [
    'coachName' => $coach_name,
    'reportText' => $report_text,
    'currentDate' => $current_date_str,
    'nextDate' => $next_date_str
];

// Chỉ thêm debug nếu không có lỗi
if (empty($report_text)) {
    $response['debug'] = [
        'today_sessions_count' => count($today_sessions),
        'next_sessions_count' => count($next_sessions),
        'coach_id' => $coach_id,
        'current_date_str' => $current_date_str
    ];
}

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
