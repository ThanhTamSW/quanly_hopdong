<?php
$page_title = 'Quản lý Target';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';

// Tất cả người dùng đã đăng nhập đều có thể truy cập

// Xử lý cập nhật target
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_target'])) {
    $coach_id = intval($_POST['coach_id']);
    $sales_target = floatval($_POST['sales_target']);
    
    $stmt = $conn->prepare("UPDATE users SET sales_target = ? WHERE id = ? AND role = 'coach'");
    $stmt->bind_param("di", $sales_target, $coach_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Đã cập nhật target thành công!'
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Lỗi khi cập nhật: ' . $stmt->error
        ];
    }
    $stmt->close();
    header("Location: manage_targets.php");
    exit;
}

// Lấy danh sách coaches và target
$current_month = date('Y-m-01');
$sql = "
    SELECT 
        u.id,
        u.full_name,
        u.sales_target,
        COALESCE(SUM(c.final_price), 0) as monthly_revenue
    FROM users u
    LEFT JOIN contracts c ON u.id = c.coach_id AND c.start_date >= ?
    WHERE u.role = 'coach'
    GROUP BY u.id, u.full_name, u.sales_target
    ORDER BY u.full_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$coaches = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<h2 class="mb-4">🎯 Quản lý Target Doanh thu</h2>

<?php if ($flash_message): ?>
    <div class="alert alert-<?= htmlspecialchars($flash_message['type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash_message['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Target Tháng <?= date('m/Y') ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>HLV</th>
                        <th>Target (đ)</th>
                        <th>Doanh thu tháng (đ)</th>
                        <th>% Hoàn thành</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coaches as $coach): 
                        $target = $coach['sales_target'];
                        $revenue = $coach['monthly_revenue'];
                        $percentage = ($target > 0) ? ($revenue / $target * 100) : 0;
                        
                        $status_class = 'danger';
                        $status_text = 'Chưa đạt';
                        if ($percentage >= 100) {
                            $status_class = 'success';
                            $status_text = 'Đạt target';
                        } elseif ($percentage >= 80) {
                            $status_class = 'warning';
                            $status_text = 'Gần đạt';
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($coach['full_name']) ?></strong></td>
                        <td>
                            <span class="text-primary fw-bold">
                                <?= number_format($target, 0, ',', '.') ?>đ
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold">
                                <?= number_format($revenue, 0, ',', '.') ?>đ
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-<?= $status_class ?>" role="progressbar" 
                                     style="width: <?= min($percentage, 100) ?>%;" 
                                     aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= number_format($percentage, 1) ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $status_class ?> fs-6">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTargetModal<?= $coach['id'] ?>">
                                ✏️ Sửa Target
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Modal Edit Target -->
                    <div class="modal fade" id="editTargetModal<?= $coach['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Cập nhật Target - <?= htmlspecialchars($coach['full_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="coach_id" value="<?= $coach['id'] ?>">
                                        <div class="mb-3">
                                            <label for="sales_target_<?= $coach['id'] ?>" class="form-label">Target doanh thu tháng (đ)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="sales_target_<?= $coach['id'] ?>" 
                                                   name="sales_target" 
                                                   value="<?= $target ?>" 
                                                   step="100000"
                                                   min="0"
                                                   required>
                                            <div class="form-text">Nhập target doanh thu tháng cho coach này</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" name="update_target" class="btn btn-primary">💾 Lưu Target</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="index.php" class="btn btn-secondary">⬅️ Quay lại Danh sách</a>
</div>

<?php include 'includes/footer.php'; ?>

