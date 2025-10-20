<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$coach_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

echo "<h2>Debug Sessions Data</h2>";
echo "<p>Coach ID: $coach_id</p>";
echo "<p>Current Date: $current_date</p>";

// Test 1: Kiểm tra tất cả buổi tập của coach
echo "<h3>1. Tất cả buổi tập của coach:</h3>";
$sql_all = "
    SELECT 
        ts.id,
        ts.session_datetime,
        ts.status,
        client.full_name AS client_name,
        c.package_name
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ?
    ORDER BY ts.session_datetime DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql_all);
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Thời gian</th><th>Học viên</th><th>Gói</th><th>Trạng thái</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['session_datetime'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có buổi tập nào</p>";
}
$stmt->close();

// Test 2: Kiểm tra buổi tập hôm nay
echo "<h3>2. Buổi tập hôm nay ($current_date):</h3>";
$sql_today = "
    SELECT 
        ts.id,
        ts.session_datetime,
        ts.status,
        client.full_name AS client_name,
        c.package_name
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ? 
    AND DATE(ts.session_datetime) = ?
    ORDER BY ts.session_datetime ASC
";

$stmt = $conn->prepare($sql_today);
$stmt->bind_param("is", $coach_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Thời gian</th><th>Học viên</th><th>Gói</th><th>Trạng thái</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['session_datetime'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có buổi tập nào hôm nay</p>";
}
$stmt->close();

// Test 3: Kiểm tra hợp đồng của coach
echo "<h3>3. Hợp đồng của coach:</h3>";
$sql_contracts = "
    SELECT 
        c.id,
        c.package_name,
        c.total_sessions,
        client.full_name AS client_name,
        c.status
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ?
    ORDER BY c.start_date DESC
";

$stmt = $conn->prepare($sql_contracts);
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Học viên</th><th>Gói</th><th>Tổng buổi</th><th>Trạng thái</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "<td>" . $row['total_sessions'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có hợp đồng nào</p>";
}
$stmt->close();

echo "<br><a href='view_sessions.php'>Back to Sessions</a>";
$conn->close();
?>
