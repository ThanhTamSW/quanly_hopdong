<?php
$page_title = 'Import Hợp đồng';
$requires_login = true;
include 'includes/header.php';

$message = $_SESSION['flash_message'] ?? null;
if ($message) unset($_SESSION['flash_message']);
?>
<div class="card shadow-sm mx-auto" style="max-width: 700px;">
    <div class="card-header"><h4>⬆️ Tải lên File Hợp đồng (Excel)</h4></div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message['type']) ?>"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
        <?php endif; ?>
        <form action="actions/handle_import.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3"><label for="import_file" class="form-label">Chọn file (.xlsx)</label><input type="file" name="import_file" id="import_file" class="form-control" accept=".xlsx" required></div>
            <div class="mb-3"><label for="sheet_name" class="form-label">Tên Sheet cần Import</label><input type="text" name="sheet_name" id="sheet_name" class="form-control" placeholder="Ví dụ: Sheet1, Com day..." required></div>
            <div class="alert alert-info"><p class="mb-1">Sheet được chọn phải có các cột với tiêu đề sau (thứ tự bất kỳ):</p><code>HỌ VÀ TÊN, SĐT, Tên HLV, Ngày ĐK, Gói SP, Số buổi, Tổng thu</code></div>
            <button type="submit" class="btn btn-primary w-100">Bắt đầu Import</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>