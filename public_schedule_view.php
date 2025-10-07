<?php
// Cập nhật đường dẫn đến db.php
include 'includes/db.php';

// Bắt buộc phải có contract_id trên URL
if (!isset($_GET['contract_id'])) {
    die("Thiếu thông tin hợp đồng.");
}
$contract_id = intval($_GET['contract_id']);

// --- LOGIC ĐIỀU HƯỚNG TUẦN (Tương tự lịch của Coach) ---
$date_param = isset($_GET['date']) ? $_GET['date'] : 'now';
try { $current_date = new DateTime($date_param); } catch (Exception $e) { $current_date = new DateTime('now'); }
$day_of_week = $current_date->format('N');
$start_of_week = clone $current_date;
$start_of_week->modify('-' . ($day_of_week - 1) . ' days');
$end_of_week = clone $start_of_week;
$end_of_week->modify('+6 days');
$prev_week = clone $start_of_week; $prev_week->modify('-1 week');
$next_week = clone $start_of_week; $next_week->modify('+1 week');

// Lấy thông tin hợp đồng và lịch tập
$sql = "
    SELECT 
        ts.session_datetime, 
        coach.full_name AS coach_name,
        client.full_name AS client_name
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users coach ON c.coach_id = coach.id 
    JOIN users client ON c.client_id = client.id
    WHERE c.id = ? -- Lọc theo ID của hợp đồng
      AND ts.status = 'scheduled'
      AND DATE(ts.session_datetime) BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$start_date_str = $start_of_week->format('Y-m-d');
$end_date_str = $end_of_week->format('Y-m-d');
$stmt->bind_param("iss", $contract_id, $start_date_str, $end_date_str);
$stmt->execute();
$result = $stmt->get_result();

$schedule = [];
$client_name_display = 'Học viên'; // Tên mặc định
while ($row = $result->fetch_assoc()) {
    $datetime = new DateTime($row['session_datetime']);
    $day = $datetime->format('N');
    $time = $datetime->format('H:i');
    $schedule[$day][$time] = $row['coach_name'];
    $client_name_display = $row['client_name']; // Lấy tên học viên để hiển thị
}

$time_slots = []; for ($h = 7; $h <= 20; $h++) { $time_slots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; }
$days_of_week = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch tập của <?= htmlspecialchars($client_name_display) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="text-center">🗓️ Lịch tập của bạn <?= htmlspecialchars($client_name_display) ?></h2>
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <a href="?contract_id=<?= $contract_id ?>&date=<?= $prev_week->format('Y-m-d') ?>" class="btn btn-outline-primary">&laquo; Tuần trước</a>
            <h5 class="mb-0 text-center">Tuần từ <?= $start_of_week->format('d/m/Y') ?> đến <?= $end_of_week->format('d/m/Y') ?></h5>
            <a href="?contract_id=<?= $contract_id ?>&date=<?= $next_week->format('Y-m-d') ?>" class="btn btn-outline-primary">Tuần sau &raquo;</a>
        </div>
        <div class="table-responsive">
            </div>
    </div>
</div>
</body>
</html>