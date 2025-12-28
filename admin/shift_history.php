<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập & quyền hạn
require '../includes/header.php';     // Gọi giao diện Header & CSS
require '../includes/admin_sidebar.php'; // Gọi thanh Menu bên trái

echo '<div class="main-with-sidebar">'; // Mở khung nội dung chính
echo '<div class="admin-wrapper" style="margin: 0; max-width: none; flex: 1;">';

// =================================================================
// 2. CẤU HÌNH PHÂN TRANG & NHẬN DỮ LIỆU LỌC
// =================================================================
$limit = 10; // Số dòng hiển thị trên 1 trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; // Tính vị trí bắt đầu lấy dữ liệu

// Lấy tham số tìm kiếm từ URL (GET)
$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : '';
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";
$filter_month = isset($_GET['month']) ? $_GET['month'] : "";
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y'); // Mặc định là năm nay
if ($filter_year == 'all') $filter_year = '';

// =================================================================
// 3. XÂY DỰNG CÂU TRUY VẤN (QUERY BUILDER)
// =================================================================
$where_sql = "WHERE 1=1"; // Điều kiện mặc định luôn đúng để dễ nối chuỗi

// Nếu có chọn Ca
if (!empty($filter_shift) && $filter_shift != 'all') {
    $where_sql .= " AND r.shift_code = '$filter_shift'";
}
// Nếu có chọn Ngày
if (!empty($filter_day)) {
    $where_sql .= " AND DAY(r.report_date) = '$filter_day'";
}
// Nếu có chọn Tháng
if (!empty($filter_month)) {
    $where_sql .= " AND MONTH(r.report_date) = '$filter_month'";
}
// Nếu có chọn Năm
if (!empty($filter_year)) {
    $where_sql .= " AND YEAR(r.report_date) = '$filter_year'";
}

// =================================================================
// 4. QUERY 1: TÍNH TỔNG QUÁT (DÙNG CHO DASHBOARD & PHÂN TRANG)
// =================================================================
// Mục đích: Tính tổng doanh thu hệ thống, tiền thực tế và chênh lệch 
// của TOÀN BỘ dữ liệu tìm được (không bị cắt bởi LIMIT phân trang)
$sql_sum = "SELECT 
                COUNT(*) as total_reports,
                SUM(r.system_revenue) as sum_system,
                SUM(r.real_cash) as sum_real,
                SUM(r.difference) as sum_diff
            FROM shift_reports r 
            $where_sql";

$result_sum = mysqli_query($conn, $sql_sum);
$row_sum = mysqli_fetch_assoc($result_sum);

// Gán biến để hiển thị lên Dashboard
$total_records = $row_sum['total_reports'];
$sum_system    = $row_sum['sum_system'] ?? 0;
$sum_real      = $row_sum['sum_real'] ?? 0;
$sum_diff      = $row_sum['sum_diff'] ?? 0;

// Tính tổng số trang
$total_pages = ceil($total_records / $limit);

// =================================================================
// 5. QUERY 2: LẤY DỮ LIỆU CHI TIẾT (HIỂN THỊ BẢNG)
// =================================================================
// Mục đích: Lấy danh sách báo cáo, có JOIN với bảng users để lấy tên nhân viên
// Sắp xếp mới nhất lên đầu, và chỉ lấy 10 dòng (LIMIT)
$sql_data = "SELECT r.*, u.full_name, u.username 
             FROM shift_reports r 
             JOIN users u ON r.user_id = u.id 
             $where_sql 
             ORDER BY r.created_at DESC 
             LIMIT $offset, $limit";
$result_data = mysqli_query($conn, $sql_data);
?>

<div class="header-row">
    <h2 class="title-history" style="margin:0;">Lịch sử Kết Ca & Bàn Giao</h2>

    <a href="export_shift_excel.php?<?php echo http_build_query($_GET); ?>" class="btn-excel" target="_blank">
        📥 Xuất Báo Cáo Excel
    </a>
</div>

<div class="dashboard-stats">
    <div class="stat-card card-system">
        <h4>Doanh thu Máy (Hệ thống)</h4>
        <div class="value"><?php echo number_format($sum_system); ?> ₫</div>
    </div>

    <div class="stat-card card-real">
        <h4>Tiền Mặt Thực Tế (Két)</h4>
        <div class="value"><?php echo number_format($sum_real); ?> ₫</div>
    </div>

    <?php
    // Logic xử lý màu sắc cho ô Chênh lệch
    $diff_class = "";
    $diff_display = number_format($sum_diff);

    if ($sum_diff > 0) {
        $diff_class = "diff-pos"; // Class xanh (Thừa tiền)
        $diff_display = "+" . $diff_display;
    } elseif ($sum_diff < 0) {
        $diff_class = "diff-neg"; // Class đỏ (Thiếu tiền)
    }
    ?>
    <div class="stat-card card-diff <?php echo $diff_class; ?>">
        <h4>Tổng Chênh Lệch</h4>
        <div class="value"><?php echo $diff_display; ?> ₫</div>
    </div>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row">

        <div class="filter-group" style="width: 120px;">
            <label>Chọn Ca</label>
            <select name="shift" class="form-control">
                <option value="all">Tất cả</option>
                <option value="sang" <?php if ($filter_shift == 'sang') echo 'selected'; ?>>Sáng</option>
                <option value="chieu" <?php if ($filter_shift == 'chieu') echo 'selected'; ?>>Chiều</option>
                <option value="toi" <?php if ($filter_shift == 'toi') echo 'selected'; ?>>Tối</option>
            </select>
        </div>

        <div class="filter-group" style="width: 80px;">
            <label>Ngày</label>
            <select name="day" class="form-control">
                <option value="">--</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?php echo $d; ?>" <?php if ($filter_day == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filter-group" style="width: 100px;">
            <label>Tháng</label>
            <select name="month" class="form-control">
                <option value="">Tất cả</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php if ($filter_month == $m) echo 'selected'; ?>>Tháng <?php echo $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filter-group" style="width: 100px;">
            <label>Năm</label>
            <select name="year" class="form-control">
                <option value="all">Tất cả</option>
                <?php $c = date('Y');
                for ($y = $c; $y >= $c - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php if ($filter_year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filter-group action-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn-filter">Xem Báo Cáo</button>
            <?php if ($filter_shift || $filter_day || $filter_month || ($filter_year != date('Y'))): ?>
                <a href="shift_history.php" class="btn-reset" title="Đặt lại">↺</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div style="margin-bottom: 15px; display:flex; justify-content:space-between;" class="text-muted">
    <span>Đang xem trang: <strong><?php echo $page; ?>/<?php echo $total_pages; ?></strong></span>
    <span>Tổng: <strong><?php echo $total_records; ?></strong> phiếu báo cáo</span>
</div>

<?php if ($result_data && mysqli_num_rows($result_data) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Nhân viên</th>
                <th class="text-right">Doanh thu (Máy)</th>
                <th class="text-right">Thực tế (Két)</th>
                <th class="text-right">Chênh lệch</th>
                <th style="width: 250px;">Ghi chú</th>
                <th width="50" class="text-center">Xóa</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result_data)): ?>
                <tr>
                    <td>
                        <?php
                        if ($row['shift_code'] == 'sang') echo '<span class="shift-badge shift-sang">Ca Sáng</span>';
                        elseif ($row['shift_code'] == 'chieu') echo '<span class="shift-badge shift-chieu">Ca Chiều</span>';
                        else echo '<span class="shift-badge shift-toi">Ca Tối</span>';
                        ?>
                        <div class="font-bold" style="color: #333; margin-top: 4px;">
                            <?php echo date('d/m/Y', strtotime($row['report_date'])); ?>
                        </div>
                        <span class="text-muted"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
                    </td>

                    <td>
                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                        <span class="text-muted">@<?php echo $row['username']; ?></span>
                    </td>

                    <td class="text-right text-primary font-bold">
                        <?php echo number_format($row['system_revenue']); ?> ₫
                    </td>

                    <td class="text-right font-bold" style="color:#333;">
                        <?php echo number_format($row['real_cash']); ?> ₫
                    </td>

                    <td class="text-right">
                        <?php
                        if ($row['difference'] == 0) {
                            echo '<span class="diff-ok">✓ Khớp</span>';
                        } elseif ($row['difference'] > 0) {
                            echo '<span class="diff-pos">+' . number_format($row['difference']) . '</span>';
                        } else {
                            echo '<span class="diff-neg">' . number_format($row['difference']) . '</span>';
                        }
                        ?>
                    </td>


                    <td style="font-size: 13px;">
                        <?php if (!empty($row['inventory_notes'])): ?>
                            <div style="margin-bottom: 5px;" class="text-purple">
                                <strong>Kho:</strong> <?php echo nl2br(htmlspecialchars($row['inventory_notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($row['notes'])): ?>
                            <div style="font-style: italic;" class="text-muted">
                                <strong>Chung:</strong> <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($row['inventory_notes']) && empty($row['notes'])) echo '<span class="text-muted">-</span>'; ?>
                    </td>
                    <td class="text-center">
                        <a href="shift_delete.php?id=<?php echo $row['id']; ?>"
                            onclick="return confirmDeleteShift(event, this.href, '<?php echo date('d/m', strtotime($row['report_date'])); ?>')"
                            class="btn-action-delete" title="Xóa phiếu này">
                            🗑
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // Hàm tạo URL giữ lại các tham số lọc hiện tại
            function get_url($p)
            {
                $params = $_GET;
                $params['page'] = $p;
                return '?' . http_build_query($params);
            }
            ?>
            <?php if ($page > 1): ?>
                <a href="<?php echo get_url($page - 1); ?>">«</a>
            <?php else: ?>
                <span class="disabled">«</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="<?php echo get_url($i); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="disabled">...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?php echo get_url($page + 1); ?>">»</a>
            <?php else: ?>
                <span class="disabled">»</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div style="text-align:center; padding: 40px; background: white; border-radius: 8px; color: #777;">
        Chưa có dữ liệu báo cáo nào phù hợp.
    </div>
<?php endif; ?>

<script>
    function confirmDeleteShift(event, url, date) {
        event.preventDefault(); // Chặn chuyển trang ngay

        Swal.fire({
            title: 'Xóa báo cáo ngày ' + date + '?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

<?php
// Giải phóng bộ nhớ và đóng kết nối
if ($result_data) mysqli_free_result($result_data);
disconnect_db();
echo '</div></div>';
?>