<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

// Cập nhật đường dẫn đến db.php
include '../includes/db.php';

// Kiểm tra xem có session_id và contract_id trên URL không
if (isset($_GET['session_id']) && isset($_GET['contract_id'])) {
    $session_id = intval($_GET['session_id']);
    $contract_id = intval($_GET['contract_id']);

    if ($session_id > 0) {
        // Chuẩn bị câu lệnh DELETE an toàn
        $stmt = $conn->prepare("DELETE FROM training_sessions WHERE id = ?");
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Đã xóa buổi tập thành công!'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Lỗi khi xóa buổi tập: ' . $stmt->error
            ];
        }
        $stmt->close();
    }

    // Sau khi xóa, quay trở lại trang chi tiết hợp đồng
    header("Location: ../view_sessions.php?contract_id=" . $contract_id);
    exit;

} else {
    // Nếu thiếu thông tin, quay về trang chủ
    header("Location: ../index.php");
    exit;
}
?>