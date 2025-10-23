<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $contract_id = intval($_POST['contract_id']);
        $coach_id = intval($_POST['coach_id']);
        $start_date_str = $_POST['start_date'];
        // ĐÃ XÓA: package_name - Không cần cập nhật tên gói
        $total_sessions = intval($_POST['total_sessions']);
        $total_price = intval($_POST['total_price']);
        $discount_percentage = intval($_POST['discount_percentage']);
        $final_price = intval($_POST['final_price']);

        $stmt_update = $conn->prepare("UPDATE contracts SET coach_id = ?, start_date = ?, total_sessions = ?, total_price = ?, discount_percentage = ?, final_price = ? WHERE id = ?");
        $stmt_update->bind_param("isiiiiii", $coach_id, $start_date_str, $total_sessions, $total_price, $discount_percentage, $final_price, $contract_id);
        $stmt_update->execute();
        $stmt_update->close();

        if (isset($_POST['regenerate_schedule'])) {
            $schedule_start_date_str = !empty($_POST['schedule_start_date']) ? $_POST['schedule_start_date'] : $start_date_str;
            
            $stmt_delete = $conn->prepare("DELETE FROM training_sessions WHERE contract_id = ? AND status = 'scheduled'");
            $stmt_delete->bind_param("i", $contract_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            $stmt_count = $conn->prepare("SELECT COUNT(id) as completed_count FROM training_sessions WHERE contract_id = ? AND status = 'completed'");
            $stmt_count->bind_param("i", $contract_id);
            $stmt_count->execute();
            $completed_count = $stmt_count->get_result()->fetch_assoc()['completed_count'];
            $stmt_count->close();

            $sessions_to_create = $total_sessions - $completed_count;

            if ($sessions_to_create > 0 && isset($_POST['schedule_days']) && !empty($_POST['schedule_days'][0])) {
                $schedule_days = $_POST['schedule_days'];
                $schedule_times = $_POST['schedule_times'];
                $sessions_created = 0;
                $current_date = new DateTime($schedule_start_date_str, new DateTimeZone('Asia/Ho_Chi_Minh'));
                $current_date->setTime(0, 0, 0);

                $schedule_map = [];
                for ($i = 0; $i < count($schedule_days); $i++) {
                    if (!empty($schedule_days[$i]) && !empty($schedule_times[$i])) {
                        $schedule_map[$schedule_days[$i]][] = $schedule_times[$i];
                    }
                }

                if (!empty($schedule_map)) {
                    $insert_session_stmt = $conn->prepare("INSERT INTO training_sessions (contract_id, session_datetime, status) VALUES (?, ?, 'scheduled')");
                    for ($day_offset = 0; $day_offset < 365 && $sessions_created < $sessions_to_create; $day_offset++) {
                        $check_date = clone $current_date;
                        $check_date->modify("+$day_offset days");
                        $day_of_week_num = $check_date->format('N');
                        if (isset($schedule_map[$day_of_week_num])) {
                            foreach ($schedule_map[$day_of_week_num] as $time) {
                                if ($sessions_created < $sessions_to_create) {
                                    $session_datetime_str = $check_date->format('Y-m-d') . ' ' . $time;
                                    $insert_session_stmt->bind_param("is", $contract_id, $session_datetime_str);
                                    $insert_session_stmt->execute();
                                    $sessions_created++;
                                } else {
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        $conn->commit();
        header("Location: ../index.php"); // Sửa đường dẫn chuyển hướng
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "Lỗi khi cập nhật: " . $e->getMessage();
    }
} else {
    header("Location: ../index.php"); // Sửa đường dẫn chuyển hướng
    exit;
}
$conn->close();
?>