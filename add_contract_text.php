<?php
$page_title = 'Thêm hợp đồng nhanh (AI)';
$requires_login = true;
include 'includes/header.php'; 
include 'includes/db.php';

// Lấy danh sách coaches để map name -> id
$coaches = $conn->query("SELECT id, name as full_name FROM coaches ORDER BY name");
$coaches_list = [];
while($coach = $coaches->fetch_assoc()) {
    $coaches_list[] = $coach;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <!-- Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">🤖 Thêm hợp đồng nhanh với AI</h4>
                        <a href="add_contract.php" class="btn btn-light btn-sm">📝 Form thường</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>💡 Cách sử dụng:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Nhập thông tin hợp đồng dưới dạng văn bản tự nhiên</li>
                            <li>Nhấn "Phân tích với AI" để AI trích xuất thông tin</li>
                            <li>Kiểm tra kết quả, sửa nếu cần</li>
                            <li>Nhấn "Xác nhận & Lưu" để tạo hợp đồng</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Form nhập văn bản -->
            <div class="card shadow-sm mb-4" id="input-section">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📝 Bước 1: Nhập thông tin hợp đồng</h5>
                </div>
                <div class="card-body">
                    <form id="textForm">
                        <div class="mb-3">
                            <label for="contract_text" class="form-label"><strong>Nhập thông tin hợp đồng:</strong></label>
                            <textarea 
                                id="contract_text" 
                                name="contract_text" 
                                class="form-control" 
                                rows="8" 
                                placeholder="VD: Nguyễn Văn A, 0912345678, bắt đầu 01/11/2025, gói 12 buổi, giá 3 triệu, giảm 10%, HLV Tuấn, tập T2-T4-T6 lúc 7h sáng"
                                required
                            ></textarea>
                            <div class="form-text">Nhập tự nhiên, AI sẽ tự động hiểu và trích xuất thông tin</div>
                        </div>

                        <!-- Ví dụ mẫu -->
                        <div class="accordion mb-3" id="examplesAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#examples">
                                        📚 Xem ví dụ mẫu
                                    </button>
                                </h2>
                                <div id="examples" class="accordion-collapse collapse" data-bs-parent="#examplesAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="card border-success">
                                                    <div class="card-header bg-success text-white">
                                                        <strong>Ví dụ 1: Format ngắn gọn</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <code style="white-space: pre-line; font-size: 0.9rem;">Nguyễn Văn A, 0912345678, bắt đầu 1/11/2025, gói 12 buổi, giá 3 triệu, giảm 10%, HLV Tuấn, tập T2 T4 T6 lúc 7h sáng</code>
                                                        <button type="button" class="btn btn-sm btn-outline-success mt-2 w-100" onclick="useExample(1)">Dùng ví dụ này</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-info">
                                                    <div class="card-header bg-info text-white">
                                                        <strong>Ví dụ 2: Format có dấu đầu dòng</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <code style="white-space: pre-line; font-size: 0.9rem;">- Tên: Trần Thị B
- SĐT: 0987654321
- Ngày bắt đầu: 05/11/2025
- Gói: 24 buổi
- Giá: 5,000,000đ
- Giảm giá: 15%
- HLV: Minh
- Lịch: Thứ 3, 5, 7 - 18:00</code>
                                                        <button type="button" class="btn btn-sm btn-outline-info mt-2 w-100" onclick="useExample(2)">Dùng ví dụ này</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-warning">
                                                    <div class="card-header bg-warning">
                                                        <strong>Ví dụ 3: Format tin nhắn</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <code style="white-space: pre-line; font-size: 0.9rem;">Em muốn đăng ký gói 12 buổi ạ. Tên em là Lê Văn C, sđt 0901234567. Em muốn tập với HLV Hùng, bắt đầu từ 10/11. Giá 3.5 triệu giảm 5% nhé anh. Em tập T2, T5 lúc 6h chiều.</code>
                                                        <button type="button" class="btn btn-sm btn-outline-warning mt-2 w-100" onclick="useExample(3)">Dùng ví dụ này</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-danger">
                                                    <div class="card-header bg-danger text-white">
                                                        <strong>Ví dụ 4: Format đầy đủ</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <code style="white-space: pre-line; font-size: 0.9rem;">Học viên: Phạm Thị D
Số điện thoại: 0909876543
Bắt đầu: 15/11/2025
Tổng buổi: 48 buổi
Giá gốc: 18 triệu
Giảm giá: 20%
Huấn luyện viên: Tuấn
Lịch tập: Thứ 2, 4, 6 lúc 7:00 sáng</code>
                                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 w-100" onclick="useExample(4)">Dùng ví dụ này</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100" id="parseBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="parseSpinner"></span>
                            🤖 Phân tích với AI
                        </button>
                    </form>
                </div>
            </div>

            <!-- Kết quả phân tích -->
            <div class="card shadow-sm mb-4 d-none" id="result-section">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">✅ Bước 2: Kiểm tra kết quả</h5>
                </div>
                <div class="card-body">
                    <div id="result-content"></div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning" onclick="editResult()">
                            ✏️ Sửa thông tin
                        </button>
                        <button type="button" class="btn btn-success" onclick="confirmAndSave()">
                            ✅ Xác nhận & Lưu hợp đồng
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            🔄 Nhập lại
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form edit (ẩn) -->
            <div class="card shadow-sm mb-4 d-none" id="edit-section">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">✏️ Chỉnh sửa thông tin</h5>
                </div>
                <div class="card-body">
                    <form id="editForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ tên học viên *</label>
                            <input type="text" name="client_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="client_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày bắt đầu *</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tổng số buổi *</label>
                            <input type="number" name="total_sessions" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giá gốc (VNĐ) *</label>
                            <input type="number" name="total_price" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giảm giá (%)</label>
                            <input type="number" name="discount_percentage" class="form-control" min="0" max="100" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Huấn luyện viên *</label>
                            <select name="coach_id" class="form-select" required>
                                <option value="">-- Chọn HLV --</option>
                                <?php foreach($coaches_list as $coach): ?>
                                    <option value="<?= $coach['id'] ?>"><?= htmlspecialchars($coach['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thành tiền (VNĐ)</label>
                            <input type="text" name="final_price_display" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold;">
                        </div>
                        
                        <div class="col-12">
                            <h6>📅 Lịch tập:</h6>
                            <div id="schedule-container"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addScheduleRow()">+ Thêm lịch</button>
                        </div>
                        
                        <div class="col-12">
                            <button type="button" class="btn btn-success" onclick="saveEdited()">💾 Lưu thay đổi</button>
                            <button type="button" class="btn btn-secondary" onclick="cancelEdit()">❌ Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let parsedData = null;
const coachesList = <?= json_encode($coaches_list) ?>;

// Ví dụ mẫu
const examples = {
    1: "Nguyễn Văn A, 0912345678, bắt đầu 1/11/2025, gói 12 buổi, giá 3 triệu, giảm 10%, HLV Tuấn, tập T2 T4 T6 lúc 7h sáng",
    2: `- Tên: Trần Thị B
- SĐT: 0987654321
- Ngày bắt đầu: 05/11/2025
- Gói: 24 buổi
- Giá: 5,000,000đ
- Giảm giá: 15%
- HLV: Minh
- Lịch: Thứ 3, 5, 7 - 18:00`,
    3: "Em muốn đăng ký gói 12 buổi ạ. Tên em là Lê Văn C, sđt 0901234567. Em muốn tập với HLV Hùng, bắt đầu từ 10/11. Giá 3.5 triệu giảm 5% nhé anh. Em tập T2, T5 lúc 6h chiều.",
    4: `Học viên: Phạm Thị D
Số điện thoại: 0909876543
Bắt đầu: 15/11/2025
Tổng buổi: 48 buổi
Giá gốc: 18 triệu
Giảm giá: 20%
Huấn luyện viên: Tuấn
Lịch tập: Thứ 2, 4, 6 lúc 7:00 sáng`
};

function useExample(num) {
    document.getElementById('contract_text').value = examples[num];
    // Scroll to textarea
    document.getElementById('contract_text').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Submit form phân tích
document.getElementById('textForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const text = document.getElementById('contract_text').value.trim();
    if (!text) {
        alert('Vui lòng nhập thông tin hợp đồng!');
        return;
    }
    
    const parseBtn = document.getElementById('parseBtn');
    const parseSpinner = document.getElementById('parseSpinner');
    
    // Show loading
    parseBtn.disabled = true;
    parseSpinner.classList.remove('d-none');
    
    try {
        const response = await fetch('actions/parse_contract_text.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: text })
        });
        
        const result = await response.json();
        
        if (result.success) {
            parsedData = result.data;
            displayResult(result.data);
        } else {
            alert('❌ Lỗi: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra: ' + error.message);
    } finally {
        parseBtn.disabled = false;
        parseSpinner.classList.add('d-none');
    }
});

function displayResult(data) {
    const days = {
        'Monday': 'Thứ 2',
        'Tuesday': 'Thứ 3',
        'Wednesday': 'Thứ 4',
        'Thursday': 'Thứ 5',
        'Friday': 'Thứ 6',
        'Saturday': 'Thứ 7',
        'Sunday': 'Chủ nhật'
    };
    
    const scheduleHTML = data.schedule.map(s => 
        `${days[s.day] || s.day} - ${s.time}`
    ).join(', ');
    
    const html = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-primary">👤 Học viên</h6>
                        <p class="mb-0 fs-5">${data.client_name}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-primary">📱 Số điện thoại</h6>
                        <p class="mb-0 fs-5">${data.client_phone}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-success">📅 Ngày bắt đầu</h6>
                        <p class="mb-0 fs-5">${formatDate(data.start_date)}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-success">💪 Tổng số buổi</h6>
                        <p class="mb-0 fs-5">${data.total_sessions} buổi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-info">💰 Giá gốc</h6>
                        <p class="mb-0 fs-5">${formatMoney(data.total_price)}đ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-info">🎁 Giảm giá</h6>
                        <p class="mb-0 fs-5">${data.discount_percentage}%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-warning">💵 Thành tiền</h6>
                        <p class="mb-0 fs-4 fw-bold text-primary">${formatMoney(data.final_price)}đ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-warning">🏋️ Huấn luyện viên</h6>
                        <p class="mb-0 fs-5">${data.coach_name}</p>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-secondary">
                    <div class="card-body">
                        <h6 class="text-secondary">📋 Lịch tập</h6>
                        <p class="mb-0 fs-5">${scheduleHTML}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('result-content').innerHTML = html;
    document.getElementById('result-section').classList.remove('d-none');
    document.getElementById('result-section').scrollIntoView({ behavior: 'smooth' });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

function editResult() {
    if (!parsedData) return;
    
    const form = document.getElementById('editForm');
    form.client_name.value = parsedData.client_name;
    form.client_phone.value = parsedData.client_phone;
    form.start_date.value = parsedData.start_date;
    form.total_sessions.value = parsedData.total_sessions;
    form.total_price.value = parsedData.total_price;
    form.discount_percentage.value = parsedData.discount_percentage;
    form.final_price_display.value = formatMoney(parsedData.final_price);
    
    // Set coach
    const coachOption = Array.from(form.coach_id.options).find(opt => 
        opt.text.toLowerCase().includes(parsedData.coach_name.toLowerCase())
    );
    if (coachOption) {
        form.coach_id.value = coachOption.value;
    }
    
    // Render schedule
    renderSchedule(parsedData.schedule);
    
    document.getElementById('edit-section').classList.remove('d-none');
    document.getElementById('edit-section').scrollIntoView({ behavior: 'smooth' });
}

function renderSchedule(schedule) {
    const container = document.getElementById('schedule-container');
    container.innerHTML = '';
    
    schedule.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-5">
                <select name="schedule_days[]" class="form-select">
                    <option value="">-- Chọn thứ --</option>
                    <option value="Monday" ${item.day === 'Monday' ? 'selected' : ''}>Thứ 2</option>
                    <option value="Tuesday" ${item.day === 'Tuesday' ? 'selected' : ''}>Thứ 3</option>
                    <option value="Wednesday" ${item.day === 'Wednesday' ? 'selected' : ''}>Thứ 4</option>
                    <option value="Thursday" ${item.day === 'Thursday' ? 'selected' : ''}>Thứ 5</option>
                    <option value="Friday" ${item.day === 'Friday' ? 'selected' : ''}>Thứ 6</option>
                    <option value="Saturday" ${item.day === 'Saturday' ? 'selected' : ''}>Thứ 7</option>
                    <option value="Sunday" ${item.day === 'Sunday' ? 'selected' : ''}>Chủ nhật</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="time" name="schedule_times[]" class="form-control" value="${item.time}">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">Xóa</button>
            </div>
        `;
        container.appendChild(row);
    });
}

function addScheduleRow() {
    const container = document.getElementById('schedule-container');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 align-items-center';
    row.innerHTML = `
        <div class="col-md-5">
            <select name="schedule_days[]" class="form-select">
                <option value="">-- Chọn thứ --</option>
                <option value="Monday">Thứ 2</option>
                <option value="Tuesday">Thứ 3</option>
                <option value="Wednesday">Thứ 4</option>
                <option value="Thursday">Thứ 5</option>
                <option value="Friday">Thứ 6</option>
                <option value="Saturday">Thứ 7</option>
                <option value="Sunday">Chủ nhật</option>
            </select>
        </div>
        <div class="col-md-5">
            <input type="time" name="schedule_times[]" class="form-control">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">Xóa</button>
        </div>
    `;
    container.appendChild(row);
}

function saveEdited() {
    const form = document.getElementById('editForm');
    
    // Update parsedData with edited values
    parsedData.client_name = form.client_name.value;
    parsedData.client_phone = form.client_phone.value;
    parsedData.start_date = form.start_date.value;
    parsedData.total_sessions = parseInt(form.total_sessions.value);
    parsedData.total_price = parseFloat(form.total_price.value);
    parsedData.discount_percentage = parseInt(form.discount_percentage.value);
    parsedData.final_price = parsedData.total_price * (1 - parsedData.discount_percentage / 100);
    parsedData.coach_id = form.coach_id.value;
    parsedData.coach_name = form.coach_id.options[form.coach_id.selectedIndex].text;
    
    // Update schedule
    const days = form.querySelectorAll('[name="schedule_days[]"]');
    const times = form.querySelectorAll('[name="schedule_times[]"]');
    parsedData.schedule = [];
    days.forEach((daySelect, index) => {
        if (daySelect.value && times[index].value) {
            parsedData.schedule.push({
                day: daySelect.value,
                time: times[index].value
            });
        }
    });
    
    // Hide edit, show result
    document.getElementById('edit-section').classList.add('d-none');
    displayResult(parsedData);
}

function cancelEdit() {
    document.getElementById('edit-section').classList.add('d-none');
    document.getElementById('result-section').scrollIntoView({ behavior: 'smooth' });
}

async function confirmAndSave() {
    if (!parsedData) {
        alert('Không có dữ liệu để lưu!');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn lưu hợp đồng này?')) {
        return;
    }
    
    try {
        const response = await fetch('actions/save_contract_from_text.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(parsedData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Đã tạo hợp đồng thành công!');
            window.location.href = 'index.php';
        } else {
            alert('❌ Lỗi: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Có lỗi xảy ra: ' + error.message);
    }
}

function resetForm() {
    document.getElementById('contract_text').value = '';
    document.getElementById('result-section').classList.add('d-none');
    document.getElementById('edit-section').classList.add('d-none');
    parsedData = null;
    document.getElementById('input-section').scrollIntoView({ behavior: 'smooth' });
}

// Auto-calculate final price when editing
document.getElementById('editForm').addEventListener('input', function(e) {
    if (e.target.name === 'total_price' || e.target.name === 'discount_percentage') {
        const totalPrice = parseFloat(this.total_price.value) || 0;
        const discount = parseInt(this.discount_percentage.value) || 0;
        const finalPrice = totalPrice * (1 - discount / 100);
        this.final_price_display.value = formatMoney(finalPrice);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

