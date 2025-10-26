<?php
session_start();

// Fake login
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Test data
$testText = "Nguyễn Văn A, 0912345678, bắt đầu 1/11/2025, gói 12 buổi, giá 3 triệu, giảm 10%, HLV Tuấn, tập T2 T4 T6 lúc 7h sáng";

// Call API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/test/actions/parse_contract_text.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $testText]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>HTTP Code: $httpCode</h2>";
echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>JSON Decode:</h3>";
$decoded = json_decode($response, true);
if ($decoded === null) {
    echo "<p style='color:red;'>JSON Parse Error: " . json_last_error_msg() . "</p>";
} else {
    echo "<pre>" . print_r($decoded, true) . "</pre>";
}
?>

