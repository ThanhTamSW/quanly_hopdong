<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}

include '../includes/db.php';

define('SESSION_COMM_RATE_PERCENT', 26);

if (isset($_GET['action']) && isset($_GET['session_id']) && isset($_GET['contract_id'])) {
    
    $session_id = intval($_GET['session_id']);
    $contract_id = intval($_GET['contract_id']);
    $action = $_GET['action'];

    $new_status = '';
    if ($action === 'complete') $new_status = 'completed';
    elseif ($action === 'cancel') $new_status = 'cancelled';

    if (!empty($new_status)) {
        $conn->begin_transaction();
        try {
            $current_coach_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("UPDATE training_sessions SET status = ?, action_timestamp = NOW(), action_by_coach_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $current_coach_id, $session_id);
            $stmt->execute();
            $stmt->close();

            if ($action === 'complete') {
                $contract_stmt = $conn->prepare("SELECT coach_id, final_price, total_sessions FROM contracts WHERE id = ?");
                $contract_stmt->bind_param("i", $contract_id);
                $contract_stmt->execute();
                $contract_result = $contract_stmt->get_result();
                
                if ($contract_result && $contract_result->num_rows > 0) {
                    $contract = $contract_result->fetch_assoc();
                    $price_per_session = ($contract['total_sessions'] > 0) ? ($contract['final_price'] / $contract['total_sessions']) : 0;
                    $commission_earned = $price_per_session * (SESSION_COMM_RATE_PERCENT / 100);

                    $log_stmt = $conn->prepare("INSERT INTO payroll_log (session_id, contract_id, coach_id, completion_timestamp, commission_earned) VALUES (?, ?, ?, NOW(), ?)");
                    $log_stmt->bind_param("iiid", $session_id, $contract_id, $contract['coach_id'], $commission_earned);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
                $contract_stmt->close();
            }
            
            $conn->commit();
            
            // Thêm thông báo thành công
            $message = ($action === 'complete') ? 'Đã xác nhận hoàn thành buổi tập!' : 'Đã hủy buổi tập!';
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => $message
            ];

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    // Chuyển hướng về trang chi tiết hợp đồng sau khi xử lý xong
    header("Location: ../view_sessions.php?contract_id=" . $contract_id);
    exit;
}
?>