<?php
// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy tên file hiện tại để xác định trang nào đang active
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Mảng định nghĩa các mục trên navbar
$nav_items = [
    'index.php' => 'Quản lý Hợp đồng',
    'coach_schedule.php' => 'Lịch dạy',
    'add_contract.php' => 'Thêm Hợp đồng',
    'payment_installments.php' => '💰 Quản lý Trả góp',
    'manage_targets.php' => '🎯 Quản lý Target'
];

// Nếu file này được gọi từ một trang yêu cầu đăng nhập, hãy kiểm tra session
if (isset($requires_login) && $requires_login === true) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Quản lý Hợp đồng' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body { 
        padding-top: 70px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f8f9fa !important;
        min-height: 100vh;
    }
    
    /* Navbar Animation */
    .navbar {
        backdrop-filter: blur(10px);
        background: rgba(33, 37, 41, 0.95) !important;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        background: linear-gradient(45deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        transition: transform 0.3s ease;
    }
    
    .navbar-brand:hover {
        transform: scale(1.05);
    }
    
    .nav-link {
        position: relative;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(45deg, #667eea, #764ba2);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    
    .nav-link:hover::after,
    .nav-link.active::after {
        width: 80%;
    }
    
    .nav-link.active {
        color: #667eea !important;
    }
    
    /* Card Animations */
    .card {
        transition: all 0.3s ease;
        border: none;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }
    
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }
    
    /* Button Animations */
    .btn {
        transition: all 0.3s ease;
        border-radius: 8px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .btn:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .btn:active {
        transform: translateY(0);
    }
    
    /* Table Styles */
    .table {
        animation: fadeIn 0.8s ease;
    }
    
    .table th { 
        white-space: nowrap;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
        font-weight: 600;
        padding: 12px;
    }
    
    .table {
        background: white;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
        background: white;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa !important;
        transform: translateX(2px);
    }
    
    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .done { 
        background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%) !important;
        animation: pulse 2s infinite;
    }
    
    .warning { 
        background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%) !important;
    }
    
    /* Tab Styles */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
    }
    
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-radius: 8px 8px 0 0;
        border: none !important;
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    
    /* Progress Bar */
    .progress {
        border-radius: 10px;
        overflow: hidden;
        background: #e9ecef;
    }
    
    .progress-bar {
        transition: width 1s ease;
        animation: slideInRight 1s ease;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(-100%);
        }
        to {
            transform: translateX(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.95;
        }
    }
    
    /* Loading Animation */
    .spinner-border {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Form Controls */
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }
    
    /* Alert */
    .alert {
        border-radius: 12px;
        border: none;
        animation: slideInDown 0.5s ease;
    }
    
    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    /* Badge */
    .badge {
        padding: 0.5em 0.8em;
        border-radius: 8px;
        font-weight: 500;
    }
    
    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">QUẢN LÝ PT</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php foreach ($nav_items as $file => $title): ?>
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == $file) ? 'active' : '' ?>" href="<?= $file ?>"><?= $title ?></a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (isset($_SESSION['user_id'])): ?>
      <span class="navbar-text text-white me-3">
        Xin chào, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>!
      </span>
      <a href="logout.php" class="btn btn-outline-light">Đăng xuất</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container-fluid mt-4">