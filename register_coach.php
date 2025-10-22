<?php
// Sử dụng cấu trúc header/footer để đồng bộ giao diện
$page_title = 'Đăng ký Coach';
$requires_login = false; // Trang này không cần đăng nhập
include 'includes/header.php';
include 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone_number = trim($_POST['phone_number']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $start_work_date = $_POST['start_work_date'];

    if (empty($phone_number) || empty($password) || empty($full_name) || empty($start_work_date)) {
        $error = "Vui lòng điền đầy đủ tất cả thông tin.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // Kiểm tra xem SĐT đã được dùng cho Coach chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ? AND role = 'coach'");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Số điện thoại này đã được đăng ký làm Coach.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'coach';

            $insert_stmt = $conn->prepare("INSERT INTO users (phone_number, password, full_name, role, start_work_date) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $phone_number, $hashed_password, $full_name, $role, $start_work_date);

            if ($insert_stmt->execute()) {
                $success = "Tạo tài khoản Coach thành công! Bạn có thể đăng nhập.";
            } else {
                $error = "Đã xảy ra lỗi. Vui lòng thử lại.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
        <h2 class="text-center mb-4">💪 Đăng ký Coach</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="register_coach.php">
            <div class="mb-3">
                <label for="full_name" class="form-label">Họ và Tên</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">Số điện thoại</label>
                <input type="tel" name="phone_number" id="phone_number" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label for="start_work_date" class="form-label">Ngày bắt đầu làm việc</label>
                <input type="date" name="start_work_date" id="start_work_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
            <p class="text-center mt-3"><a href="login.php">Đã có tài khoản? Đăng nhập</a></p>
        </form>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>