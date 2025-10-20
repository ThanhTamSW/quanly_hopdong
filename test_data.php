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
$next_date = date('Y-m-d', strtotime('+1 day'));

echo "<h2>Test Data for Coach ID: $coach_id</h2>";
echo "<p>Current Date: $current_date</p>";
echo "<p>Next Date: $next_date</p>";

// Kiểm tra dữ liệu hôm nay
$sql_today = "
    SELECT 
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

$stmt_today = $conn->prepare($sql_today);
$stmt_today->bind_param("is", $coach_id, $current_date);
$stmt_today->execute();
$result_today = $stmt_today->get_result();

echo "<h3>Buổi tập hôm nay ($current_date):</h3>";
if ($result_today->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Thời gian</th><th>Học viên</th><th>Gói</th><th>Trạng thái</th></tr>";
    while ($row = $result_today->fetch_assoc()) {
        echo "<tr>";
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

// Kiểm tra dữ liệu ngày mai
$sql_next = "
    SELECT 
        ts.session_datetime,
        client.full_name AS client_name,
        c.package_name
    FROM training_sessions ts
    JOIN contracts c ON ts.contract_id = c.id
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ? 
    AND DATE(ts.session_datetime) = ?
    AND ts.status = 'scheduled'
    ORDER BY ts.session_datetime ASC
";

$stmt_next = $conn->prepare($sql_next);
$stmt_next->bind_param("is", $coach_id, $next_date);
$stmt_next->execute();
$result_next = $stmt_next->get_result();

echo "<h3>Buổi tập ngày mai ($next_date):</h3>";
if ($result_next->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Thời gian</th><th>Học viên</th><th>Gói</th></tr>";
    while ($row = $result_next->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['session_datetime'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có buổi tập nào ngày mai</p>";
}

// Kiểm tra tất cả hợp đồng của coach
$sql_contracts = "
    SELECT 
        c.id,
        c.package_name,
        c.total_sessions,
        client.full_name AS client_name,
        (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') as sessions_completed
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    WHERE c.coach_id = ?
    ORDER BY c.start_date DESC
";

$stmt_contracts = $conn->prepare($sql_contracts);
$stmt_contracts->bind_param("i", $coach_id);
$stmt_contracts->execute();
$result_contracts = $stmt_contracts->get_result();

echo "<h3>Tất cả hợp đồng của coach:</h3>";
if ($result_contracts->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Học viên</th><th>Gói</th><th>Tổng buổi</th><th>Đã hoàn thành</th><th>Còn lại</th></tr>";
    while ($row = $result_contracts->fetch_assoc()) {
        $remaining = $row['total_sessions'] - $row['sessions_completed'];
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "<td>" . $row['total_sessions'] . "</td>";
        echo "<td>" . $row['sessions_completed'] . "</td>";
        echo "<td>" . $remaining . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có hợp đồng nào</p>";
}

$conn->close();
?>
