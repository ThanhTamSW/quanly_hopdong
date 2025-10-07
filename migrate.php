<?php
// Bật hiển thị lỗi để theo dõi quá trình
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'includes/db.php';

echo "<h1>Bắt đầu quá trình di chuyển dữ liệu...</h1>";
echo "<p>Vui lòng không đóng trình duyệt cho đến khi quá trình hoàn tất.</p><hr>";

// Bắt đầu transaction để đảm bảo an toàn
$conn->begin_transaction();
try {
    // Lấy tất cả dữ liệu từ bảng hopdong cũ
    $old_contracts_result = $conn->query("SELECT * FROM hopdong");
    if ($old_contracts_result->num_rows === 0) {
        die("Bảng 'hopdong' cũ không có dữ liệu để di chuyển. Dừng lại.");
    }
    $old_contracts = $old_contracts_result->fetch_all(MYSQLI_ASSOC);

    echo "Tìm thấy " . count($old_contracts) . " hợp đồng cũ để xử lý.<br><br>";

    foreach ($old_contracts as $old_contract) {
        echo "<strong>Đang xử lý hợp đồng cũ ID: {$old_contract['id']} (Học viên: {$old_contract['ho_ten']})</strong><br>";

        // --- 1. Xử lý Coach ---
        $coach_name = $old_contract['ten_hlv'];
        $stmt_find_coach = $conn->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'coach'");
        $stmt_find_coach->bind_param("s", $coach_name);
        $stmt_find_coach->execute();
        $coach_result = $stmt_find_coach->get_result();
        $coach_id = 0;

        if ($coach_result->num_rows > 0) {
            $coach_id = $coach_result->fetch_assoc()['id'];
            echo " - Tìm thấy Coach '{$coach_name}' với ID: $coach_id<br>";
        } else {
            // Nếu không tìm thấy, tạo Coach mới với thông tin giả định
            $placeholder_phone = 'C' . time() . rand(100, 999); // Tạo SĐT giả không trùng
            $placeholder_pass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt_create_coach = $conn->prepare("INSERT INTO users (phone_number, password, full_name, role) VALUES (?, ?, ?, 'coach')");
            $stmt_create_coach->bind_param("sss", $placeholder_phone, $placeholder_pass, $coach_name);
            $stmt_create_coach->execute();
            $coach_id = $stmt_create_coach->insert_id;
            echo " - Không tìm thấy Coach '{$coach_name}'. Đã tạo mới với ID: $coach_id<br>";
        }

        // --- 2. Xử lý Học viên (Client) ---
        $client_name = $old_contract['ho_ten'];
        $stmt_find_client = $conn->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'client'");
        $stmt_find_client->bind_param("s", $client_name);
        $stmt_find_client->execute();
        $client_result = $stmt_find_client->get_result();
        $client_id = 0;

        if ($client_result->num_rows > 0) {
            $client_id = $client_result->fetch_assoc()['id'];
            echo " - Tìm thấy Học viên '{$client_name}' với ID: $client_id<br>";
        } else {
            // Nếu không tìm thấy, tạo Học viên mới
            $placeholder_phone = 'HV' . time() . rand(100, 999);
            $placeholder_pass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt_create_client = $conn->prepare("INSERT INTO users (phone_number, password, full_name, role) VALUES (?, ?, ?, 'client')");
            $stmt_create_client->bind_param("sss", $placeholder_phone, $placeholder_pass, $client_name);
            $stmt_create_client->execute();
            $client_id = $stmt_create_client->insert_id;
            echo " - Không tìm thấy Học viên '{$client_name}'. Đã tạo mới với ID: $client_id<br>";
        }

        // --- 3. Tạo Hợp đồng mới ---
        $start_date = $old_contract['ngay_dk'];
        $package_name = $old_contract['goi_sp'];
        $total_sessions = $old_contract['so_buoi'];

        $stmt_create_contract = $conn->prepare("INSERT INTO contracts (client_id, coach_id, start_date, package_name, total_sessions) VALUES (?, ?, ?, ?, ?)");
        $stmt_create_contract->bind_param("iissi", $client_id, $coach_id, $start_date, $package_name, $total_sessions);
        $stmt_create_contract->execute();
        $new_contract_id = $stmt_create_contract->insert_id;
        echo " - Đã tạo hợp đồng mới với ID: $new_contract_id<br>";

        // --- 4. Tạo các buổi tập đã hoàn thành (nếu có) ---
        $sessions_completed_count = $old_contract['so_buoi_da_tap'];
        if ($sessions_completed_count > 0) {
            $stmt_create_session = $conn->prepare("INSERT INTO training_sessions (contract_id, session_datetime, status, action_timestamp, action_by_coach_id) VALUES (?, ?, 'completed', ?, ?)");
            $start_date_obj = new DateTime($start_date);

            for ($i = 0; $i < $sessions_completed_count; $i++) {
                // Tạo ngày tháng giả định cho các buổi tập đã qua
                $session_date = clone $start_date_obj;
                $session_date->modify("+$i days");
                $session_date_str = $session_date->format('Y-m-d H:i:s');
                
                $stmt_create_session->bind_param("issi", $new_contract_id, $session_date_str, $session_date_str, $coach_id);
                $stmt_create_session->execute();
            }
            echo " - Đã tạo $sessions_completed_count buổi tập đã hoàn thành.<br>";
        }
        echo "<hr>";
    }

    // Nếu mọi thứ thành công, lưu lại
    $conn->commit();
    echo "<h2 style='color:green;'>✅ DI CHUYỂN DỮ LIỆU THÀNH CÔNG!</h2>";
    echo "<p>Bây giờ bạn có thể quay lại <a href='index.php'>trang chủ</a> để xem kết quả.</p>";
    echo "<p>Sau khi đã xác nhận mọi thứ đều đúng, bạn nên xóa file <strong>migrate.php</strong> này và xóa bảng <strong>hopdong</strong> cũ khỏi database.</p>";

} catch (Exception $e) {
    // Nếu có lỗi, hủy bỏ mọi thay đổi
    $conn->rollback();
    echo "<h2 style='color:red;'>❌ ĐÃ CÓ LỖI XẢY RA!</h2>";
    echo "<p>Quá trình đã được hủy bỏ để bảo toàn dữ liệu.</p>";
    echo "<p>Lỗi chi tiết: " . $e->getMessage() . "</p>";
}

$conn->close();
?>