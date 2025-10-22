<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kiểm tra nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'includes/db.php';

$error = "";

// Xử lý form đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone_number = trim($_POST['phone_number']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE phone_number = ? AND role = 'coach'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "❌ Sai mật khẩu.";
        }
    } else {
        $error = "❌ Tài khoản Coach với số điện thoại này không tồn tại.";
    }
    $stmt->close();
}

// Bây giờ mới include header (sau khi đã xử lý redirect)
$page_title = 'Đăng nhập';
$requires_login = false;
include 'includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
        <h2 class="text-center mb-4">🔑 Đăng nhập Coach</h2>
        
        <?php if($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="mb-3">
            <input type="text" name="phone_number" class="form-control" placeholder="Số điện thoại" required>
          </div>
          <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
        </form>

        <p class="text-center mt-3">
            Chưa có tài khoản Coach? <a href="register_coach.php">Đăng ký ngay</a>
        </p>
    </div>
</div>

<?php 
include 'includes/footer.php';
$conn->close();
?>