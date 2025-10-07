<?php
$page_title = 'Quản lý Hợp đồng';
$requires_login = true;
include 'includes/header.php'; 
include 'includes/db.php';

$isCoach = ($_SESSION['role'] === 'coach');
$user_role = $_SESSION['role'];

// LẤY DANH SÁCH TẤT CẢ COACH ĐỂ TẠO TAB
$coaches_result = $conn->query("SELECT id, full_name FROM users WHERE role = 'coach' ORDER BY full_name");
$coaches_list = [];
while($coach = $coaches_result->fetch_assoc()) {
    $coaches_list[] = $coach;
}

// XÁC ĐỊNH TAB ĐANG ĐƯỢC CHỌN
$active_coach_id = isset($_GET['coach_id']) ? $_GET['coach_id'] : 'all';


// --- TÍNH TOÁN CÁC CHỈ SỐ DOANH THU ---
$params_revenue = [];
$types_revenue = '';
$where_clause_revenue = '';

if ($active_coach_id !== 'all') {
    $where_clause_revenue = "WHERE coach_id = ?";
    $params_revenue[] = intval($active_coach_id);
    $types_revenue .= 'i';
}

// Tính Doanh thu Tổng (của tất cả hợp đồng trong tab)
$sql_total = "SELECT SUM(final_price) as total FROM contracts " . $where_clause_revenue;
$stmt_total = $conn->prepare($sql_total);
if (!empty($params_revenue)) {
    $stmt_total->bind_param($types_revenue, ...$params_revenue);
}
$stmt_total->execute();
$overall_revenue = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

// Tính Doanh thu Tháng hiện tại (dựa trên ngày bắt đầu hợp đồng)
$first_day_month = date('Y-m-01');
$last_day_month = date('Y-m-t');
$sql_monthly = "SELECT SUM(final_price) as total FROM contracts " . (empty($where_clause_revenue) ? "WHERE" : $where_clause_revenue . " AND") . " start_date BETWEEN ? AND ?";
$params_monthly = $params_revenue;
array_push($params_monthly, $first_day_month, $last_day_month);
$types_monthly = $types_revenue . 'ss';

$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param($types_monthly, ...$params_monthly);
$stmt_monthly->execute();
$monthly_revenue = $stmt_monthly->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_monthly->close();


// --- XÂY DỰNG CÂU LỆNH SQL ĐỂ HIỂN THỊ DANH SÁCH ---
$sql_where_clause = '';
$params = [];
$types = '';

if ($active_coach_id !== 'all') {
    $sql_where_clause = "WHERE c.coach_id = ?";
    $params[] = intval($active_coach_id);
    $types .= 'i';
}
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search_term)) {
    $like_term = "%" . $search_term . "%";
    $search_clause = "(client.full_name LIKE ? OR client.phone_number LIKE ? OR coach.full_name LIKE ?)";
    if (empty($sql_where_clause)) {
        $sql_where_clause = "WHERE " . $search_clause;
    } else {
        $sql_where_clause .= " AND " . $search_clause;
    }
    array_push($params, $like_term, $like_term, $like_term);
    $types .= 'sss';
}

$sql = "
    SELECT 
        c.id, c.start_date, c.package_name, c.total_sessions, c.total_price, c.discount_percentage, c.final_price,
        client.full_name AS client_name, client.phone_number AS client_phone,
        coach.full_name AS coach_name,
        (SELECT COUNT(id) FROM training_sessions WHERE contract_id = c.id AND status = 'completed') AS sessions_completed,
        DATE_ADD(c.start_date, INTERVAL CEIL(c.total_sessions / 3) WEEK) AS end_date_estimated
    FROM contracts c
    JOIN users client ON c.client_id = client.id
    JOIN users coach ON c.coach_id = coach.id
    $sql_where_clause
    ORDER BY c.start_date DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$contracts = $result->fetch_all(MYSQLI_ASSOC);
$search_query_param = !empty($search_term) ? '&search=' . urlencode($search_term) : '';
?>

<h2 class="mb-3">📑 Danh sách Hợp đồng</h2>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= ($active_coach_id == 'all') ? 'active' : '' ?>" href="?coach_id=all<?= $search_query_param ?>">Tất cả</a>
  </li>
  <?php foreach ($coaches_list as $coach): ?>
  <li class="nav-item">
    <a class="nav-link <?= ($active_coach_id == $coach['id']) ? 'active' : '' ?>" href="?coach_id=<?= $coach['id'] ?><?= $search_query_param ?>">
        <?= htmlspecialchars($coach['full_name']) ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-auto">
                  <a class="btn btn-success" href="add_contract.php">+ Thêm hợp đồng</a>
            </div>
            <div class="col-md">
                <form method="GET" action="index.php" class="d-flex">
                    <input type="hidden" name="coach_id" value="<?= htmlspecialchars($active_coach_id) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Tìm trong tab hiện tại..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit" class="btn btn-info ms-2">Tìm</button>
                </form>
            </div>
        </div>
        
        <hr>

        <div class="row text-center">
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <?php
                    $revenue_title = 'Doanh thu Tổng';
                    if ($active_coach_id !== 'all') {
                        foreach ($coaches_list as $coach) {
                            if ($coach['id'] == $active_coach_id) {
                                $revenue_title .= ' ('.htmlspecialchars($coach['full_name']).')';
                                break;
                            }
                        }
                    }
                ?>
                <h6 class="mb-0"><?= $revenue_title ?></h6>
                <p class="fs-5 text-success fw-bold mb-0"><?= number_format($overall_revenue, 0, ',', '.') ?>đ</p>
            </div>
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <h6 class="mb-0">Doanh thu Tháng <?= date('m/Y') ?></h6>
                <p class="fs-5 text-primary fw-bold mb-0"><?= number_format($monthly_revenue, 0, ',', '.') ?>đ</p>
            </div>
            <div class="col-lg-4 col-md-12">
                <?php if ($active_coach_id !== 'all'): ?>
                    <form action="actions/export_advanced.php" method="GET" class="d-inline-flex align-items-center justify-content-center justify-content-lg-end w-100">
                        <input type="hidden" name="coach_id" value="<?= $active_coach_id ?>">
                        <label for="month-select-<?= $active_coach_id ?>" class="me-2 text-nowrap">Xuất lương tháng:</label>
                        <input type="month" id="month-select-<?= $active_coach_id ?>" name="month" class="form-control form-control-sm me-2" style="width: auto;" value="<?= date('Y-m') ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">
                            Xuất Excel
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive shadow-sm">
  <table class="table table-bordered table-hover bg-white mb-0">
    <thead class="table-dark">
      <tr>
        <th>Ngày bắt đầu</th>
        <th>Ngày kết thúc</th>
        <th>Tên HLV</th>
        <th>Học viên</th>
        <th>SĐT Học viên</th>
        <th>Gói SP</th>
        <th>Tổng buổi</th>
        <th>Đã tập</th>
        <th>Còn lại</th>
        <th>Thành tiền</th>
        <th>Giảm giá</th>
        <th>Giá/buổi</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
    <?php if (count($contracts) > 0): ?>
        <?php foreach ($contracts as $row): 
            $sessions_remaining = $row['total_sessions'] - $row['sessions_completed'];
            $price_per_session = ($row['total_sessions'] > 0) ? ($row['final_price'] / $row['total_sessions']) : 0;
            $class = "";
            if ($sessions_remaining <= 0) $class = "done";
            else if ($sessions_remaining <= 2) $class = "warning";
        ?>
        <tr class="<?= $class ?>">
          <td><?= date("d/m/Y", strtotime($row['start_date'])) ?></td>
          <td><?= date("d/m/Y", strtotime($row['end_date_estimated'])) ?></td>
          <td><?= htmlspecialchars($row['coach_name']) ?></td>
          <td><?= htmlspecialchars($row['client_name']) ?></td>
          <td><?= htmlspecialchars($row['client_phone']) ?></td>
          <td><?= htmlspecialchars($row['package_name']) ?></td>
          <td><?= $row['total_sessions'] ?></td>
          <td><?= $row['sessions_completed'] ?></td>
          <td><strong class="text-danger"><?= $sessions_remaining ?></strong></td>
          <td><?= number_format($row['final_price'], 0, ',', '.') ?>đ</td>
          <td><?= ($row['discount_percentage'] > 0) ? $row['discount_percentage'] . '%' : '-' ?></td>
          <td><?= number_format($price_per_session, 0, ',', '.') ?>đ</td>
          <td>
            <div class="d-flex flex-wrap justify-content-center">
                <a class="btn btn-info btn-sm m-1" href="view_sessions.php?contract_id=<?= $row['id'] ?>" title="Quản lý lịch tập chi tiết">Quản lý lịch</a>
                <button class="btn btn-success btn-sm m-1" onclick="copyScheduleLink(<?= $row['id'] ?>)" title="Sao chép link lịch tập để gửi cho học viên">Lấy Link HV</button>
                <a class="btn btn-warning btn-sm m-1" href="edit_contract.php?id=<?= $row['id'] ?>" title="Sửa thông tin hợp đồng">Sửa</a>
                <a class="btn btn-danger btn-sm m-1" href="actions/delete_contract.php?id=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa hợp đồng này không?')" title="Xóa hợp đồng">Xóa</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="13" class="text-center">Không tìm thấy hợp đồng nào.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function copyScheduleLink(contractId) {
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const scheduleUrl = `${baseUrl}/public_schedule_view.php?contract_id=${contractId}`;
    navigator.clipboard.writeText(scheduleUrl).then(function() {
        alert('Đã sao chép link lịch tập của học viên!\n' + scheduleUrl);
    }, function(err) {
        alert('Lỗi khi sao chép link.');
    });
}
</script>

<?php 
include 'includes/footer.php'; 
?>