<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    die("Bạn không có quyền truy cập.");
}
include '../includes/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    header("Location: ../import.php");
    exit;
}

if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
    $uploaded_file_path = $_FILES['import_file']['tmp_name'];
    $sheet_name = trim($_POST['sheet_name']);

    if (empty($sheet_name)) set_flash_message('danger', 'Lỗi: Vui lòng nhập tên Sheet.');

    try {
        $spreadsheet = IOFactory::load($uploaded_file_path);
        if (!$spreadsheet->sheetNameExists($sheet_name)) set_flash_message('danger', "Lỗi: Không tìm thấy sheet tên '".htmlspecialchars($sheet_name)."'.");
        
        $worksheet = $spreadsheet->getSheetByName($sheet_name);
        $data_array = $worksheet->toArray();

        if (count($data_array) < 2) set_flash_message('danger', 'Lỗi: Sheet bạn chọn không có dữ liệu.');

        $headers = array_shift($data_array);
        
        $required_columns = ['HỌ VÀ TÊN' => -1, 'SĐT' => -1, 'Tên HLV' => -1, 'Ngày ĐK' => -1, 'Gói SP' => -1, 'Số buổi' => -1, 'Tổng thu' => -1];

        foreach ($required_columns as $key => &$index) {
            $found_index = array_search($key, $headers);
            if ($found_index !== false) {
                $index = $found_index;
            } else {
                set_flash_message('danger', "Import thất bại: Thiếu cột bắt buộc có tiêu đề là '{$key}'.");
            }
        }
        
        $success_count = 0; $errors = []; $line_number = 1;
        $conn->begin_transaction();
        
        $stmt_find_coach = $conn->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'coach'");
        $stmt_find_client = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt_create_client = $conn->prepare("INSERT INTO users (phone_number, password, full_name, role) VALUES (?, ?, ?, 'client')");
        $stmt_create_contract = $conn->prepare("INSERT INTO contracts (client_id, coach_id, start_date, package_name, total_sessions, total_price, discount_percentage, final_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        foreach ($data_array as $data) {
            $line_number++;
            $client_name = trim($data[$required_columns['HỌ VÀ TÊN']]);
            $client_phone = trim($data[$required_columns['SĐT']]);
            $coach_name = trim($data[$required_columns['Tên HLV']]);
            $start_date = trim($data[$required_columns['Ngày ĐK']]);
            $package_name = trim($data[$required_columns['Gói SP']]);
            $total_sessions = intval($data[$required_columns['Số buổi']]);
            $final_price = intval($data[$required_columns['Tổng thu']]);
            $total_price = $final_price; $discount = 0;

            if (empty($client_name) || empty($client_phone) || empty($coach_name)) continue;

            $stmt_find_coach->bind_param("s", $coach_name);
            $stmt_find_coach->execute();
            $coach_result = $stmt_find_coach->get_result();
            if ($coach_result->num_rows == 0) { $errors[] = "Lỗi dòng $line_number: Không tìm thấy Coach '".htmlspecialchars($coach_name)."'."; continue; }
            $coach_id = $coach_result->fetch_assoc()['id'];

            $stmt_find_client->bind_param("s", $client_phone);
            $stmt_find_client->execute();
            $client_result = $stmt_find_client->get_result();
            $client_id = 0;
            if ($client_result->num_rows > 0) {
                $client_id = $client_result->fetch_assoc()['id'];
            } else {
                $hashed_pass = password_hash($client_phone, PASSWORD_DEFAULT);
                $stmt_create_client->bind_param("sss", $client_phone, $hashed_pass, $client_name);
                $stmt_create_client->execute();
                $client_id = $stmt_create_client->insert_id;
            }
            if ($client_id <= 0) { $errors[] = "Lỗi dòng $line_number: Không thể tạo học viên '".htmlspecialchars($client_name)."'."; continue; }
            
            $stmt_create_contract->bind_param("iissiiis", $client_id, $coach_id, $start_date, $package_name, $total_sessions, $total_price, $discount, $final_price);
            $stmt_create_contract->execute();
            if ($stmt_create_contract->affected_rows <= 0) { $errors[] = "Lỗi dòng $line_number: Không thể tạo hợp đồng cho '".htmlspecialchars($client_name)."'."; } 
            else { $success_count++; }
        }
        
        if (empty($errors)) {
            $conn->commit();
            set_flash_message('success', "Import hoàn tất! Đã thêm thành công $success_count hợp đồng.");
        } else {
            $conn->rollback();
            $error_string = "Import thất bại. Vui lòng sửa các lỗi sau:\n" . implode("\n", $errors);
            set_flash_message('danger', $error_string);
        }
    } catch (Exception $e) { set_flash_message('danger', "Import thất bại: " . $e->getMessage()); }
} else { set_flash_message('danger', "Lỗi khi tải file lên."); }
?>