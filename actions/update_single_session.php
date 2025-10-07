<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

// Cập nhật đường dẫn đến db.php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = intval($_POST['session_id']);
    $contract_id = intval($_POST['contract_id']);
    $new_date = $_POST['session_date'];
    $new_time = $_POST['session_time'];

    if (empty($session_id) || empty($contract_id) || empty($new_date) || empty($new_time)) {
        die("Thiếu thông tin.");
    }

    // Ghép ngày và giờ mới thành một chuỗi datetime
    $new_datetime = $new_date . ' ' . $new_time;

    // Cập nhật lại buổi tập trong database
    $stmt = $conn->prepare("UPDATE training_sessions SET session_datetime = ? WHERE id = ?");
    $stmt->bind_param("si", $new_datetime, $session_id);
    
    if ($stmt->execute()) {
        // Nếu thành công, quay về trang chi tiết hợp đồng
        header("Location: view_sessions.php?contract_id=" . $contract_id);
        exit;
    } else {
        echo "Lỗi khi cập nhật buổi tập: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>