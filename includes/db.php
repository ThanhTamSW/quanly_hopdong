<?php
$servername = "localhost";
$username = "root"; // đổi nếu khác
$password = "";     // đổi nếu có mật khẩu
$dbname = "quanly_hopdong";

// Kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
