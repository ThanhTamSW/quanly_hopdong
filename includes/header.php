<?php
// Bắt đầu session ở đầu file header để tất cả các trang đều có
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy tên file hiện tại để xác định trang nào đang active
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Mảng định nghĩa các mục trên navbar
$nav_items = [
    'index.php' => 'Quản lý Hợp đồng',
    'coach_schedule.php' => 'Lịch dạy',
    'add_contract.php' => 'Thêm Hợp đồng'
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
    body { padding-top: 56px; /* Thêm padding ở body để nội dung không bị navbar che mất */ }
    .done { background-color: #d1e7dd !important; }
    .warning { background-color: #fff3cd !important; }
    .table th { white-space: nowrap; }
    .nav-tabs .nav-link.active {
        font-weight: bold;
        background-color: #e9ecef;
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