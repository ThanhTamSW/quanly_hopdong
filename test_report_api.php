<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

echo "<h2>Test Report API</h2>";
echo "<p>Coach ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Coach Name: " . $_SESSION['full_name'] . "</p>";

// Test API call
$api_url = "api/coach_report.php?date=" . date('Y-m-d');
echo "<p>API URL: " . $api_url . "</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>API Response (HTTP $http_code):</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Parsed Data:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        echo "<h3>Report Text:</h3>";
        echo "<pre>" . htmlspecialchars($data['reportText']) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>API call failed with HTTP $http_code</p>";
}

echo "<br><a href='view_sessions.php'>Back to Sessions</a>";
?>
