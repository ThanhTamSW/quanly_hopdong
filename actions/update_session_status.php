<?php
session_start();

// --- BỘ GHI NHẬT KÝ ---
$log_file = '../debug_log.txt';
if (file_exists($log_file)) { unlink($log_file); }
function log_message($message, $log_file) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}
// --- KẾT THÚC BỘ GHI NHẬT KÝ ---

log_message("--- BẮT ĐẦU KỊCH BẢN update_session_status.php ---", $log_file);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    log_message("LỖI: Kiểm tra session thất bại. Dừng lại.", $log_file);
    die("Bạn không có quyền truy cập.");
}

include '../includes/db.php';
log_message("Đã nạp file db.php.", $log_file);

define('SESSION_COMM_RATE_PERCENT', 26);

if (isset($_GET['action']) && isset($_GET['session_id']) && isset($_GET['contract_id'])) {
    
    $session_id = intval($_GET['session_id']);
    $contract_id = intval($_GET['contract_id']);
    $action = $_GET['action'];
    
    log_message("Hành động: $action | Session ID: $session_id | Contract ID: $contract_id", $log_file);

    $new_status = '';
    if ($action === 'complete') $new_status = 'completed';
    elseif ($action === 'cancel') $new_status = 'cancelled';

    if (!empty($new_status)) {
        $conn->begin_transaction();
        log_message("Đã bắt đầu transaction.", $log_file);
        try {
            $current_coach_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("UPDATE training_sessions SET status = ?, action_timestamp = NOW(), action_by_coach_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $current_coach_id, $session_id);
            $stmt->execute();
            log_message("UPDATE training_sessions: " . $stmt->affected_rows . " dòng bị ảnh hưởng.", $log_file);
            $stmt->close();

            if ($action === 'complete') {
                log_message("Hành động là 'complete'. Bắt đầu xử lý payroll_log.", $log_file);

                $contract_stmt = $conn->prepare("SELECT coach_id, final_price, total_sessions FROM contracts WHERE id = ?");
                $contract_stmt->bind_param("i", $contract_id);
                $contract_stmt->execute();
                $contract_result = $contract_stmt->get_result();
                
                if ($contract_result && $contract_result->num_rows > 0) {
                    $contract = $contract_result->fetch_assoc();
                    log_message("Đã tìm thấy hợp đồng. final_price = " . $contract['final_price'] . ", total_sessions = " . $contract['total_sessions'], $log_file);

                    $price_per_session = ($contract['total_sessions'] > 0) ? ($contract['final_price'] / $contract['total_sessions']) : 0;
                    $commission_earned = $price_per_session * (SESSION_COMM_RATE_PERCENT / 100);
                    log_message("Tính toán hoa hồng: " . $commission_earned, $log_file);

                    $log_stmt = $conn->prepare("INSERT INTO payroll_log (session_id, contract_id, coach_id, completion_timestamp, commission_earned) VALUES (?, ?, ?, NOW(), ?)");
                    $log_stmt->bind_param("iiid", $session_id, $contract_id, $contract['coach_id'], $commission_earned);
                    $log_stmt->execute();

                    if ($log_stmt->affected_rows > 0) {
                        log_message("THÀNH CÔNG: Đã INSERT vào payroll_log.", $log_file);
                    } else {
                        log_message("LỖI: INSERT vào payroll_log thất bại. Lỗi SQL: " . $log_stmt->error, $log_file);
                    }
                    $log_stmt->close();
                } else {
                    log_message("LỖI: Không tìm thấy hợp đồng với ID $contract_id.", $log_file);
                }
                $contract_stmt->close();
            }
            
            log_message("Chuẩn bị commit transaction.", $log_file);
            $conn->commit();
            log_message("Đã commit transaction thành công.", $log_file);

        } catch (Exception $e) {
            log_message("EXCEPTION: Giao dịch thất bại! Lỗi: " . $e->getMessage(), $log_file);
            $conn->rollback();
        }
    }

    // Chuyển hướng về trang chi tiết hợp đồng sau khi xử lý xong
    header("Location: ../view_sessions.php?contract_id=" . $contract_id);
    exit;
}
?>