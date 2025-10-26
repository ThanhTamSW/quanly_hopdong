<?php
require_once 'includes/db.php';

echo "<h2>🔍 Kiểm tra cấu trúc bảng training_sessions</h2>";

$sql = "SHOW COLUMNS FROM training_sessions";
$result = $conn->query($sql);

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Lỗi: " . $conn->error . "</p>";
}

$conn->close();
?>

