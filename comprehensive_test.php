<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

include 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra toàn bộ hệ thống</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeeba; }
        h2 { color: #333; }
        h3 { color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h1>🔍 Kiểm tra toàn bộ hệ thống</h1>";

// ===== TEST 1: Kiểm tra kết nối database =====
echo "<div class='test-section'>";
echo "<h2>1️⃣ Kiểm tra kết nối Database</h2>";
if ($conn->connect_error) {
    echo "<div class='error'>❌ Lỗi kết nối: " . $conn->connect_error . "</div>";
} else {
    echo "<div class='success'>✅ Kết nối database thành công</div>";
    echo "<p>Database: " . $conn->get_server_info() . "</p>";
}
echo "</div>";

// ===== TEST 2: Kiểm tra cấu trúc bảng =====
echo "<div class='test-section'>";
echo "<h2>2️⃣ Kiểm tra cấu trúc các bảng</h2>";

$required_tables = ['users', 'contracts', 'training_sessions', 'payroll_log', 'payment_installments'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Bảng '$table' tồn tại</div>";
    } else {
        echo "<div class='error'>❌ Bảng '$table' KHÔNG tồn tại</div>";
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<p><strong>✅ Tất cả bảng cần thiết đều tồn tại</strong></p>";
} else {
    echo "<div class='error'><strong>❌ Thiếu " . count($missing_tables) . " bảng: " . implode(', ', $missing_tables) . "</strong></div>";
}
echo "</div>";

// ===== TEST 3: Kiểm tra cấu trúc bảng contracts =====
echo "<div class='test-section'>";
echo "<h2>3️⃣ Kiểm tra cột trong bảng 'contracts'</h2>";

$required_columns = [
    'id', 'client_id', 'coach_id', 'start_date', 'package_name', 
    'total_sessions', 'total_price', 'discount_percentage', 'final_price',
    'payment_type', 'paid_amount', 'status'
];

$columns_result = $conn->query("SHOW COLUMNS FROM contracts");
$existing_columns = [];
while ($col = $columns_result->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}

echo "<table>";
echo "<tr><th>Cột</th><th>Trạng thái</th></tr>";
foreach ($required_columns as $col) {
    if (in_array($col, $existing_columns)) {
        echo "<tr><td>$col</td><td style='color: green;'>✅ Tồn tại</td></tr>";
    } else {
        echo "<tr><td>$col</td><td style='color: red;'>❌ Thiếu</td></tr>";
    }
}
echo "</table>";
echo "</div>";

// ===== TEST 4: Kiểm tra dữ liệu users =====
echo "<div class='test-section'>";
echo "<h2>4️⃣ Kiểm tra dữ liệu người dùng</h2>";

$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$coach_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='coach'")->fetch_assoc()['count'];
$client_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='client'")->fetch_assoc()['count'];
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];

echo "<table>";
echo "<tr><th>Loại người dùng</th><th>Số lượng</th></tr>";
echo "<tr><td>Tổng số users</td><td>$users_count</td></tr>";
echo "<tr><td>Admin</td><td>$admin_count</td></tr>";
echo "<tr><td>Coach</td><td>$coach_count</td></tr>";
echo "<tr><td>Client</td><td>$client_count</td></tr>";
echo "</table>";

if ($coach_count == 0) {
    echo "<div class='warning'>⚠️ Chưa có coach nào trong hệ thống</div>";
}
if ($admin_count == 0) {
    echo "<div class='warning'>⚠️ Chưa có admin nào trong hệ thống</div>";
}
echo "</div>";

// ===== TEST 5: Kiểm tra dữ liệu contracts =====
echo "<div class='test-section'>";
echo "<h2>5️⃣ Kiểm tra dữ liệu hợp đồng</h2>";

$contracts_count = $conn->query("SELECT COUNT(*) as count FROM contracts")->fetch_assoc()['count'];
$active_contracts = $conn->query("SELECT COUNT(*) as count FROM contracts WHERE status='active'")->fetch_assoc()['count'];
$completed_contracts = $conn->query("SELECT COUNT(*) as count FROM contracts WHERE status='completed'")->fetch_assoc()['count'];

echo "<table>";
echo "<tr><th>Loại hợp đồng</th><th>Số lượng</th></tr>";
echo "<tr><td>Tổng hợp đồng</td><td>$contracts_count</td></tr>";
echo "<tr><td>Đang hoạt động</td><td>$active_contracts</td></tr>";
echo "<tr><td>Đã hoàn thành</td><td>$completed_contracts</td></tr>";
echo "</table>";

// Kiểm tra hợp đồng có package_name NULL
$null_package = $conn->query("SELECT COUNT(*) as count FROM contracts WHERE package_name IS NULL OR package_name = ''")->fetch_assoc()['count'];
if ($null_package > 0) {
    echo "<div class='warning'>⚠️ Có $null_package hợp đồng có package_name NULL hoặc rỗng</div>";
}

// Kiểm tra hợp đồng không có client hoặc coach
$invalid_contracts = $conn->query("
    SELECT c.id, c.client_id, c.coach_id 
    FROM contracts c
    LEFT JOIN users u1 ON c.client_id = u1.id
    LEFT JOIN users u2 ON c.coach_id = u2.id
    WHERE u1.id IS NULL OR u2.id IS NULL
")->num_rows;

if ($invalid_contracts > 0) {
    echo "<div class='error'>❌ Có $invalid_contracts hợp đồng tham chiếu đến client/coach không tồn tại</div>";
}

echo "</div>";

// ===== TEST 6: Kiểm tra training_sessions =====
echo "<div class='test-section'>";
echo "<h2>6️⃣ Kiểm tra buổi tập</h2>";

$sessions_count = $conn->query("SELECT COUNT(*) as count FROM training_sessions")->fetch_assoc()['count'];
$scheduled_sessions = $conn->query("SELECT COUNT(*) as count FROM training_sessions WHERE status='scheduled'")->fetch_assoc()['count'];
$completed_sessions = $conn->query("SELECT COUNT(*) as count FROM training_sessions WHERE status='completed'")->fetch_assoc()['count'];
$cancelled_sessions = $conn->query("SELECT COUNT(*) as count FROM training_sessions WHERE status='cancelled'")->fetch_assoc()['count'];

echo "<table>";
echo "<tr><th>Loại buổi tập</th><th>Số lượng</th></tr>";
echo "<tr><td>Tổng buổi tập</td><td>$sessions_count</td></tr>";
echo "<tr><td>Đã lên lịch</td><td>$scheduled_sessions</td></tr>";
echo "<tr><td>Đã hoàn thành</td><td>$completed_sessions</td></tr>";
echo "<tr><td>Đã hủy</td><td>$cancelled_sessions</td></tr>";
echo "</table>";

// Kiểm tra sessions không có contract
$orphan_sessions = $conn->query("
    SELECT ts.id 
    FROM training_sessions ts
    LEFT JOIN contracts c ON ts.contract_id = c.id
    WHERE c.id IS NULL
")->num_rows;

if ($orphan_sessions > 0) {
    echo "<div class='error'>❌ Có $orphan_sessions buổi tập không có hợp đồng tương ứng</div>";
}

echo "</div>";

// ===== TEST 7: Kiểm tra payment_installments =====
echo "<div class='test-section'>";
echo "<h2>7️⃣ Kiểm tra các đợt trả góp</h2>";

$installments_count = $conn->query("SELECT COUNT(*) as count FROM payment_installments")->fetch_assoc()['count'];
$pending_installments = $conn->query("SELECT COUNT(*) as count FROM payment_installments WHERE status='pending'")->fetch_assoc()['count'];
$paid_installments = $conn->query("SELECT COUNT(*) as count FROM payment_installments WHERE status='paid'")->fetch_assoc()['count'];
$overdue_installments = $conn->query("SELECT COUNT(*) as count FROM payment_installments WHERE status='overdue'")->fetch_assoc()['count'];

echo "<table>";
echo "<tr><th>Trạng thái</th><th>Số lượng</th></tr>";
echo "<tr><td>Tổng đợt trả góp</td><td>$installments_count</td></tr>";
echo "<tr><td>Chờ thanh toán</td><td>$pending_installments</td></tr>";
echo "<tr><td>Đã thanh toán</td><td>$paid_installments</td></tr>";
echo "<tr><td>Quá hạn</td><td>$overdue_installments</td></tr>";
echo "</table>";

echo "</div>";

// ===== TEST 8: Kiểm tra các file quan trọng =====
echo "<div class='test-section'>";
echo "<h2>8️⃣ Kiểm tra các file quan trọng</h2>";

$important_files = [
    'index.php' => 'Trang chủ',
    'login.php' => 'Đăng nhập',
    'add_contract.php' => 'Thêm hợp đồng',
    'view_sessions.php' => 'Xem buổi tập',
    'coach_schedule.php' => 'Lịch coach',
    'actions/save_contract.php' => 'Lưu hợp đồng',
    'actions/update_session_status.php' => 'Cập nhật trạng thái buổi tập',
    'actions/delete_contract.php' => 'Xóa hợp đồng',
    'actions/export_salary.php' => 'Xuất lương',
    'includes/db.php' => 'Kết nối DB',
    'includes/header.php' => 'Header',
    'includes/footer.php' => 'Footer'
];

echo "<table>";
echo "<tr><th>File</th><th>Mô tả</th><th>Trạng thái</th></tr>";
foreach ($important_files as $file => $desc) {
    $exists = file_exists($file);
    $status = $exists ? "<span style='color: green;'>✅ Tồn tại</span>" : "<span style='color: red;'>❌ Thiếu</span>";
    echo "<tr><td>$file</td><td>$desc</td><td>$status</td></tr>";
}
echo "</table>";
echo "</div>";

// ===== TEST 9: Kiểm tra quyền ghi file =====
echo "<div class='test-section'>";
echo "<h2>9️⃣ Kiểm tra quyền ghi file</h2>";

$writable_dirs = ['actions', 'includes'];
echo "<table>";
echo "<tr><th>Thư mục</th><th>Có thể ghi</th></tr>";
foreach ($writable_dirs as $dir) {
    $writable = is_writable($dir);
    $status = $writable ? "<span style='color: green;'>✅ Có thể ghi</span>" : "<span style='color: red;'>❌ Không thể ghi</span>";
    echo "<tr><td>$dir</td><td>$status</td></tr>";
}
echo "</table>";
echo "</div>";

// ===== TEST 10: Kiểm tra session =====
echo "<div class='test-section'>";
echo "<h2>🔟 Kiểm tra Session</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='success'>✅ Session đang hoạt động</div>";
    echo "<p>Session ID: " . session_id() . "</p>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>User ID trong session: " . $_SESSION['user_id'] . "</p>";
        echo "<p>Role trong session: " . ($_SESSION['role'] ?? 'Không xác định') . "</p>";
    }
} else {
    echo "<div class='error'>❌ Session không hoạt động</div>";
}
echo "</div>";

// ===== TEST 11: Kiểm tra tính toàn vẹn dữ liệu =====
echo "<div class='test-section'>";
echo "<h2>1️⃣1️⃣ Kiểm tra tính toàn vẹn dữ liệu</h2>";

// Kiểm tra contracts có số buổi tập khớp với training_sessions
$mismatched = $conn->query("
    SELECT c.id, c.total_sessions, COUNT(ts.id) as actual_sessions
    FROM contracts c
    LEFT JOIN training_sessions ts ON c.id = ts.contract_id
    GROUP BY c.id
    HAVING c.total_sessions != COUNT(ts.id)
")->num_rows;

if ($mismatched > 0) {
    echo "<div class='warning'>⚠️ Có $mismatched hợp đồng có số buổi tập không khớp với số buổi đã tạo</div>";
} else {
    echo "<div class='success'>✅ Tất cả hợp đồng có số buổi tập khớp</div>";
}

// Kiểm tra users có số điện thoại trùng lặp
$duplicate_phones = $conn->query("
    SELECT phone_number, COUNT(*) as count
    FROM users
    GROUP BY phone_number
    HAVING COUNT(*) > 1
")->num_rows;

if ($duplicate_phones > 0) {
    echo "<div class='warning'>⚠️ Có $duplicate_phones số điện thoại bị trùng lặp</div>";
    
    // Hiển thị chi tiết
    $dups = $conn->query("
        SELECT phone_number, COUNT(*) as count, GROUP_CONCAT(full_name SEPARATOR ', ') as names
        FROM users
        GROUP BY phone_number
        HAVING COUNT(*) > 1
    ");
    
    echo "<table>";
    echo "<tr><th>Số điện thoại</th><th>Số lần xuất hiện</th><th>Tên</th></tr>";
    while ($dup = $dups->fetch_assoc()) {
        echo "<tr><td>{$dup['phone_number']}</td><td>{$dup['count']}</td><td>{$dup['names']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='success'>✅ Không có số điện thoại trùng lặp</div>";
}

echo "</div>";

// ===== SUMMARY =====
echo "<div class='test-section' style='background-color: #e7f3ff;'>";
echo "<h2>📊 Tổng kết</h2>";
echo "<p><strong>Hệ thống đã được kiểm tra đầy đủ!</strong></p>";
echo "<p>Thời gian kiểm tra: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='index.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Về trang chủ</a></p>";
echo "</div>";

$conn->close();

echo "</body></html>";
?>

