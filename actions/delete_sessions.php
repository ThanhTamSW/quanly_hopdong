<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

// SỬA LỖI 1: Cập nhật đường dẫn đến file db.php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract_id = intval($_POST['contract_id']);

    if (isset($_POST['session_ids']) && is_array($_POST['session_ids'])) {
        $session_ids = $_POST['session_ids'];
        
        $sanitized_ids = array_map('intval', $session_ids);

        // Lọc ra các ID hợp lệ (khác 0) để tránh lỗi
        $valid_ids = array_filter($sanitized_ids, function($id) {
            return $id > 0;
        });

        if (!empty($valid_ids)) {
            $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
            
            $sql = "DELETE FROM training_sessions WHERE id IN ($placeholders)";
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('i', count($valid_ids));
            $stmt->bind_param($types, ...$valid_ids);
            
            $stmt->execute();
            $stmt->close();
        }
    }

    // SỬA LỖI 2: Cập nhật đường dẫn chuyển hướng
    header("Location: ../view_sessions.php?contract_id=" . $contract_id);
    exit;
}
?>