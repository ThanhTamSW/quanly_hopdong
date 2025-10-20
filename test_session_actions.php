<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$coach_id = $_SESSION['user_id'];
$today = date('Y-m-d');

echo "<h2>Test Session Actions</h2>";
echo "<p>Coach ID: $coach_id</p>";
echo "<p>Today: $today</p>";

// Lấy tất cả buổi tập hôm nay
$sql = "
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $coach_id, $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Buổi tập hôm nay:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Thời gian</th><th>Học viên</th><th>Gói</th><th>Trạng thái</th><th>Hành động</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['session_datetime'] . "</td>";
        echo "<td>" . $row['client_name'] . "</td>";
        echo "<td>" . $row['package_name'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>";
        
        if ($row['status'] == 'scheduled') {
            echo "<a href='actions/update_session_status.php?action=complete&session_id=" . $row['id'] . "&contract_id=1' class='btn btn-success btn-sm'>✅ Complete</a> ";
            echo "<a href='actions/update_session_status.php?action=cancel&session_id=" . $row['id'] . "&contract_id=1' class='btn btn-danger btn-sm'>❌ Cancel</a>";
        } else {
            echo "<span style='color: gray;'>" . ucfirst($row['status']) . "</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có buổi tập nào hôm nay</p>";
}

$stmt->close();

echo "<h3>Test Report API:</h3>";
echo "<iframe src='api/coach_report.php?date=$today' width='100%' height='300' style='border: 1px solid #ccc;'></iframe>";

echo "<br><br>";
echo "<a href='view_sessions.php'>Back to Sessions</a> | ";
echo "<a href='test_report_api.php'>Test Report API</a>";

$conn->close();
?>
