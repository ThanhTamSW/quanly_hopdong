<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

echo "<h2>Test API Simple</h2>";
echo "<p>Coach ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Coach Name: " . $_SESSION['full_name'] . "</p>";

// Test API call
$api_url = "api/coach_report.php?date=" . date('Y-m-d');
echo "<p>API URL: <a href='$api_url' target='_blank'>$api_url</a></p>";

// Test với cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

echo "<h3>Response Headers:</h3>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h3>Response Body (HTTP $http_code):</h3>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Test JSON parsing
if ($http_code == 200) {
    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<h3>✅ JSON is valid</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<h3>❌ JSON Error: " . json_last_error_msg() . "</h3>";
    }
} else {
    echo "<h3>❌ HTTP Error: $http_code</h3>";
}

echo "<br><a href='view_sessions.php'>Back to Sessions</a>";
?>
