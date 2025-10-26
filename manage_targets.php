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
                        <th class="sticky-column">HLV</th>
                        <th class="text-end">Target (đ)</th>
                        <th class="text-end">Doanh thu tháng (đ)</th>
                        <th class="text-center">% Hoàn thành</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center sticky-action">Hành động</th>
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
                        <td class="sticky-column"><strong><?= htmlspecialchars($coach['full_name']) ?></strong></td>
                        <td class="text-end">
                            <span class="text-primary fw-bold">
                                <?= number_format($target, 0, ',', '.') ?>đ
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold">
                                <?= number_format($revenue, 0, ',', '.') ?>đ
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-<?= $status_class ?>" role="progressbar" 
                                     style="width: <?= min($percentage, 100) ?>%;" 
                                     aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= number_format($percentage, 1) ?>%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $status_class ?> fs-6">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td class="text-center sticky-action">
                            <button type="button" class="btn btn-sm btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTargetModal<?= $coach['id'] ?>">
                                ✏️ Sửa
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Modal Edit Target -->
                    <div class="modal fade" id="editTargetModal<?= $coach['id'] ?>" tabindex="-1" aria-labelledby="editTargetModalLabel<?= $coach['id'] ?>" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
                        <div class="modal-dialog modal-dialog-centered" style="z-index: 9999;">
                            <div class="modal-content" style="z-index: 10000; position: relative;">
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
                                                   value="<?= $target ?? 0 ?>" 
                                                   step="100000"
                                                   min="0"
                                                   required
                                                   autocomplete="off">
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

<style>
/* Fix modal z-index và overlay issues - LOẠI BỎ BACKDROP */
.modal {
    z-index: 9999 !important;
    background: rgba(0,0,0,0.3) !important;
}

.modal-backdrop {
    display: none !important;
}

.modal-dialog {
    z-index: 9999 !important;
    position: relative;
    pointer-events: auto !important;
}

.modal-content {
    z-index: 10000 !important;
    position: relative;
    pointer-events: auto !important;
    background: white !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Đảm bảo input có thể tương tác - CSS mạnh nhất */
.modal input[type="number"] {
    pointer-events: auto !important;
    user-select: text !important;
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    position: relative !important;
    z-index: 9999 !important;
    transform: none !important;
    transition: none !important;
}

/* Fix cho form trong modal */
.modal form {
    pointer-events: auto !important;
    position: relative;
    z-index: 9998 !important;
}

.modal .form-control {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 9999 !important;
    transform: none !important;
    transition: none !important;
}

/* Override tất cả CSS có thể gây conflict */
.modal .modal-body {
    pointer-events: auto !important;
    position: relative;
    z-index: 9997 !important;
}

.modal .modal-content * {
    pointer-events: auto !important;
}

/* Đảm bảo modal có thể tương tác hoàn toàn */
.modal.show {
    display: block !important;
    pointer-events: auto !important;
}

.modal.show .modal-dialog {
    pointer-events: auto !important;
}

.modal.show .modal-content {
    pointer-events: auto !important;
}

.modal.show .modal-content * {
    pointer-events: auto !important;
}

/* Loại bỏ hoàn toàn backdrop */
.modal-backdrop {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    z-index: -1 !important;
}

/* Ẩn backdrop bằng CSS mạnh nhất */
body.modal-open .modal-backdrop {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    z-index: -1 !important;
}

/* Đảm bảo body không bị block */
body.modal-open {
    overflow: auto !important;
}

/* Đảm bảo modal body có thể tương tác */
.modal-body {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10001 !important;
}

.modal-header {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10001 !important;
}

.modal-footer {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10001 !important;
}

/* Đặc biệt cho input target */
input[name="sales_target"] {
    pointer-events: auto !important;
    user-select: text !important;
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    position: relative !important;
    z-index: 9999 !important;
    transform: none !important;
    transition: none !important;
    background: white !important;
    border: 2px solid #ced4da !important;
}

/* RESPONSIVE CHO MOBILE */
@media (max-width: 768px) {
    .table-responsive {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch; /* Smooth scroll trên iOS */
    }
    
    .table {
        font-size: 0.875rem;
        margin-bottom: 0;
        min-width: 800px !important; /* Đảm bảo bảng đủ rộng để scroll */
        width: max-content; /* Đảm bảo bảng không bị co lại */
    }
    
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        white-space: nowrap;
        vertical-align: middle;
    }
    
    /* Sticky columns cho mobile */
    .sticky-column {
        position: sticky;
        left: 0;
        background: #212529 !important;
        color: white !important;
        z-index: 10;
        min-width: 120px;
        max-width: 120px;
    }
    
    .sticky-action {
        position: sticky;
        right: 0;
        background: #212529 !important;
        color: white !important;
        z-index: 10;
        min-width: 100px;
        max-width: 100px;
    }
    
    /* Tối ưu cho các cột số */
    .table td:nth-child(2),
    .table td:nth-child(3) {
        text-align: right;
        font-size: 0.8rem;
        min-width: 100px;
    }
    
    /* Progress bar nhỏ hơn */
    .progress {
        height: 20px;
        font-size: 0.75rem;
    }
    
    /* Badge nhỏ hơn */
    .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.6em;
    }
    
    /* Button nhỏ hơn */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Modal responsive */
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    
    .modal-content {
        border-radius: 0.5rem;
    }
    
    /* Card responsive */
    .card {
        margin: 0;
        border-radius: 12px;
    }
    
    .card-body {
        padding: 1rem;
    }
}

/* Responsive cho tablet */
@media (max-width: 992px) and (min-width: 769px) {
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
    }
    
    .sticky-column {
        min-width: 140px;
        max-width: 140px;
    }
    
    .sticky-action {
        min-width: 120px;
        max-width: 120px;
    }
}

/* Đảm bảo hiển thị nhất quán */
.table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    overflow-x: auto; /* Cho phép scroll ngang */
    overflow-y: visible;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.table {
    margin-bottom: 0;
    background: white;
    min-width: 800px; /* Đảm bảo bảng đủ rộng để kích hoạt scroll */
}

.table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border: none !important;
    font-weight: 600;
    padding: 12px;
    white-space: nowrap;
}

.table td {
    padding: 12px;
    vertical-align: middle;
    border-color: #dee2e6;
}

/* Đảm bảo tất cả cột hiển thị đúng */
.table th:nth-child(1),
.table td:nth-child(1) {
    min-width: 150px;
    max-width: 150px;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    min-width: 120px;
    text-align: right;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    min-width: 120px;
    text-align: right;
}

.table th:nth-child(4),
.table td:nth-child(4) {
    min-width: 150px;
    text-align: center;
}

.table th:nth-child(5),
.table td:nth-child(5) {
    min-width: 120px;
    text-align: center;
}

.table th:nth-child(6),
.table td:nth-child(6) {
    min-width: 100px;
    text-align: center;
}

/* Đảm bảo scroll hoạt động trên mọi thiết bị */
@media (max-width: 1200px) {
    .table-responsive {
        overflow-x: auto !important;
    }
    
    .table {
        min-width: 800px !important;
        width: max-content !important;
    }
}

/* Sticky columns cho desktop */
@media (min-width: 769px) {
    .sticky-column {
        position: sticky;
        left: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        z-index: 10;
    }
    
    .sticky-action {
        position: sticky;
        right: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        z-index: 10;
    }
}

/* Horizontal scroll indicator */
.table-responsive::after {
    content: "← Kéo sang trái/phải để xem thêm →";
    display: block;
    text-align: center;
    color: #6c757d;
    font-size: 0.8rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

@media (min-width: 769px) {
    .table-responsive::after {
        display: none;
    }
}
</style>

<script>
// Debug và fix input target
document.addEventListener('DOMContentLoaded', function() {
    // Đảm bảo tất cả input target có thể chỉnh sửa
    document.querySelectorAll('input[name="sales_target"]').forEach(function(input) {
        
        // Xóa các thuộc tính có thể gây vấn đề
        input.removeAttribute('readonly');
        input.removeAttribute('disabled');
        
        // Force CSS
        input.style.pointerEvents = 'auto';
        input.style.userSelect = 'text';
        input.style.position = 'relative';
        input.style.zIndex = '9999';
        input.style.transform = 'none';
        input.style.transition = 'none';
        
        // Đảm bảo input có thể focus
        input.addEventListener('click', function(e) {
            e.stopPropagation();
            this.focus();
            this.select();
        });
    });
    
    // Fix cho modal khi mở
    document.querySelectorAll('[data-bs-target^="#editTargetModal"]').forEach(function(button) {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = document.querySelector(modalId);
            if (modal) {
                // Loại bỏ backdrop ngay khi click
                setTimeout(function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(backdrop) {
                        backdrop.remove();
                    });
                }, 10);
                
                // Đợi modal hiển thị hoàn toàn
                modal.addEventListener('shown.bs.modal', function() {
                    // Loại bỏ backdrop hoàn toàn
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(backdrop) {
                        backdrop.remove();
                    });
                    
                    // Force modal có thể tương tác
                    modal.style.pointerEvents = 'auto';
                    modal.style.zIndex = '9999';
                    modal.style.background = 'rgba(0,0,0,0.3)';
                    
                    const modalDialog = modal.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.pointerEvents = 'auto';
                        modalDialog.style.zIndex = '9999';
                    }
                    
                    const modalContent = modal.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.style.pointerEvents = 'auto';
                        modalContent.style.zIndex = '10000';
                        modalContent.style.background = 'white';
                    }
                    
                    const input = modal.querySelector('input[name="sales_target"]');
                    if (input) {
                        // Force tất cả thuộc tính
                        input.removeAttribute('readonly');
                        input.removeAttribute('disabled');
                        input.style.pointerEvents = 'auto';
                        input.style.userSelect = 'text';
                        input.style.position = 'relative';
                        input.style.zIndex = '9999';
                        input.style.transform = 'none';
                        input.style.transition = 'none';
                        input.style.background = 'white';
                        input.style.border = '2px solid #ced4da';
                        
                        // Focus và select sau một chút delay
                        setTimeout(function() {
                            input.focus();
                            input.select();
                        }, 300);
                    }
                }, { once: true });
            }
        });
    });
    
    // Fix cho tất cả modal events
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
            // Force tất cả elements có thể tương tác
            const allElements = this.querySelectorAll('*');
            allElements.forEach(function(el) {
                el.style.pointerEvents = 'auto';
            });
            
            const inputs = this.querySelectorAll('input[type="number"]');
            inputs.forEach(function(input) {
                input.style.pointerEvents = 'auto';
                input.style.userSelect = 'text';
                input.removeAttribute('readonly');
                input.removeAttribute('disabled');
            });
        });
    });
    
    // Force modal hoạt động khi click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.modal')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                modal.style.pointerEvents = 'auto';
                const allElements = modal.querySelectorAll('*');
                allElements.forEach(function(el) {
                    el.style.pointerEvents = 'auto';
                });
            }
        }
    });
    
    // Mobile touch improvements
    if (window.innerWidth <= 768) {
        // Thêm touch scroll mượt hơn
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.style.webkitOverflowScrolling = 'touch';
            tableContainer.style.overflowX = 'auto';
            tableContainer.style.overflowY = 'visible';
            
            // Đảm bảo bảng có chiều rộng đủ để scroll
            const table = tableContainer.querySelector('.table');
            if (table) {
                table.style.minWidth = '800px';
                table.style.width = 'max-content';
            }
        }
        
        // Thêm indicator scroll
        const scrollIndicator = document.createElement('div');
        scrollIndicator.innerHTML = '← Kéo sang trái/phải để xem thêm →';
        scrollIndicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0.8;
        `;
        document.body.appendChild(scrollIndicator);
        
        // Ẩn indicator sau 3 giây
        setTimeout(function() {
            scrollIndicator.style.opacity = '0';
            setTimeout(function() {
                scrollIndicator.remove();
            }, 500);
        }, 3000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

