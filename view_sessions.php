<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Cập nhật đường dẫn đến db.php
include 'includes/db.php';

if (!isset($_GET['contract_id'])) {
    header("Location: index.php");
    exit;
}

$contract_id = intval($_GET['contract_id']);
$isCoach = ($_SESSION['role'] === 'coach');

$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Lấy thông tin chi tiết hợp đồng và SỐ BUỔI CÒN LẠI
$contract_sql = "
    SELECT 
        client.full_name AS client_name, 
        coach.full_name AS coach_name, 
        c.package_name,
        c.total_sessions,
        (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') AS sessions_completed
    FROM contracts c 
    JOIN users client ON c.client_id = client.id 
    JOIN users coach ON c.coach_id = coach.id 
    WHERE c.id = ?
";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    die("Không tìm thấy hợp đồng.");
}

$sessions_remaining = $contract['total_sessions'] - $contract['sessions_completed'];

// Lấy danh sách các buổi tập của hợp đồng này, bao gồm cả tên của coach đã hành động
$sessions_sql = "
    SELECT 
        ts.id, ts.session_datetime, ts.status, ts.action_timestamp,
        action_coach.full_name AS action_coach_name
    FROM training_sessions ts
    LEFT JOIN users action_coach ON ts.action_by_coach_id = action_coach.id
    WHERE ts.contract_id = ? 
    ORDER BY ts.session_datetime ASC
";
$stmt_sessions = $conn->prepare($sessions_sql);
$stmt_sessions->bind_param("i", $contract_id);
$stmt_sessions->execute();
$sessions = $stmt_sessions->get_result();
$stmt_sessions->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chi tiết Lịch tập</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="index.php" class="btn btn-secondary">⬅️ Quay lại Danh sách</a>
        <div class="d-flex gap-2">
            <button class="btn btn-success" onclick="showQuickReport()">📊 Báo cáo ngày</button>
            <a href="schedule_report.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline-success">📋 Báo cáo chi tiết</a>
            <a href="test_data.php" class="btn btn-info btn-sm">🔍 Test Data</a>
            <a href="add_sample_data.php" class="btn btn-warning btn-sm">➕ Add Sample</a>
            <a href="test_report_api.php" class="btn btn-danger btn-sm">🧪 Test API</a>
            <a href="test_session_actions.php" class="btn btn-primary btn-sm">⚡ Test Actions</a>
            <a href="debug_sessions.php" class="btn btn-dark btn-sm">🔍 Debug Sessions</a>
            <a href="test_update_status.php" class="btn btn-success btn-sm">🔄 Test Update</a>
            <a href="test_api_simple.php" class="btn btn-warning btn-sm">🔧 Test API Simple</a>
            <h4 class="mb-0">Chi tiết Hợp đồng</h4>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?= htmlspecialchars($flash_message['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash_message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <form action="actions/delete_sessions.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa các buổi tập đã chọn?');">
                <input type="hidden" name="contract_id" value="<?= $contract_id ?>">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5>Lịch sử buổi tập - <?= htmlspecialchars($contract['package_name']) ?></h5>
                        <p class="mb-0"><strong>Học viên:</strong> <?= htmlspecialchars($contract['client_name']) ?> | <strong>HLV:</strong> <?= htmlspecialchars($contract['coach_name']) ?></p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 5%;"><input type="checkbox" id="select_all_sessions" title="Chọn tất cả"></th>
                                        <th>Thời gian</th>
                                        <th>Trạng thái & Ghi nhận</th>
                                        <th>Hành động (HLV)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($sessions->num_rows > 0): ?>
                                        <?php mysqli_data_seek($sessions, 0); while($session = $sessions->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <?php if($session['status'] == 'scheduled'): ?>
                                                <input type="checkbox" name="session_ids[]" value="<?= $session['id'] ?>" class="session-checkbox">
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle"><?= date("d/m/Y H:i", strtotime($session['session_datetime'])) ?></td>
                                            <td class="align-middle">
                                                <?php 
                                                    if($session['status'] == 'completed') echo '<span class="badge bg-success">Đã hoàn thành</span>';
                                                    else if($session['status'] == 'scheduled') echo '<span class="badge bg-warning text-dark">Đã lên lịch</span>';
                                                    else echo '<span class="badge bg-danger">Đã hủy</span>';
                                                    
                                                    if (!empty($session['action_timestamp'])) {
                                                        echo '<br><small class="text-muted">' . date("d/m/y H:i", strtotime($session['action_timestamp'])) . '</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if($isCoach && $session['status'] == 'scheduled'): ?>
                                                    <a href="actions/edit_session.php?session_id=<?= $session['id'] ?>&contract_id=<?= $contract_id ?>" class="btn btn-warning btn-sm" title="Sửa buổi tập">✏️</a>
                                                    <a href="actions/update_session_status.php?action=complete&session_id=<?= $session['id'] ?>&contract_id=<?= $contract_id ?>" class="btn btn-success btn-sm" title="Xác nhận hoàn thành">✅</a>
                                                    <a href="actions/update_session_status.php?action=cancel&session_id=<?= $session['id'] ?>&contract_id=<?= $contract_id ?>" class="btn btn-danger btn-sm" title="Hủy buổi tập">❌</a>
                                                    <a href="actions/delete_single_session.php?session_id=<?= $session['id'] ?>&contract_id=<?= $contract_id ?>" class="btn btn-dark btn-sm" title="Xóa buổi tập này" onclick="return confirm('Bạn có chắc chắn muốn xóa buổi tập này không?');">🗑️</a>
                                                <?php elseif (!empty($session['action_coach_name'])): ?>
                                                    <span class="text-muted fst-italic">bởi <?= htmlspecialchars($session['action_coach_name']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">Chưa có buổi tập nào được tạo cho hợp đồng này.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($isCoach && $sessions->num_rows > 0): ?>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger">Xóa các mục đã chọn</button>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5>Thêm buổi tập lẻ</h5>
                </div>
                <div class="card-body">
                    <?php if($sessions_remaining > 0): ?>
                        <form action="actions/add_single_session.php" method="POST">
                            <input type="hidden" name="contract_id" value="<?= $contract_id ?>">
                            <div class="mb-3">
                                <label for="session_date" class="form-label">Ngày tập</label>
                                <input type="date" name="session_date" id="session_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="session_time" class="form-label">Giờ tập</label>
                                <input type="time" name="session_time" id="session_time" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Thêm lịch</button>
                        </form>
                        <div class="mt-3 text-center">
                            <span class="badge bg-info fs-6">Còn lại: <?= $sessions_remaining ?> buổi</span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success text-center">
                            Hợp đồng đã hoàn thành đủ số buổi!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal báo cáo nhanh -->
<div class="modal fade" id="quickReportModal" tabindex="-1" aria-labelledby="quickReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickReportModalLabel">📊 Báo cáo lịch dạy hôm nay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reportLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải báo cáo...</p>
                </div>
                <div id="reportContent" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>Nội dung báo cáo:</h6>
                        <button class="btn btn-success btn-sm" onclick="copyReport()">📋 Copy</button>
                    </div>
                    <div class="report-text" style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; white-space: pre-line; max-height: 400px; overflow-y: auto;"></div>
                </div>
                <div id="reportError" style="display: none;" class="alert alert-danger">
                    <h6>❌ Lỗi tải báo cáo</h6>
                    <p id="errorMessage"></p>
                    <button class="btn btn-primary btn-sm" onclick="loadReport()">🔄 Thử lại</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <a href="schedule_report.php?date=<?= date('Y-m-d') ?>" class="btn btn-primary">📋 Xem báo cáo chi tiết</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let reportData = null;
    
    document.getElementById('select_all_sessions').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.session-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });
    
    function showQuickReport() {
        const modal = new bootstrap.Modal(document.getElementById('quickReportModal'));
        modal.show();
        loadReport();
    }
    
    function loadReport() {
        const loading = document.getElementById('reportLoading');
        const content = document.getElementById('reportContent');
        const error = document.getElementById('reportError');
        
        // Hiển thị loading
        loading.style.display = 'block';
        content.style.display = 'none';
        error.style.display = 'none';
        
        // Gọi API
        fetch(`api/coach_report.php?date=<?= date('Y-m-d') ?>`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text(); // Lấy text trước để kiểm tra
            })
            .then(text => {
                console.log('Raw response:', text);
                
                // Kiểm tra xem có phải JSON không
                try {
                    const data = JSON.parse(text);
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    reportData = data;
                    displayReport(data);
                    
                    // Ẩn loading, hiển thị báo cáo
                    loading.style.display = 'none';
                    content.style.display = 'block';
                } catch (parseError) {
                    throw new Error('Invalid JSON response: ' + parseError.message + '\nResponse: ' + text.substring(0, 200));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                
                // Ẩn loading, hiển thị lỗi
                loading.style.display = 'none';
                error.style.display = 'block';
                document.getElementById('errorMessage').textContent = err.message;
            });
    }
    
    function displayReport(data) {
        const reportText = document.querySelector('.report-text');
        let displayText = data.reportText;
        
        // Thêm thông tin debug nếu có
        if (data.debug) {
            displayText += "\n\n--- DEBUG INFO ---\n";
            displayText += "Buổi tập hôm nay: " + data.debug.today_sessions_count + "\n";
            displayText += "Buổi tập ngày mai: " + data.debug.next_sessions_count + "\n";
            displayText += "Buổi đã hoàn thành: " + data.debug.completed_sessions_count + "\n";
            displayText += "Coach ID: " + data.debug.coach_id + "\n";
            displayText += "Ngày hiện tại: " + data.debug.current_date_str + "\n";
            
            if (data.debug.today_sessions && data.debug.today_sessions.length > 0) {
                displayText += "\nChi tiết buổi tập hôm nay:\n";
                data.debug.today_sessions.forEach((session, index) => {
                    displayText += `${index + 1}. ${session.time} ${session.client} (${session.status})\n`;
                });
            }
        }
        
        reportText.textContent = displayText;
    }
    
    function copyReport() {
        if (!reportData) {
            alert('Không có dữ liệu báo cáo để copy!');
            return;
        }
        
        const reportText = reportData.reportText;
        
        navigator.clipboard.writeText(reportText).then(function() {
            // Hiển thị toast thông báo
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ✅ Đã sao chép nội dung báo cáo!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Tự động xóa toast sau 3 giây
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }, function(err) {
            alert('Lỗi khi copy: ' + err);
        });
    }
</script>
</body>
</html>