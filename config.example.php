<?php
// =============================================
// CẤU HÌNH MÔI TRƯỜNG - HỆ THỐNG QUẢN LÝ GYM
// =============================================

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'quanly_hopdong');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Hệ thống Quản lý Gym');
define('APP_URL', 'http://localhost');
define('APP_ENV', 'production');

// Security
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 6);

// Business Configuration
define('DEFAULT_COMMISSION_RATE', 26); // 26% hoa hồng buổi tập
define('SALES_COMMISSION_RATE', 4);    // 4% hoa hồng bán hàng
define('SALES_TARGET_THRESHOLD', 80);  // 80% target để được hoa hồng

// File Upload
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['xlsx', 'xls']);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error Reporting (chỉ bật trong development)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
