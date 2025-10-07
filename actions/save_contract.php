<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

// CẬP NHẬT: Sửa đường dẫn file cho đúng với cấu trúc thư mục
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy thông tin học viên
    $client_full_name = trim($_POST['client_full_name']);
    $client_phone_number = trim($_POST['client_phone_number']);
    
    // Lấy thông tin hợp đồng
    $coach_id = intval($_POST['coach_id']);
    $start_date_str = $_POST['start_date'];
    $package_choice = $_POST['package_name'];
    $total_sessions = intval($_POST['total_sessions']);
    
    // CẬP NHẬT: Lấy đầy đủ thông tin giá và giảm giá
    $total_price = intval($_POST['total_price']);
    $discount_percentage = intval($_POST['discount_percentage']);
    $final_price = intval($_POST['final_price']);
    
    // CẬP NHẬT: Lấy ngày bắt đầu tạo lịch, nếu trống thì dùng ngày bắt đầu hợp đồng
    $schedule_start_date_str = !empty($_POST['schedule_start_date']) ? $_POST['schedule_start_date'] : $start_date_str;
    
    $conn->begin_transaction();

    try {
        // === BƯỚC 1: XỬ LÝ TÀI KHOẢN HỌC VIÊN ===
        $client_id_for_contract = 0;
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt_check->bind_param("s", $client_phone_number);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            $existing_client = $result->fetch_assoc();
            $client_id_for_contract = $existing_client['id'];
        } else {
            $default_password = password_hash($client_phone_number, PASSWORD_DEFAULT);
            $client_role = 'client';
            $stmt_create = $conn->prepare("INSERT INTO users (phone_number, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt_create->bind_param("ssss", $client_phone_number, $default_password, $client_full_name, $client_role);
            $stmt_create->execute();

            $new_client_id = $stmt_create->insert_id;
            if ($new_client_id > 0) {
                $client_id_for_contract = $new_client_id;
            } else {
                throw new Exception("Không thể tạo tài khoản học viên mới. Lỗi: " . $stmt_create->error);
            }
            $stmt_create->close();
        }
        $stmt_check->close();
        
        if ($client_id_for_contract <= 0) {
            throw new Exception("ID của học viên không hợp lệ.");
        }

        // === BƯỚC 2: TẠO HỢP ĐỒNG ===
        $package_to_save = ($package_choice === 'other' && !empty($_POST['custom_package_name'])) 
            ? trim($_POST['custom_package_name']) 
            : $package_choice;
        
        $stmt_contract = $conn->prepare("INSERT INTO contracts (client_id, coach_id, start_date, package_name, total_sessions, total_price, discount_percentage, final_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt_contract->bind_param("iissiiis", $client_id_for_contract, $coach_id, $start_date_str, $package_to_save, $total_sessions, $total_price, $discount_percentage, $final_price);
        $stmt_contract->execute();
        $new_contract_id = $stmt_contract->insert_id;
        $stmt_contract->close();

        // === BƯỚC 3: TẠO LỊCH TẬP HÀNG LOẠT ===
        if (isset($_POST['schedule_days']) && isset($_POST['schedule_times'])) {
            $schedule_days = $_POST['schedule_days'];
            $schedule_times = $_POST['schedule_times'];
            
            $sessions_created = 0;
            // CẬP NHẬT: Sử dụng biến $schedule_start_date_str để bắt đầu vòng lặp
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
                for ($day_offset = 0; $day_offset < 365 && $sessions_created < $total_sessions; $day_offset++) {
                    $check_date = clone $current_date;
                    $check_date->modify("+$day_offset days");
                    $day_of_week_num = $check_date->format('N');
                    if (isset($schedule_map[$day_of_week_num])) {
                        foreach ($schedule_map[$day_of_week_num] as $time) {
                            if ($sessions_created < $total_sessions) {
                                $session_datetime_str = $check_date->format('Y-m-d') . ' ' . $time;
                                $insert_session_stmt->bind_param("is", $new_contract_id, $session_datetime_str);
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

        $conn->commit();
        // CẬP NHẬT: Sửa đường dẫn chuyển hướng cho đúng
        header("Location: ../index.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "<h1>Giao dịch thất bại</h1>";
        echo "<p>Lỗi chi tiết: " . $e->getMessage() . "</p>";
    }
}
$conn->close();
?>