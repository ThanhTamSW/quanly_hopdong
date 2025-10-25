<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập để thực hiện thao tác này.");
}

// Cho phép cả admin và coach thêm hợp đồng
if (!in_array($_SESSION['role'], ['admin', 'coach'])) {
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
    // ĐÃ XÓA: package_name - Không cần lưu tên gói nữa
    $total_sessions = intval($_POST['total_sessions']);
    
    // CẬP NHẬT: Lấy đầy đủ thông tin giá và giảm giá
    $total_price = intval($_POST['total_price']);
    $discount_percentage = intval($_POST['discount_percentage']);
    $final_price = intval($_POST['final_price']);
    
    // Lấy thông tin thanh toán
    $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'full';
    $paid_amount = ($payment_type === 'full') ? $final_price : 0;
    
    // Lấy thông tin trả góp (nếu có)
    $installment_percentages = isset($_POST['installment_percentages']) ? $_POST['installment_percentages'] : [];
    $installment_amounts = isset($_POST['installment_amounts']) ? $_POST['installment_amounts'] : [];
    $installment_dates = isset($_POST['installment_dates']) ? $_POST['installment_dates'] : [];
    
    // CẬP NHẬT: Lấy ngày bắt đầu tạo lịch, nếu trống thì dùng ngày bắt đầu hợp đồng
    $schedule_start_date_str = !empty($_POST['schedule_start_date']) ? $_POST['schedule_start_date'] : $start_date_str;
    
    $conn->begin_transaction();

    try {
        // === BƯỚC 1: TẠO TÀI KHOẢN HỌC VIÊN MỚI CHO MỖI HỢP ĐỒNG ===
        // Luôn tạo user mới, cho phép cùng SĐT nhưng tên khác nhau
        $client_id_for_contract = 0;
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
        
        if ($client_id_for_contract <= 0) {
            throw new Exception("ID của học viên không hợp lệ.");
        }

        // === BƯỚC 2: TẠO HỢP ĐỒNG ===
        // Tạo tên gói tự động dựa trên số buổi
        $package_name = "Gói $total_sessions buổi";
        
        $stmt_contract = $conn->prepare("INSERT INTO contracts (client_id, coach_id, start_date, package_name, total_sessions, total_price, discount_percentage, final_price, payment_type, paid_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt_contract->bind_param("iissiiiisi", $client_id_for_contract, $coach_id, $start_date_str, $package_name, $total_sessions, $total_price, $discount_percentage, $final_price, $payment_type, $paid_amount);
        $stmt_contract->execute();
        $new_contract_id = $stmt_contract->insert_id;
        $stmt_contract->close();
        
        // === BƯỚC 2.5: TẠO CÁC ĐỢT TRẢ GÓP (NẾU CÓ) ===
        if ($payment_type === 'installment' && !empty($installment_percentages)) {
            $stmt_installment = $conn->prepare("INSERT INTO payment_installments (contract_id, installment_number, percentage, amount, due_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            
            for ($i = 0; $i < count($installment_percentages); $i++) {
                $installment_number = $i + 1;
                $percentage = floatval($installment_percentages[$i]);
                $amount = intval($installment_amounts[$i]);
                $due_date = !empty($installment_dates[$i]) ? $installment_dates[$i] : null;
                
                if ($percentage > 0 && $amount > 0) {
                    $stmt_installment->bind_param("iidis", $new_contract_id, $installment_number, $percentage, $amount, $due_date);
                    $stmt_installment->execute();
                }
            }
            $stmt_installment->close();
        }

        // === BƯỚC 3: TẠO LỊCH TẬP HÀNG LOẠT (HỖ TRỢ NHIỀU NHÓM) ===
        $schedule_group_starts = isset($_POST['schedule_group_start']) ? $_POST['schedule_group_start'] : [];
        $schedule_group_ends = isset($_POST['schedule_group_end']) ? $_POST['schedule_group_end'] : [];
        
        $sessions_created = 0;
        $insert_session_stmt = $conn->prepare("INSERT INTO training_sessions (contract_id, session_datetime, status) VALUES (?, ?, 'scheduled')");
        
        // Xử lý từng nhóm lịch
        for ($group_idx = 0; $group_idx < count($schedule_group_starts); $group_idx++) {
            $group_start = $schedule_group_starts[$group_idx];
            $group_end = !empty($schedule_group_ends[$group_idx]) ? $schedule_group_ends[$group_idx] : null;
            
            // Lấy days và times cho nhóm này
            $group_days_name = "schedule_days_" . $group_idx;
            $group_times_name = "schedule_times_" . $group_idx;
            
            if (!isset($_POST[$group_days_name]) || !isset($_POST[$group_times_name])) {
                continue; // Bỏ qua nhóm không có dữ liệu
            }
            
            $group_days = $_POST[$group_days_name];
            $group_times = $_POST[$group_times_name];
            
            // Tạo schedule map cho nhóm này
            $schedule_map = [];
            for ($i = 0; $i < count($group_days); $i++) {
                if (!empty($group_days[$i]) && !empty($group_times[$i])) {
                    $schedule_map[$group_days[$i]][] = $group_times[$i];
                }
            }
            
            if (empty($schedule_map) || empty($group_start)) {
                continue; // Bỏ qua nhóm không hợp lệ
            }
            
            // Tạo lịch cho nhóm này
            $current_date = new DateTime($group_start, new DateTimeZone('Asia/Ho_Chi_Minh'));
            $current_date->setTime(0, 0, 0);
            
            $end_date = null;
            if ($group_end) {
                $end_date = new DateTime($group_end, new DateTimeZone('Asia/Ho_Chi_Minh'));
                $end_date->setTime(23, 59, 59);
            }
            
            // Tạo lịch trong khoảng thời gian của nhóm
            for ($day_offset = 0; $day_offset < 365 && $sessions_created < $total_sessions; $day_offset++) {
                $check_date = clone $current_date;
                $check_date->modify("+$day_offset days");
                
                // Kiểm tra xem đã vượt quá ngày kết thúc của nhóm chưa
                if ($end_date && $check_date > $end_date) {
                    break; // Chuyển sang nhóm tiếp theo
                }
                
                $day_of_week_num = $check_date->format('N');
                if (isset($schedule_map[$day_of_week_num])) {
                    foreach ($schedule_map[$day_of_week_num] as $time) {
                        if ($sessions_created < $total_sessions) {
                            $session_datetime_str = $check_date->format('Y-m-d') . ' ' . $time;
                            $insert_session_stmt->bind_param("is", $new_contract_id, $session_datetime_str);
                            $insert_session_stmt->execute();
                            $sessions_created++;
                        } else {
                            break 3; // Thoát khỏi tất cả vòng lặp
                        }
                    }
                }
            }
            
            // Nếu đã đủ số buổi thì dừng
            if ($sessions_created >= $total_sessions) {
                break;
            }
        }
        
        $insert_session_stmt->close();

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
?>