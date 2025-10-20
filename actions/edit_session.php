<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}
// Cập nhật đường dẫn đến db.php
include '../includes/db.php';

// Bắt buộc phải có session_id và contract_id trên URL
if (!isset($_GET['session_id']) || !isset($_GET['contract_id'])) {
    header("Location: ../index.php");
    exit;
}

$session_id = intval($_GET['session_id']);
$contract_id = intval($_GET['contract_id']);

// Lấy thông tin ngày giờ hiện tại của buổi tập
$stmt = $conn->prepare("SELECT session_datetime FROM training_sessions WHERE id = ?");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    die("Không tìm thấy buổi tập.");
}

// Tách ngày và giờ ra từ chuỗi datetime
$datetime = new DateTime($session['session_datetime']);
$session_date = $datetime->format('Y-m-d');
$session_time = $datetime->format('H:i');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa buổi tập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
        <div class="card-header bg-warning">
            <h4>✏️ Sửa Lịch tập</h4>
        </div>
        <div class="card-body">
            <form action="update_single_session.php" method="POST">
                <input type="hidden" name="session_id" value="<?= $session_id ?>">
                <input type="hidden" name="contract_id" value="<?= $contract_id ?>">
                
                <div class="mb-3">
                    <label for="session_date" class="form-label">Ngày tập mới</label>
                    <input type="date" name="session_date" id="session_date" class="form-control" value="<?= $session_date ?>" required>
                </div>
                <div class="mb-3">
                    <label for="session_time" class="form-label">Giờ tập mới</label>
                    <input type="time" name="session_time" id="session_time" class="form-control" value="<?= $session_time ?>" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <a href="../view_sessions.php?contract_id=<?= $contract_id ?>" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>