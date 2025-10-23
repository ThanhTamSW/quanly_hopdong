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
                status
            ) VALUES (?, ?, ?, 'Imported', ?, ?, ?, ?, 'active')
        ");
        
        $stmt_contract->bind_param("iisiddd",
            $client_id,
            $row['coach_id'],
            $row['start_date'],
            $row['total_sessions'],
            $row['total_price'],
            $row['discount_percentage'],
            $row['final_price']
        );
        
        if ($stmt_contract->execute()) {
            $contract_id = $conn->insert_id;
            $imported++;
            $coaches_assigned[$row['coach_id']] = true;
            
            // Create sessions
            createSessions($conn, $contract_id, $client_id, $row['coach_id'], 
                          $row['start_date'], $row['total_sessions']);
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
?>

