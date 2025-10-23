<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['data']) || !is_array($input['data'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }
    
    $data = $input['data'];
    $auto_create_users = $input['auto_create_users'] ?? true;
    
    $imported = 0;
    $new_users = 0;
    $coaches_assigned = [];
    
    $conn->begin_transaction();
    
    foreach ($data as $row) {
        // Check if client exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt->bind_param("s", $row['client_phone']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $client = $result->fetch_assoc();
            $client_id = $client['id'];
        } else {
            if (!$auto_create_users) {
                continue; // Skip if not auto-creating users
            }
            
            // Create new client
            $password = password_hash($row['client_phone'], PASSWORD_DEFAULT);
            $stmt_create = $conn->prepare("
                INSERT INTO users (phone_number, password, full_name, role)
                VALUES (?, ?, ?, 'client')
            ");
            $stmt_create->bind_param("sss", 
                $row['client_phone'],
                $password,
                $row['client_name']
            );
            $stmt_create->execute();
            $client_id = $conn->insert_id;
            $new_users++;
            $stmt_create->close();
        }
        $stmt->close();
        
        // Insert contract
        $stmt_contract = $conn->prepare("
            INSERT INTO contracts (
                client_id,
                coach_id,
                start_date,
                package_name,
                total_sessions,
                total_price,
                discount_percentage,
                final_price,
                payment_type,
                number_of_installments,
                first_payment,
                status
            ) VALUES (?, ?, ?, 'Imported', ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt_contract->bind_param("iisidddsid",
            $client_id,
            $row['coach_id'],
            $row['start_date'],
            $row['total_sessions'],
            $row['total_price'],
            $row['discount_percentage'],
            $row['final_price'],
            $row['payment_type'],
            $row['number_of_installments'],
            $row['first_payment']
        );
        
        if ($stmt_contract->execute()) {
            $contract_id = $conn->insert_id;
            $imported++;
            $coaches_assigned[$row['coach_id']] = true;
            
            // Create sessions
            createSessions($conn, $contract_id, $client_id, $row['coach_id'], 
                          $row['start_date'], $row['total_sessions']);
            
            // Create installments if payment type is installment
            if ($row['payment_type'] === 'installment' && $row['number_of_installments'] > 1) {
                createInstallments($conn, $contract_id, $row['start_date'], 
                                 $row['final_price'], $row['first_payment'], 
                                 $row['number_of_installments']);
            }
        }
        $stmt_contract->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'new_users' => $new_users,
        'coaches_assigned' => count($coaches_assigned)
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createSessions($conn, $contract_id, $client_id, $coach_id, $start_date, $total_sessions) {
    $stmt = $conn->prepare("
        INSERT INTO sessions (contract_id, client_id, coach_id, scheduled_date, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    $current_date = new DateTime($start_date);
    
    for ($i = 0; $i < $total_sessions; $i++) {
        // Skip Sundays
        while ($current_date->format('N') == 7) {
            $current_date->modify('+1 day');
        }
        
        $date_str = $current_date->format('Y-m-d');
        $stmt->bind_param("iiis", $contract_id, $client_id, $coach_id, $date_str);
        $stmt->execute();
        
        $current_date->modify('+1 day');
    }
    
    $stmt->close();
}

function createInstallments($conn, $contract_id, $start_date, $final_price, $first_payment, $number_of_installments) {
    $stmt = $conn->prepare("
        INSERT INTO installments (contract_id, installment_number, amount, due_date, status, paid_amount, paid_date, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Đợt 1: Tiền đặt cọc/trả trước (ngày ký hợp đồng)
    $installment_num = 1;
    $amount = $first_payment;
    $due_date = $start_date;
    $status = $first_payment > 0 ? 'paid' : 'pending';
    $paid_amount = $first_payment;
    $paid_date = $first_payment > 0 ? $start_date : null;
    $payment_method = null;
    
    $stmt->bind_param("iidssdss", 
        $contract_id, 
        $installment_num, 
        $amount, 
        $due_date, 
        $status,
        $paid_amount,
        $paid_date,
        $payment_method
    );
    $stmt->execute();
    
    // Các đợt còn lại
    $remaining_amount = $final_price - $first_payment;
    $remaining_installments = $number_of_installments - 1;
    
    if ($remaining_installments > 0) {
        $amount_per_installment = round($remaining_amount / $remaining_installments, 2);
        $current_date = new DateTime($start_date);
        
        for ($i = 1; $i <= $remaining_installments; $i++) {
            // Mỗi đợt cách nhau 1 tháng
            $current_date->modify('+1 month');
            
            $installment_num = $i + 1;
            $amount = ($i == $remaining_installments) ? 
                      $remaining_amount - ($amount_per_installment * ($remaining_installments - 1)) : 
                      $amount_per_installment;
            $due_date = $current_date->format('Y-m-d');
            $status = 'pending';
            $paid_amount = 0;
            $paid_date = null;
            $payment_method = null;
            
            $stmt->bind_param("iidssdss", 
                $contract_id, 
                $installment_num, 
                $amount, 
                $due_date, 
                $status,
                $paid_amount,
                $paid_date,
                $payment_method
            );
            $stmt->execute();
        }
    }
    
    $stmt->close();
}
?>

