<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'includes/header.php';
include 'includes/db.php';

$page_title = "Import Hợp Đồng";
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>📥 Import Hợp Đồng Từ Excel</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>

            <!-- Hướng dẫn -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">📋 Hướng Dẫn Import</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li><strong>Tải file mẫu:</strong> <a href="download_template.php" class="btn btn-sm btn-success">📥 Tải Template Excel</a></li>
                        <li><strong>Điền thông tin</strong> vào file Excel theo mẫu</li>
                        <li><strong>Upload file</strong> và xem preview dữ liệu</li>
                        <li><strong>Kiểm tra</strong> và xác nhận import</li>
                    </ol>
                    <div class="alert alert-warning mb-0">
                        <strong>⚠️ Lưu ý:</strong>
                        <ul class="mb-0">
                            <li>File phải có định dạng .xlsx hoặc .xls</li>
                            <li>Số điện thoại phải đúng định dạng (10-11 số)</li>
                            <li>Ngày tháng phải đúng định dạng (DD/MM/YYYY)</li>
                            <li>Hệ thống sẽ tự động lọc dữ liệu trùng lặp</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form Upload -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📤 Upload File Excel</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Chọn file Excel:</label>
                            <input type="file" class="form-control" id="excelFile" name="excel_file" 
                                   accept=".xlsx,.xls" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skipDuplicates" 
                                       name="skip_duplicates" checked>
                                <label class="form-check-label" for="skipDuplicates">
                                    <strong>Tự động bỏ qua hợp đồng trùng lặp</strong>
                                    <small class="text-muted d-block">
                                        (Kiểm tra theo số điện thoại học viên + ngày bắt đầu)
                                    </small>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoCreateUsers" 
                                       name="auto_create_users" checked>
                                <label class="form-check-label" for="autoCreateUsers">
                                    <strong>Tự động tạo tài khoản cho học viên mới</strong>
                                    <small class="text-muted d-block">
                                        (Mật khẩu mặc định = Số điện thoại)
                                    </small>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-cloud-upload"></i> Upload và Preview
                        </button>
                    </form>
                </div>
            </div>

            <!-- Loading -->
            <div id="loadingDiv" class="text-center" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang xử lý file...</p>
            </div>

            <!-- Preview Data -->
            <div id="previewDiv" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">👁️ Preview Dữ Liệu</h5>
                    </div>
                    <div class="card-body">
                        <div id="statisticsDiv" class="mb-3"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="previewTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Học viên</th>
                                        <th>SĐT Học viên</th>
                                        <th>Coach</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Số buổi</th>
                                        <th>Giá gốc</th>
                                        <th>Giảm giá (%)</th>
                                        <th>Giá cuối</th>
                                        <th>Loại TT</th>
                                        <th>Số đợt</th>
                                        <th>Đặt cọc</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-success btn-lg" id="confirmImportBtn">
                                <i class="bi bi-check-circle"></i> Xác Nhận Import
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn">
                                <i class="bi bi-x-circle"></i> Hủy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result -->
            <div id="resultDiv" style="display: none;">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📊 Kết Quả Import</h5>
                    </div>
                    <div class="card-body" id="resultContent">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let previewData = null;

// Upload form
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loadingDiv = document.getElementById('loadingDiv');
    const previewDiv = document.getElementById('previewDiv');
    
    loadingDiv.style.display = 'block';
    previewDiv.style.display = 'none';
    
    try {
        const response = await fetch('actions/preview_import.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            previewData = result.data;
            displayPreview(result.data, result.statistics);
            previewDiv.style.display = 'block';
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        alert('Lỗi khi upload file: ' + error.message);
    } finally {
        loadingDiv.style.display = 'none';
    }
});

// Display preview
function displayPreview(data, stats) {
    // Statistics
    const statsDiv = document.getElementById('statisticsDiv');
    statsDiv.innerHTML = `
        <div class="row text-center">
            <div class="col-md-3">
                <div class="alert alert-primary">
                    <h4>${stats.total}</h4>
                    <small>Tổng số dòng</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-success">
                    <h4>${stats.valid}</h4>
                    <small>Dữ liệu hợp lệ</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-warning">
                    <h4>${stats.duplicates}</h4>
                    <small>Trùng lặp (bỏ qua)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-danger">
                    <h4>${stats.invalid}</h4>
                    <small>Lỗi</small>
                </div>
            </div>
        </div>
    `;
    
    // Table
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';
    
    data.forEach((row, index) => {
        const tr = document.createElement('tr');
        tr.className = row.status === 'error' ? 'table-danger' : 
                       row.status === 'duplicate' ? 'table-warning' : '';
        
        const paymentTypeLabel = row.payment_type === 'installment' ? 
            '<span class="badge bg-info">Trả góp</span>' : 
            '<span class="badge bg-secondary">Trả 1 lần</span>';
        
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${row.client_name}</td>
            <td>${row.client_phone}</td>
            <td>${row.coach_name}</td>
            <td>${row.start_date}</td>
            <td>${row.total_sessions}</td>
            <td>${formatNumber(row.total_price)}</td>
            <td>${row.discount_percentage}%</td>
            <td>${formatNumber(row.final_price)}</td>
            <td>${paymentTypeLabel}</td>
            <td>${row.number_of_installments || 1}</td>
            <td>${formatNumber(row.first_payment || 0)}</td>
            <td>
                ${row.status === 'valid' ? '<span class="badge bg-success">Hợp lệ</span>' :
                  row.status === 'duplicate' ? '<span class="badge bg-warning">Trùng</span>' :
                  '<span class="badge bg-danger">Lỗi</span>'}
                ${row.error ? '<br><small class="text-danger">' + row.error + '</small>' : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Confirm import
document.getElementById('confirmImportBtn').addEventListener('click', async function() {
    if (!confirm('Bạn có chắc chắn muốn import ' + previewData.filter(r => r.status === 'valid').length + ' hợp đồng?')) {
        return;
    }
    
    const loadingDiv = document.getElementById('loadingDiv');
    const resultDiv = document.getElementById('resultDiv');
    
    loadingDiv.style.display = 'block';
    
    try {
        const response = await fetch('actions/execute_import.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                data: previewData.filter(r => r.status === 'valid'),
                auto_create_users: document.getElementById('autoCreateUsers').checked
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayResult(result);
            resultDiv.style.display = 'block';
            document.getElementById('previewDiv').style.display = 'none';
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        alert('Lỗi khi import: ' + error.message);
    } finally {
        loadingDiv.style.display = 'none';
    }
});

// Display result
function displayResult(result) {
    const resultContent = document.getElementById('resultContent');
    resultContent.innerHTML = `
        <div class="alert alert-success">
            <h4>✅ Import Thành Công!</h4>
            <hr>
            <p><strong>Số hợp đồng đã import:</strong> ${result.imported}</p>
            <p><strong>Học viên mới được tạo:</strong> ${result.new_users}</p>
            <p><strong>Coach được gán:</strong> ${result.coaches_assigned}</p>
        </div>
        <a href="index.php" class="btn btn-primary">Xem danh sách hợp đồng</a>
        <button type="button" class="btn btn-secondary" onclick="location.reload()">Import thêm</button>
    `;
}

// Cancel
document.getElementById('cancelBtn').addEventListener('click', function() {
    if (confirm('Bạn có chắc muốn hủy?')) {
        location.reload();
    }
});

// Format number
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num) + ' đ';
}
</script>

<?php include 'includes/footer.php'; ?>

