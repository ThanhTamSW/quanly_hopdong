<?php
$page_title = 'Quản lý trả góp';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';

// Lấy danh sách hợp đồng trả góp
$query = "
    SELECT 
        c.id,
        c.package_name,
        c.final_price,
        c.paid_amount,
        c.start_date,
        client.full_name AS client_name,
        client.phone_number AS client_phone,
        coach.full_name AS coach_name,
        (SELECT COUNT(*) FROM payment_installments WHERE contract_id = c.id) AS total_installments,
        (SELECT COUNT(*) FROM payment_installments WHERE contract_id = c.id AND status = 'paid') AS paid_installments
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    JOIN users coach ON c.coach_id = coach.id
    WHERE c.payment_type = 'installment'
    ORDER BY c.id DESC
";
$result = $conn->query($query);
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">💰 Quản lý thanh toán trả góp</h4>
            <a href="index.php" class="btn btn-light btn-sm">← Quay lại</a>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Học viên</th>
                                <th>SĐT</th>
                                <th>Coach</th>
                                <th>Gói</th>
                                <th>Tổng tiền</th>
                                <th>Đã trả</th>
                                <th>Còn lại</th>
                                <th>Tiến độ</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $remaining = $row['final_price'] - $row['paid_amount'];
                                $progress_percent = ($row['final_price'] > 0) ? ($row['paid_amount'] / $row['final_price']) * 100 : 0;
                                $progress_color = $progress_percent >= 100 ? 'success' : ($progress_percent >= 50 ? 'warning' : 'danger');
                            ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['client_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['client_phone']) ?></td>
                                    <td><?= htmlspecialchars($row['coach_name']) ?></td>
                                    <td><?= htmlspecialchars($row['package_name']) ?></td>
                                    <td><?= number_format($row['final_price'], 0, ',', '.') ?>đ</td>
                                    <td class="text-success"><strong><?= number_format($row['paid_amount'], 0, ',', '.') ?>đ</strong></td>
                                    <td class="text-danger"><strong><?= number_format($remaining, 0, ',', '.') ?>đ</strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?= $progress_color ?>" role="progressbar" style="width: <?= min($progress_percent, 100) ?>%;" aria-valuenow="<?= $progress_percent ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= number_format($progress_percent, 1) ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted"><?= $row['paid_installments'] ?>/<?= $row['total_installments'] ?> đợt</small>
                                    </td>
                                    <td>
                                        <a href="payment_detail.php?contract_id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                            📋 Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>ℹ️ Chưa có hợp đồng trả góp nào.</strong><br>
                    Các hợp đồng trả góp sẽ hiển thị ở đây.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

