<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

include '../includes/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Xóa an toàn với prepared statement
    $sql = "DELETE FROM contracts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Lỗi: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Thiếu ID để xóa!";
}
$conn->close();
?>