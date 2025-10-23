<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Cập nhật đường dẫn đến db.php
include 'includes/db.php';

// --- LOGIC ĐIỀU HƯỚNG TUẦN ---
$date_param = isset($_GET['date']) ? $_GET['date'] : 'now';
try {
    $current_date = new DateTime($date_param);
} catch (Exception $e) {
    $current_date = new DateTime('now');
}
$day_of_week = $current_date->format('N');
$start_of_week = clone $current_date;
$start_of_week->modify('-' . ($day_of_week - 1) . ' days');
$end_of_week = clone $start_of_week;
$end_of_week->modify('+6 days');
$prev_week = clone $start_of_week;
$prev_week->modify('-1 week');
$next_week = clone $start_of_week;
$next_week->modify('+1 week');

// --- CÂU LỆNH SQL ---
$sql = "
    SELECT 
        ts.session_datetime,
        client.full_name AS client_name,
        coach.full_name AS coach_name 
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    JOIN users coach ON c.coach_id = coach.id 
    WHERE ts.status = 'scheduled'
      AND DATE(ts.session_datetime) BETWEEN ? AND ? 
";
$stmt = $conn->prepare($sql);
$start_date_str = $start_of_week->format('Y-m-d');
$end_date_str = $end_of_week->format('Y-m-d');
$stmt->bind_param("ss", $start_date_str, $end_date_str);
$stmt->execute();
$result = $stmt->get_result();

$schedule = [];
while ($row = $result->fetch_assoc()) {
    $datetime = new DateTime($row['session_datetime']);
    $day = $datetime->format('N');
    $time = $datetime->format('H:i');
    $schedule[$day][$time][] = [
        'client' => $row['client_name'],
        'coach' => $row['coach_name']
    ];
}

$time_slots = [];
for ($h = 7; $h <= 20; $h++) {
    $time_slots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
}
$days_of_week = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật'];

// Include header với navigation
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h2 class="mb-2 mb-md-0">🗓️ Lịch dạy Toàn bộ Coach</h2>
        <div class="d-flex gap-2">
            <a href="schedule_report.php?date=<?= date('Y-m-d') ?>" class="btn btn-success">📊 Xuất báo cáo lịch</a>
        </div>
    </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <a href="?date=<?= $prev_week->format('Y-m-d') ?>" class="btn btn-outline-primary">&laquo; Tuần trước</a>
                <h5 class="mb-0 text-center">
                    Tuần từ <?= $start_of_week->format('d/m/Y') ?><br>đến <?= $end_of_week->format('d/m/Y') ?>
                </h5>
                <a href="?date=<?= $next_week->format('Y-m-d') ?>" class="btn btn-outline-primary">Tuần sau &raquo;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Giờ</th>
                            <?php foreach ($days_of_week as $day_name): ?>
                                <th><?= $day_name ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($time_slots as $time_slot): ?>
                            <tr>
                                <td><strong><?= $time_slot ?></strong></td>
                                <?php foreach ($days_of_week as $day_num => $day_name): ?>
                                    <td style="vertical-align: top; min-width: 150px;">
                                        <?php 
                                        if (!empty($schedule[$day_num])) {
                                            ksort($schedule[$day_num]);
                                            foreach ($schedule[$day_num] as $time => $sessions) {
                                                // SỬA LỖI: Thay thế hàm str_starts_with() bằng cách so sánh giờ
                                                $session_hour = (int)substr($time, 0, 2);
                                                $slot_hour = (int)substr($time_slot, 0, 2);

                                                if ($session_hour == $slot_hour) {
                                                    foreach ($sessions as $session) {
                                                        echo '<div class="alert alert-info p-2 mb-1" style="font-size: 0.9em;">';
                                                        echo '<strong>' . htmlspecialchars($session['client']) . '</strong><br>';
                                                        echo '<small>' . htmlspecialchars($time) . ' - ' . htmlspecialchars($session['coach']) . '</small>';
                                                        echo '</div>';
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>

<?php include 'includes/footer.php'; ?>