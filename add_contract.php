<?php
$page_title = 'Thêm Hợp đồng';
$requires_login = true;
include 'includes/header.php'; 
include 'includes/db.php';

$coaches = $conn->query("SELECT id, full_name FROM users WHERE role = 'coach' ORDER BY full_name");

$packages = [
    "TF8 - 8 buổi" => ['sessions' => 8, 'price' => 8 * 250000],
    "TF12 - 12 buổi" => ['sessions' => 12, 'price' => 12 * 250000],
    "TH180 - 48 buổi" => ['sessions' => 48, 'price' => 48 * 375000],
    "TH365 - 96 buổi" => ['sessions' => 96, 'price' => 96 * 312000],
    "TF50 - 50 buổi" => ['sessions' => 50, 'price' => 50 * 500000],
    "TF100 - 100 buổi" => ['sessions' => 100, 'price' => 100 * 450000]
];
?>

<div class="card shadow-sm mx-auto" style="max-width: 800px;">
    <div class="card-header text-center bg-primary text-white">
        <h4>Thêm hợp đồng và Lịch tập</h4>
    </div>
    <div class="card-body">
        <form action="actions/save_contract.php" method="POST" class="row g-3">
            
            <h5 class="col-12">📝 Thông tin hợp đồng</h5>

            <div class="col-md-6">
                <label for="client_full_name" class="form-label">Họ tên học viên</label>
                <input type="text" name="client_full_name" id="client_full_name" class="form-control" placeholder="Nhập họ tên..." required>
            </div>
            <div class="col-md-6">
                <label for="client_phone_number" class="form-label">Số điện thoại học viên</label>
                <input type="tel" name="client_phone_number" id="client_phone_number" class="form-control" placeholder="Nhập số điện thoại..." required>
            </div>

            <div class="col-md-6">
                <label for="coach_id" class="form-label">Huấn luyện viên</label>
                <select name="coach_id" id="coach_id" class="form-select" required>
                  <option value="">-- Chọn HLV --</option>
                  <?php 
                  mysqli_data_seek($coaches, 0); 
                  while($coach = $coaches->fetch_assoc()): 
                  ?>
                    <option value="<?= $coach['id'] ?>"><?= htmlspecialchars($coach['full_name']) ?></option>
                  <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="start_date" class="form-label">Ngày bắt đầu hợp đồng</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label for="package_name" class="form-label">Gói sản phẩm</label>
                <select name="package_name" id="package_name" class="form-select" required>
                    <option value="" data-sessions="" data-price="">-- Chọn gói tập --</option>
                    <?php foreach ($packages as $name => $details): ?>
                        <option value="<?= htmlspecialchars($name) ?>" data-sessions="<?= $details['sessions'] ?>" data-price="<?= $details['price'] ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                    <option value="other">-- Khác (Nhập thủ công) --</option>
                </select>
            </div>

            <div class="col-md-6" id="custom_package_wrapper" style="display: none;">
                <label for="custom_package_name" class="form-label">Tên gói tùy chỉnh</label>
                <input type="text" name="custom_package_name" id="custom_package_name" class="form-control">
            </div>

            <div class="col-md-6">
                <label for="total_sessions" class="form-label">Tổng số buổi</label>
                <input type="number" name="total_sessions" id="total_sessions" class="form-control" min="1" required readonly>
            </div>
            <div class="col-md-6">
                <label for="total_price" class="form-label">Giá gốc (VNĐ)</label>
                <input type="number" name="total_price" id="total_price" class="form-control" min="0" required readonly>
            </div>
            <div class="col-md-6">
                <label for="discount_percentage" class="form-label">Giảm giá</label>
                <select name="discount_percentage" id="discount_percentage" class="form-select">
                    <option value="0">Không giảm giá</option>
                    <option value="5">Giảm 5%</option>
                    <option value="10">Giảm 10%</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="final_price" class="form-label">Thành tiền (VNĐ)</label>
                <input type="number" name="final_price" id="final_price" class="form-control" readonly style="font-weight: bold; color: #0d6efd;">
            </div>
            
            <hr class="my-4 col-12">

            <h5 class="col-12 text-center">📅 Thiết lập Lịch tập Cố định (Không bắt buộc)</h5>
            
            <div class="col-md-6">
                <label for="schedule_start_date" class="form-label">Ngày bắt đầu tạo lịch hàng loạt</label>
                <input type="date" name="schedule_start_date" id="schedule_start_date" class="form-control">
                <div class="form-text">Mặc định là ngày bắt đầu HĐ. Chọn ngày trong quá khứ nếu cần.</div>
            </div>
            
            <div class="col-12"></div>

            <div id="schedule_list" class="col-12 mt-2">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="row g-3 mb-2 align-items-center">
                    <div class="col-md-5">
                        <select name="schedule_days[]" class="form-select">
                            <option value="">-- Chọn thứ --</option>
                            <option value="1">Thứ Hai</option><option value="2">Thứ Ba</option><option value="3">Thứ Tư</option>
                            <option value="4">Thứ Năm</option><option value="5">Thứ Sáu</option><option value="6">Thứ Bảy</option><option value="7">Chủ Nhật</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="time" name="schedule_times[]" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">Xóa</button>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="col-12">
                <button type="button" id="add_schedule_button" class="btn btn-outline-primary">+ Thêm dòng lịch khác</button>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-success px-5">Lưu Hợp đồng & Tạo Lịch</button>
                <a href="index.php" class="btn btn-secondary px-4">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
    const packageNameSelect = document.getElementById('package_name');
    const totalSessionsInput = document.getElementById('total_sessions');
    const totalPriceInput = document.getElementById('total_price');
    const discountSelect = document.getElementById('discount_percentage');
    const finalPriceInput = document.getElementById('final_price');
    const customPackageWrapper = document.getElementById('custom_package_wrapper');
    const customPackageInput = document.getElementById('custom_package_name');
    const contractStartDate = document.getElementById('start_date');
    const scheduleStartDate = document.getElementById('schedule_start_date');

    function calculateFinalPrice() {
        const price = parseFloat(totalPriceInput.value) || 0;
        const discount = parseFloat(discountSelect.value) || 0;
        const finalPrice = price * (1 - discount / 100);
        finalPriceInput.value = Math.round(finalPrice);
    }

    packageNameSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value === 'other') {
            customPackageWrapper.style.display = 'block';
            customPackageInput.required = true;
            totalSessionsInput.readOnly = false;
            totalPriceInput.readOnly = false;
            totalSessionsInput.value = '';
            totalPriceInput.value = '';
            totalSessionsInput.focus();
        } else {
            customPackageWrapper.style.display = 'none';
            customPackageInput.required = false;
            customPackageInput.value = '';
            totalSessionsInput.value = selectedOption.getAttribute('data-sessions');
            totalPriceInput.value = selectedOption.getAttribute('data-price');
            totalSessionsInput.readOnly = true;
            totalPriceInput.readOnly = true;
        }
        calculateFinalPrice();
    });

    discountSelect.addEventListener('change', calculateFinalPrice);
    totalPriceInput.addEventListener('input', calculateFinalPrice);

    scheduleStartDate.value = contractStartDate.value;
    contractStartDate.addEventListener('change', function() {
        scheduleStartDate.value = this.value;
    });

    const addScheduleBtn = document.getElementById('add_schedule_button');
    const scheduleList = document.getElementById('schedule_list');
    
    addScheduleBtn.addEventListener('click', function() {
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