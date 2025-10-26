<?php
$page_title = 'Sửa Hợp đồng';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$contract_id = intval($_GET['id']);

// Lấy thông tin hiện tại của hợp đồng
$stmt = $conn->prepare("SELECT * FROM contracts WHERE id = ?");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    die("Không tìm thấy hợp đồng.");
}

$coaches = $conn->query("SELECT id, name as full_name FROM coaches ORDER BY name");

$client_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$client_stmt->bind_param("i", $contract['client_id']);
$client_stmt->execute();
$client_name = $client_stmt->get_result()->fetch_assoc()['full_name'];
$client_stmt->close();

?>

<div class="card shadow-sm mx-auto" style="max-width: 800px;">
    <div class="card-header text-center bg-warning">
        <h4>✍️ Sửa hợp đồng và Lịch tập</h4>
    </div>
    <div class="card-body">
        <form action="actions/update_contract.php" method="POST" class="row g-3">
            <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
            
            <h5 class="col-12">📝 Thông tin hợp đồng</h5>
            
            <div class="col-md-6">
                <label class="form-label">Học viên</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($client_name) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label for="coach_id" class="form-label">Huấn luyện viên</label>
                <select name="coach_id" id="coach_id" class="form-select" required>
                    <?php mysqli_data_seek($coaches, 0); while($coach = $coaches->fetch_assoc()): ?>
                        <option value="<?= $coach['id'] ?>" <?= ($coach['id'] == $contract['coach_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($coach['full_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="start_date" class="form-label">Ngày bắt đầu hợp đồng</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $contract['start_date'] ?>" required>
            </div>

            <div class="col-md-6">
                <label for="package_name" class="form-label">Gói sản phẩm</label>
                <input type="text" name="package_name" id="package_name" class="form-control" value="<?= htmlspecialchars($contract['package_name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label for="total_sessions" class="form-label">Tổng số buổi</label>
                <input type="number" name="total_sessions" id="total_sessions" class="form-control" value="<?= $contract['total_sessions'] ?>" min="1" required>
            </div>
            
            <div class="col-md-6">
                <label for="total_price" class="form-label">Giá gốc (VNĐ)</label>
                <input type="number" name="total_price" id="total_price" class="form-control" value="<?= $contract['total_price'] ?>" min="0" required>
            </div>

            <div class="col-md-6">
                <label for="discount_percentage" class="form-label">Giảm giá</label>
                <select name="discount_percentage" id="discount_percentage" class="form-select">
                    <option value="0" <?= ($contract['discount_percentage'] == 0) ? 'selected' : '' ?>>Không giảm giá</option>
                    <option value="5" <?= ($contract['discount_percentage'] == 5) ? 'selected' : '' ?>>Giảm 5%</option>
                    <option value="10" <?= ($contract['discount_percentage'] == 10) ? 'selected' : '' ?>>Giảm 10%</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="final_price" class="form-label">Thành tiền (VNĐ)</label>
                <input type="number" name="final_price" id="final_price" class="form-control" value="<?= $contract['final_price'] ?>" readonly style="font-weight: bold; color: #0d6efd;">
            </div>

            <hr class="my-4 col-12">

            <h5 class="col-12 text-center">🔄 Thiết lập lại Lịch tập Cố định</h5>

            <div class="col-md-6">
                <label for="schedule_start_date" class="form-label">Ngày bắt đầu tạo lịch</label>
                <input type="date" name="schedule_start_date" id="schedule_start_date" class="form-control">
                <div class="form-text">Điền nếu muốn tạo lại lịch từ một ngày khác. Mặc định là ngày bắt đầu HĐ.</div>
            </div>
            
            <div class="col-12">
                <div class="form-check bg-light p-3 border rounded">
                    <input class="form-check-input" type="checkbox" name="regenerate_schedule" id="regenerate_schedule">
                    <label class="form-check-label" for="regenerate_schedule">
                        <strong>Xác nhận tạo lại lịch:</strong> Xóa các buổi tập chưa hoàn thành và tạo lại lịch mới theo thiết lập bên dưới.
                    </label>
                </div>
            </div>
            <div id="schedule_list" class="col-12 mt-2"></div>
            <div class="col-12">
                <button type="button" id="add_schedule_button" class="btn btn-outline-primary">+ Thêm buổi tập trong tuần</button>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary px-4">Lưu thay đổi</button>
                <a href="index.php" class="btn btn-secondary px-4">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
    const totalPriceInput = document.getElementById('total_price');
    const discountSelect = document.getElementById('discount_percentage');
    const finalPriceInput = document.getElementById('final_price');

    function calculateFinalPrice() {
        const price = parseFloat(totalPriceInput.value) || 0;
        const discount = parseFloat(discountSelect.value) || 0;
        const finalPrice = price * (1 - discount / 100);
        finalPriceInput.value = Math.round(finalPrice);
    }
    discountSelect.addEventListener('change', calculateFinalPrice);
    totalPriceInput.addEventListener('input', calculateFinalPrice);

    const contractStartDate = document.getElementById('start_date');
    const scheduleStartDate = document.getElementById('schedule_start_date');
    scheduleStartDate.value = contractStartDate.value;
    contractStartDate.addEventListener('change', function() {
        scheduleStartDate.value = this.value;
    });
    
    document.getElementById('add_schedule_button').addEventListener('click', function() {
        const scheduleList = document.getElementById('schedule_list');
        const newScheduleRow = document.createElement('div');
        newScheduleRow.classList.add('row', 'g-3', 'mb-2', 'align-items-center');
        newScheduleRow.innerHTML = `
            <div class="col-md-5">
                <select name="schedule_days[]" class="form-select">
                    <option value="">-- Chọn thứ --</option>
                    <option value="1">Thứ Hai</option><option value="2">Thứ Ba</option><option value="3">Thứ Tư</option>
                    <option value="4">Thứ Năm</option><option value="5">Thứ Sáu</option><option value="6">Thứ Bảy</option><option value="7">Chủ Nhật</option>
                </select>
            </div>
            <div class="col-md-5"><input type="time" name="schedule_times[]" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">Xóa</button></div>
        `;
        scheduleList.appendChild(newScheduleRow);
    });
</script>

<?php 
include 'includes/footer.php'; 
?>