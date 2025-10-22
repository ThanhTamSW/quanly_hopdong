<?php
$page_title = 'Chi tiết thanh toán';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';

$contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;

if ($contract_id == 0) {
    die("Thiếu thông tin hợp đồng.");
}

// Lấy thông tin hợp đồng
$stmt = $conn->prepare("
    SELECT 
        c.*,
        client.full_name AS client_name,
        client.phone_number AS client_phone,
        coach.full_name AS coach_name
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    JOIN users coach ON c.coach_id = coach.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    die("Không tìm thấy hợp đồng.");
}

// Lấy danh sách các đợt thanh toán
$stmt_installments = $conn->prepare("
    SELECT * FROM payment_installments
    WHERE contract_id = ?
    ORDER BY installment_number ASC
");
$stmt_installments->bind_param("i", $contract_id);
$stmt_installments->execute();
$installments = $stmt_installments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_installments->close();

// Xử lý cập nhật trạng thái thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installment_id'])) {
    $installment_id = intval($_POST['installment_id']);
    $new_status = $_POST['status'];
    $paid_date = ($new_status === 'paid') ? date('Y-m-d') : null;
    
    $conn->begin_transaction();
    try {
        // Cập nhật trạng thái đợt thanh toán
        $stmt_update = $conn->prepare("UPDATE payment_installments SET status = ?, paid_date = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $new_status, $paid_date, $installment_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Tính lại tổng số tiền đã trả
        $stmt_sum = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total_paid FROM payment_installments WHERE contract_id = ? AND status = 'paid'");
        $stmt_sum->bind_param("i", $contract_id);
        $stmt_sum->execute();
        $total_paid = $stmt_sum->get_result()->fetch_assoc()['total_paid'];
        $stmt_sum->close();
        
        // Cập nhật paid_amount trong contracts
        $stmt_update_contract = $conn->prepare("UPDATE contracts SET paid_amount = ? WHERE id = ?");
        $stmt_update_contract->bind_param("ii", $total_paid, $contract_id);
        $stmt_update_contract->execute();
        $stmt_update_contract->close();
        
        $conn->commit();
        
        // Reload trang
        header("Location: payment_detail.php?contract_id=" . $contract_id . "&success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Lỗi: " . $e->getMessage();
    }
}
?>

<div class="container my-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            ✅ Đã cập nhật trạng thái thanh toán thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Thông tin hợp đồng -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📋 Thông tin hợp đồng #<?= $contract['id'] ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Học viên:</strong> <?= htmlspecialchars($contract['client_name']) ?></p>
                    <p><strong>SĐT:</strong> <?= htmlspecialchars($contract['client_phone']) ?></p>
                    <p><strong>Coach:</strong> <?= htmlspecialchars($contract['coach_name']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Gói:</strong> <?= htmlspecialchars($contract['package_name']) ?></p>
                    <p><strong>Ngày bắt đầu:</strong> <?= date('d/m/Y', strtotime($contract['start_date'])) ?></p>
                    <p><strong>Tổng tiền:</strong> <span class="text-primary"><strong><?= number_format($contract['final_price'], 0, ',', '.') ?>đ</strong></span></p>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6>Đã thanh toán</h6>
                            <h3><?= number_format($contract['paid_amount'], 0, ',', '.') ?>đ</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6>Còn lại</h6>
                            <h3><?= number_format($contract['final_price'] - $contract['paid_amount'], 0, ',', '.') ?>đ</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h6>Tiến độ</h6>
                            <h3><?= number_format(($contract['final_price'] > 0 ? ($contract['paid_amount'] / $contract['final_price']) * 100 : 0), 1) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Danh sách các đợt thanh toán -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">💵 Chi tiết các đợt thanh toán</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($installments)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Đợt</th>
                                <th>Phần trăm</th>
                                <th>Số tiền</th>
                                <th>Ngày đến hạn</th>
                                <th>Trạng thái</th>
                                <th>Ngày thanh toán</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($installments as $inst): 
                                $status_class = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger'
                                ];
                                $status_text = [
                                    'pending' => '⏳ Chưa trả',
                                    'paid' => '✅ Đã trả',
                                    'overdue' => '⚠️ Quá hạn'
                                ];
                            ?>
                                <tr>
                                    <td><strong>Đợt <?= $inst['installment_number'] ?></strong></td>
                                    <td><?= number_format($inst['percentage'], 2) ?>%</td>
                                    <td><strong><?= number_format($inst['amount'], 0, ',', '.') ?>đ</strong></td>
                                    <td><?= $inst['due_date'] ? date('d/m/Y', strtotime($inst['due_date'])) : '-' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $status_class[$inst['status']] ?>">
                                            <?= $status_text[$inst['status']] ?>
                                        </span>
                                    </td>
                                    <td><?= $inst['paid_date'] ? date('d/m/Y', strtotime($inst['paid_date'])) : '-' ?></td>
                                    <td>
                                        <?php if ($inst['status'] !== 'paid'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Xác nhận đã nhận thanh toán?')">
                                                    ✅ Đánh dấu đã trả
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Hủy trạng thái đã thanh toán?')">
                                                    ↩️ Hủy
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2"><strong>TỔNG CỘNG</strong></td>
                                <td><strong><?= number_format(array_sum(array_column($installments, 'amount')), 0, ',', '.') ?>đ</strong></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Không có thông tin trả góp cho hợp đồng này.
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="payment_installments.php" class="btn btn-secondary">← Quay lại danh sách</a>
                <a href="view_sessions.php?contract_id=<?= $contract_id ?>" class="btn btn-primary">📅 Xem lịch tập</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

