<?php
require_once 'includes/db.php';

echo "<h2>🔧 Migration: Allow coach_id = NULL</h2>";
echo "<p>Vì đã chuyển sang dùng new_coach_id, coach_id cũ có thể để NULL...</p>";

// Step 1: Modify coach_id to allow NULL
$sql1 = "ALTER TABLE contracts MODIFY COLUMN coach_id INT NULL";

echo "<h3>Bước 1: Cho phép coach_id = NULL</h3>";
if ($conn->query($sql1)) {
    echo "<p style='color: green;'>✅ Đã modify coach_id thành NULL-able</p>";
} else {
    echo "<p style='color: red;'>❌ Lỗi: " . $conn->error . "</p>";
    exit;
}

// Step 2: Verify
$sql2 = "
SELECT 
    COLUMN_NAME,
    IS_NULLABLE,
    COLUMN_TYPE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'quanly_hopdong' 
  AND TABLE_NAME = 'contracts' 
  AND COLUMN_NAME IN ('coach_id', 'new_coach_id')
";

echo "<h3>Bước 2: Xác nhận kết quả</h3>";
$result = $conn->query($sql2);
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Nullable?</th><th>Type</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $nullable_color = ($row['IS_NULLABLE'] == 'YES') ? 'green' : 'red';
        echo "<tr>";
        echo "<td><strong>" . $row['COLUMN_NAME'] . "</strong></td>";
        echo "<td style='color: $nullable_color;'>" . $row['IS_NULLABLE'] . "</td>";
        echo "<td>" . $row['COLUMN_TYPE'] . "</td>";
        echo "<td>" . $row['COLUMN_KEY'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Lỗi: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>✅ Migration hoàn tất!</h3>";
echo "<p><strong>Bây giờ bạn có thể:</strong></p>";
echo "<ul>";
echo "<li>Quay lại <a href='add_contract_text.php'>Thêm hợp đồng với AI</a></li>";
echo "<li>Hoặc <a href='index.php'>Quản lý hợp đồng</a></li>";
echo "</ul>";

$conn->close();
?>

