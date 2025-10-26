<?php
include 'includes/db.php';

echo "=== MIGRATION: TÁCH COACH RA KHỎI USERS ===\n\n";
echo "⚠️ CẢNH BÁO: Đây là thay đổi cấu trúc database lớn!\n";
echo "   Hãy backup database trước khi tiếp tục.\n\n";

$steps = [
    1 => "Cập nhật cấu trúc bảng coaches",
    2 => "Copy data từ users sang coaches",
    3 => "Tạo bảng mapping",
    4 => "Thêm cột new_coach_id vào contracts",
    5 => "Cập nhật new_coach_id"
];

foreach ($steps as $step => $description) {
    echo "📝 BƯỚC $step: $description\n";
}

echo "\n❓ Bạn có muốn tiếp tục? (y/n): ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'y'){
    echo "❌ Đã hủy migration.\n";
    exit;
}
fclose($handle);

echo "\n🚀 BẮT ĐẦU MIGRATION...\n\n";

try {
    $conn->begin_transaction();
    
    // BƯỚC 1: Cập nhật cấu trúc bảng coaches
    echo "📝 Bước 1: Cập nhật cấu trúc bảng coaches...\n";
    
    $columns_to_add = [
        "phone_number VARCHAR(20) UNIQUE AFTER phone",
        "password VARCHAR(255) AFTER phone_number",
        "start_work_date DATE AFTER password",
        "coach_type ENUM('official', 'freelance') DEFAULT 'official' AFTER start_work_date",
        "base_salary DECIMAL(15,2) DEFAULT 0 AFTER coach_type",
        "sales_target DECIMAL(15,2) DEFAULT 0 AFTER base_salary",
        "lunch_allowance DECIMAL(15,2) DEFAULT 0 AFTER sales_target",
        "monthly_bonus DECIMAL(15,2) DEFAULT 0 AFTER lunch_allowance",
        "monthly_penalty DECIMAL(15,2) DEFAULT 0 AFTER monthly_bonus",
        "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER monthly_penalty"
    ];
    
    foreach ($columns_to_add as $column_def) {
        $column_name = explode(' ', $column_def)[0];
        
        // Check if column exists
        $check = $conn->query("SHOW COLUMNS FROM coaches LIKE '$column_name'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE coaches ADD COLUMN $column_def";
            $conn->query($sql);
            echo "   ✅ Đã thêm cột: $column_name\n";
        } else {
            echo "   ⏭️ Cột đã tồn tại: $column_name\n";
        }
    }
    
    // BƯỚC 2: Copy data
    echo "\n📝 Bước 2: Copy data từ users sang coaches...\n";
    
    $sql = "
        INSERT INTO coaches (
            name, email, phone, phone_number, password, start_work_date, 
            coach_type, base_salary, sales_target, lunch_allowance, 
            monthly_bonus, monthly_penalty, created_at
        )
        SELECT 
            full_name,
            CONCAT(REPLACE(phone_number, ' ', ''), '@coach.local'),
            phone_number,
            phone_number,
            password,
            start_work_date,
            coach_type,
            base_salary,
            sales_target,
            lunch_allowance,
            monthly_bonus,
            monthly_penalty,
            created_at
        FROM users 
        WHERE role = 'coach'
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password = VALUES(password),
            start_work_date = VALUES(start_work_date),
            coach_type = VALUES(coach_type),
            base_salary = VALUES(base_salary),
            sales_target = VALUES(sales_target),
            lunch_allowance = VALUES(lunch_allowance),
            monthly_bonus = VALUES(monthly_bonus),
            monthly_penalty = VALUES(monthly_penalty)
    ";
    
    $conn->query($sql);
    echo "   ✅ Đã copy " . $conn->affected_rows . " coach(s)\n";
    
    // BƯỚC 3: Tạo bảng mapping
    echo "\n📝 Bước 3: Tạo bảng mapping...\n";
    
    $sql = "
        CREATE TABLE IF NOT EXISTS user_coach_mapping (
            user_id INT NOT NULL,
            coach_id INT NOT NULL,
            PRIMARY KEY (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql);
    echo "   ✅ Đã tạo bảng mapping\n";
    
    $sql = "
        INSERT INTO user_coach_mapping (user_id, coach_id)
        SELECT u.id, c.id
        FROM users u
        JOIN coaches c ON u.phone_number COLLATE utf8mb4_unicode_ci = c.phone_number COLLATE utf8mb4_unicode_ci
        WHERE u.role = 'coach'
        ON DUPLICATE KEY UPDATE coach_id = VALUES(coach_id)
    ";
    $conn->query($sql);
    echo "   ✅ Đã tạo " . $conn->affected_rows . " mapping(s)\n";
    
    // BƯỚC 4: Thêm cột new_coach_id
    echo "\n📝 Bước 4: Thêm cột new_coach_id vào contracts...\n";
    
    $check = $conn->query("SHOW COLUMNS FROM contracts LIKE 'new_coach_id'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE contracts ADD COLUMN new_coach_id INT AFTER coach_id");
        $conn->query("ALTER TABLE contracts ADD INDEX idx_new_coach_id (new_coach_id)");
        echo "   ✅ Đã thêm cột new_coach_id\n";
    } else {
        echo "   ⏭️ Cột new_coach_id đã tồn tại\n";
    }
    
    // BƯỚC 5: Cập nhật new_coach_id
    echo "\n📝 Bước 5: Cập nhật new_coach_id...\n";
    
    $sql = "
        UPDATE contracts c
        JOIN user_coach_mapping m ON c.coach_id = m.user_id
        SET c.new_coach_id = m.coach_id
    ";
    $conn->query($sql);
    echo "   ✅ Đã cập nhật " . $conn->affected_rows . " contract(s)\n";
    
    $conn->commit();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ MIGRATION THÀNH CÔNG!\n\n";
    
    // Verify
    echo "📊 VERIFY DATA:\n\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM coaches");
    $count = $result->fetch_assoc()['count'];
    echo "   Coaches: $count\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM user_coach_mapping");
    $count = $result->fetch_assoc()['count'];
    echo "   Mappings: $count\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM contracts WHERE new_coach_id IS NOT NULL");
    $count = $result->fetch_assoc()['count'];
    echo "   Contracts updated: $count\n";
    
    echo "\n💡 BƯỚC TIẾP THEO:\n";
    echo "   1. Verify data trong database\n";
    echo "   2. Cập nhật code để query từ bảng coaches\n";
    echo "   3. Test toàn bộ chức năng\n";
    echo "   4. Sau khi OK, chạy script finalize để hoàn tất\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n❌ LỖI: " . $e->getMessage() . "\n";
}

$conn->close();
?>

