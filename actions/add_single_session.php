<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract_id = intval($_POST['contract_id']);
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];

    if (empty($contract_id) || empty($session_date) || empty($session_time)) {
        die("Vui lòng điền đầy đủ thông tin.");
    }

    // Kết hợp ngày và giờ thành một chuỗi datetime
    $session_datetime_str = $session_date . ' ' . $session_time;

    // --- KIỂM TRA BẢO MẬT: Đảm bảo hợp đồng này vẫn còn buổi ---
    $stmt_check = $conn->prepare("
        SELECT c.total_sessions, 
               (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') as sessions_completed 
        FROM contracts c 
        WHERE c.id = ?
    ");
    $stmt_check->bind_param("i", $contract_id);
    $stmt_check->execute();
    $contract = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($contract && ($contract['total_sessions'] > $contract['sessions_completed'])) {
        // Nếu còn buổi, tiến hành thêm
        $stmt_insert = $conn->prepare("INSERT INTO training_sessions (contract_id, session_datetime, status) VALUES (?, ?, 'scheduled')");
        $stmt_insert->bind_param("is", $contract_id, $session_datetime_str);
        $stmt_insert->execute();
        $stmt_insert->close();
    } else {
        // Có thể thêm một thông báo lỗi ở đây
        // Ví dụ: $_SESSION['error_message'] = "Hợp đồng đã hết số buổi tập.";
    }

    // Quay trở lại trang chi tiết hợp đồng
    header("Location: view_sessions.php?contract_id=" . $contract_id);
    exit;
}
?>