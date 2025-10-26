<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập để thực hiện thao tác này.");
}

// Cho phép cả admin và coach xóa hợp đồng
if (!in_array($_SESSION['role'], ['admin', 'coach'])) {
    die("Bạn không có quyền truy cập.");
}

include '../includes/db.php';

// Kiểm tra cả POST và GET
$contract_id = 0;
if (isset($_POST['contract_id'])) {
    $contract_id = intval($_POST['contract_id']);
} elseif (isset($_GET['id'])) {
    $contract_id = intval($_GET['id']);
}

if ($contract_id > 0) {
    // Xóa an toàn với prepared statement
    $sql = "DELETE FROM contracts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $contract_id);
    
    if ($stmt->execute()) {
        header("Location: ../index.php?deleted=success");
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