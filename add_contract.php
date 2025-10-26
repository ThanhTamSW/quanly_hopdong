<?php
$page_title = 'Thêm Hợp đồng';
$requires_login = true;
// Cache busting: Force browser to reload JS/CSS
$cache_version = '?v=' . filemtime(__FILE__);
include 'includes/header.php'; 
include 'includes/db.php';

$coaches = $conn->query("SELECT id, name as full_name FROM coaches ORDER BY name");

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
            <!-- ĐÃ XÓA: Gói sản phẩm dropdown - Người dùng tự nhập số buổi và giá -->

            <div class="col-md-6">
                <label for="total_sessions" class="form-label">Tổng số buổi</label>
                <input type="number" name="total_sessions" id="total_sessions" class="form-control" min="1" required>
            </div>
            <div class="col-md-6">
                <label for="total_price" class="form-label">Giá gốc (VNĐ)</label>
                <input type="number" name="total_price" id="total_price" class="form-control" min="0" required>
            </div>
            <div class="col-md-6">
                <label for="discount_percentage" class="form-label">Giảm giá</label>
                <select name="discount_percentage" id="discount_percentage" class="form-select">
                    <option value="0">Không giảm giá</option>
                    <option value="5">Giảm 5%</option>
                    <option value="10">Giảm 10%</option>
                    <option value="15">Giảm 15%</option>
                    <option value="20">Giảm 20%</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="final_price" class="form-label">Thành tiền (VNĐ)</label>
                <input type="text" id="final_price_display" class="form-control" style="font-weight: bold; color: #0d6efd; background-color: #e9ecef;" readonly>
                <input type="hidden" name="final_price" id="final_price" required>
            </div>

            <hr class="my-4 col-12">
            
            <h5 class="col-12">💰 Hình thức thanh toán</h5>
            
            <div class="col-12">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="payment_type" id="payment_full" value="full" checked>
                    <label class="form-check-label" for="payment_full">
                        💵 Thanh toán full
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="payment_type" id="payment_installment" value="installment">
                    <label class="form-check-label" for="payment_installment">
                        📊 Trả góp nhiều đợt
                    </label>
                </div>
            </div>

            <div id="installment_section" class="col-12" style="display: none;">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <strong>📋 Thiết lập các đợt trả góp</strong>
                    </div>
                    <div class="card-body">
                        <div id="installment_list">
                            <!-- Đợt 1 mặc định -->
                            <div class="row g-3 mb-3 align-items-center installment-row" data-index="1">
                                <div class="col-md-1">
                                    <label class="form-label">Đợt 1</label>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Phần trăm (%)</label>
                                    <input type="number" name="installment_percentages[]" class="form-control installment-percentage" min="0" max="100" step="0.01" placeholder="30">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Số tiền (VNĐ)</label>
                                    <input type="number" name="installment_amounts[]" class="form-control installment-amount" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ngày đến hạn</label>
                                    <input type="date" name="installment_dates[]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100 remove-installment" onclick="removeInstallment(this)">Xóa</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-outline-primary" onclick="addInstallment()">+ Thêm đợt thanh toán</button>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Tổng %:</strong> <span id="total_percentage">0</span>%
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <strong>Tổng tiền:</strong> <span id="total_installment_amount">0</span> VNĐ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4 col-12">

            <h5 class="col-12 text-center">📅 Thiết lập Lịch tập Cố định (Không bắt buộc)</h5>
            
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>💡 Hướng dẫn:</strong> 
                    <ul class="mb-0 mt-2">
                        <li>Chọn <strong>thứ và giờ tập</strong> để tự động tạo lịch</li>
                        <li><strong>"Từ ngày"</strong> để trống → tự động dùng ngày bắt đầu hợp đồng</li>
                        <li>Có thể tạo nhiều nhóm lịch khác nhau cho từng giai đoạn</li>
                        <li><em>VD: Tháng 11-12 tập T2-T4-T6, từ tháng 1 tập T3-T5-T7</em></li>
                    </ul>
                </div>
            </div>

            <div id="schedule_groups_container" class="col-12">
                <!-- Nhóm lịch 1 -->
                <div class="card border-primary mb-3 schedule-group">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <strong>📋 Nhóm lịch 1</strong>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeScheduleGroup(this)" style="display: none;">Xóa nhóm</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Từ ngày (tùy chọn)</label>
                                <input type="date" name="schedule_group_start[]" class="form-control schedule-group-start">
                                <div class="form-text">Để trống = dùng ngày bắt đầu hợp đồng</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Đến ngày (tùy chọn)</label>
                                <input type="date" name="schedule_group_end[]" class="form-control">
                                <div class="form-text">Để trống = áp dụng đến hết hợp đồng</div>
                            </div>
                        </div>
                        
                        <div class="schedule-times-list">
                            <div class="row g-3 mb-2 align-items-center">
                                <div class="col-md-5">
                                    <select name="schedule_days_0[]" class="form-select">
                                        <option value="">-- Chọn thứ --</option>
                                        <option value="1">Thứ Hai</option>
                                        <option value="2">Thứ Ba</option>
                                        <option value="3">Thứ Tư</option>
                                        <option value="4">Thứ Năm</option>
                                        <option value="5">Thứ Sáu</option>
                                        <option value="6">Thứ Bảy</option>
                                        <option value="7">Chủ Nhật</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="time" name="schedule_times_0[]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">Xóa</button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-outline-secondary add-schedule-time" onclick="addScheduleTime(this)">+ Thêm giờ tập</button>
                    </div>
                </div>
            </div>
            
            <div class="col-12">
                <button type="button" class="btn btn-outline-primary" onclick="addScheduleGroup()">+ Thêm nhóm lịch khác (khi đổi lịch)</button>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-success px-5">Lưu Hợp đồng & Tạo Lịch</button>
                <a href="index.php" class="btn btn-secondary px-4">Hủy</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Cache version: <?= time() ?> - Force browser reload
    console.log('Form version: <?= date("Y-m-d H:i:s") ?>');
    
    const packageNameSelect = document.getElementById('package_name');
    const totalSessionsInput = document.getElementById('total_sessions');
    const totalPriceInput = document.getElementById('total_price');
    const discountSelect = document.getElementById('discount_percentage');
    const finalPriceInput = document.getElementById('final_price');
    const customPackageWrapper = document.getElementById('custom_package_wrapper');
    const customPackageInput = document.getElementById('custom_package_name');
    const contractStartDate = document.getElementById('start_date');
    const scheduleStartDate = document.getElementById('schedule_start_date');
    
    // Payment type elements
    const paymentFull = document.getElementById('payment_full');
    const paymentInstallment = document.getElementById('payment_installment');
    const installmentSection = document.getElementById('installment_section');
    
    let installmentCounter = 1;

    function calculateFinalPrice() {
        const price = parseFloat(totalPriceInput.value) || 0;
        const discount = parseFloat(discountSelect.value) || 0;
        const finalPrice = price * (1 - discount / 100);
        const roundedPrice = Math.round(finalPrice);
        
        // Set both display and hidden input
        document.getElementById('final_price_display').value = roundedPrice.toLocaleString('vi-VN');
        finalPriceInput.value = roundedPrice;
        
        calculateInstallments();
    }
    
    // Toggle payment section
    paymentFull.addEventListener('change', function() {
        if (this.checked) {
            installmentSection.style.display = 'none';
        }
    });
    
    paymentInstallment.addEventListener('change', function() {
        if (this.checked) {
            installmentSection.style.display = 'block';
            calculateInstallments();
        }
    });
    
    // Add new installment row
    function addInstallment() {
        installmentCounter++;
        const installmentList = document.getElementById('installment_list');
        const newRow = document.createElement('div');
        newRow.className = 'row g-3 mb-3 align-items-center installment-row';
        newRow.setAttribute('data-index', installmentCounter);
        newRow.innerHTML = `
            <div class="col-md-1">
                <label class="form-label">Đợt ${installmentCounter}</label>
            </div>
            <div class="col-md-3">
                <label class="form-label">Phần trăm (%)</label>
                <input type="number" name="installment_percentages[]" class="form-control installment-percentage" min="0" max="100" step="0.01" placeholder="30">
            </div>
            <div class="col-md-3">
                <label class="form-label">Số tiền (VNĐ)</label>
                <input type="number" name="installment_amounts[]" class="form-control installment-amount" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày đến hạn</label>
                <input type="date" name="installment_dates[]" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100 remove-installment" onclick="removeInstallment(this)">Xóa</button>
            </div>
        `;
        installmentList.appendChild(newRow);
        
        // Add event listener for new percentage input
        const newPercentageInput = newRow.querySelector('.installment-percentage');
        newPercentageInput.addEventListener('input', calculateInstallments);
    }
    
    // Remove installment row
    function removeInstallment(button) {
        const rows = document.querySelectorAll('.installment-row');
        if (rows.length > 1) {
            button.closest('.installment-row').remove();
            updateInstallmentLabels();
            calculateInstallments();
        } else {
            alert('Phải có ít nhất 1 đợt thanh toán!');
        }
    }
    
    // Update installment labels after deletion
    function updateInstallmentLabels() {
        const rows = document.querySelectorAll('.installment-row');
        rows.forEach((row, index) => {
            row.querySelector('.col-md-1 label').textContent = `Đợt ${index + 1}`;
            row.setAttribute('data-index', index + 1);
        });
        installmentCounter = rows.length;
    }
    
    // Calculate installment amounts based on percentages
    function calculateInstallments() {
        const finalPrice = parseFloat(finalPriceInput.value) || 0;
        const percentageInputs = document.querySelectorAll('.installment-percentage');
        const amountInputs = document.querySelectorAll('.installment-amount');
        
        let totalPercentage = 0;
        let totalAmount = 0;
        
        percentageInputs.forEach((input, index) => {
            const percentage = parseFloat(input.value) || 0;
            const amount = Math.round((finalPrice * percentage) / 100);
            
            amountInputs[index].value = amount;
            totalPercentage += percentage;
            totalAmount += amount;
        });
        
        document.getElementById('total_percentage').textContent = totalPercentage.toFixed(2);
        document.getElementById('total_installment_amount').textContent = totalAmount.toLocaleString('vi-VN');
        
        // Highlight if total percentage is not 100%
        const totalPercentageElement = document.getElementById('total_percentage').parentElement;
        if (Math.abs(totalPercentage - 100) > 0.01) {
            totalPercentageElement.classList.remove('alert-info');
            totalPercentageElement.classList.add('alert-warning');
        } else {
            totalPercentageElement.classList.remove('alert-warning');
            totalPercentageElement.classList.add('alert-info');
        }
    }
    
    // Add event listeners for existing percentage inputs
    document.addEventListener('DOMContentLoaded', function() {
        const percentageInputs = document.querySelectorAll('.installment-percentage');
        percentageInputs.forEach(input => {
            input.addEventListener('input', calculateInstallments);
        });
    });

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

    let scheduleGroupCounter = 0;

    // Add schedule time to a group
    function addScheduleTime(button) {
        const timesList = button.previousElementSibling;
        const groupIndex = Array.from(document.querySelectorAll('.schedule-group')).indexOf(button.closest('.schedule-group'));
        
        const newRow = document.createElement('div');
        newRow.className = 'row g-3 mb-2 align-items-center';
        newRow.innerHTML = `
            <div class="col-md-5">
                <select name="schedule_days_${groupIndex}[]" class="form-select">
                    <option value="">-- Chọn thứ --</option>
                    <option value="1">Thứ Hai</option>
                    <option value="2">Thứ Ba</option>
                    <option value="3">Thứ Tư</option>
                    <option value="4">Thứ Năm</option>
                    <option value="5">Thứ Sáu</option>
                    <option value="6">Thứ Bảy</option>
                    <option value="7">Chủ Nhật</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="time" name="schedule_times_${groupIndex}[]" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">Xóa</button>
            </div>
        `;
        timesList.appendChild(newRow);
    }

    // Add new schedule group
    function addScheduleGroup() {
        scheduleGroupCounter++;
        const container = document.getElementById('schedule_groups_container');
        const groupCount = container.querySelectorAll('.schedule-group').length + 1;
        
        const newGroup = document.createElement('div');
        newGroup.className = 'card border-primary mb-3 schedule-group';
        newGroup.innerHTML = `
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <strong>📋 Nhóm lịch ${groupCount}</strong>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeScheduleGroup(this)">Xóa nhóm</button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="schedule_group_start[]" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Đến ngày (tùy chọn)</label>
                        <input type="date" name="schedule_group_end[]" class="form-control">
                        <div class="form-text">Để trống nếu áp dụng đến hết hợp đồng</div>
                    </div>
                </div>
                
                <div class="schedule-times-list">
                    <div class="row g-3 mb-2 align-items-center">
                        <div class="col-md-5">
                            <select name="schedule_days_${groupCount - 1}[]" class="form-select">
                                <option value="">-- Chọn thứ --</option>
                                <option value="1">Thứ Hai</option>
                                <option value="2">Thứ Ba</option>
                                <option value="3">Thứ Tư</option>
                                <option value="4">Thứ Năm</option>
                                <option value="5">Thứ Sáu</option>
                                <option value="6">Thứ Bảy</option>
                                <option value="7">Chủ Nhật</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="time" name="schedule_times_${groupCount - 1}[]" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">Xóa</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-sm btn-outline-secondary add-schedule-time" onclick="addScheduleTime(this)">+ Thêm giờ tập</button>
            </div>
        `;
        container.appendChild(newGroup);
        updateGroupHeaders();
    }

    // Remove schedule group
    function removeScheduleGroup(button) {
        if (document.querySelectorAll('.schedule-group').length > 1) {
            button.closest('.schedule-group').remove();
            updateGroupHeaders();
        } else {
            alert('Phải giữ lại ít nhất 1 nhóm lịch!');
        }
    }

    // Update group headers
    function updateGroupHeaders() {
        const groups = document.querySelectorAll('.schedule-group');
        groups.forEach((group, index) => {
            const header = group.querySelector('.card-header strong');
            header.textContent = `📋 Nhóm lịch ${index + 1}`;
            
            // Show/hide delete button
            const deleteBtn = group.querySelector('.card-header .btn-danger');
            if (groups.length > 1) {
                deleteBtn.style.display = '';
            } else {
                deleteBtn.style.display = 'none';
            }
        });
    }
    
    // Initial call
    updateGroupHeaders();
</script>

<?php 
include 'includes/footer.php'; 
?>