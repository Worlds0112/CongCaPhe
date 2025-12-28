<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập và quyền hạn
require '../includes/header.php';     // Gọi phần đầu trang (HTML head, CSS)
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
$search = "";
$filter_shift = ""; 
$filter_day   = ""; 
$filter_month = ""; 
$filter_year  = date('Y'); // Mặc định là năm nay
$sort_by = "orders.order_date"; // Mặc định sắp xếp theo ngày
$order_dir = "DESC"; // Mặc định giảm dần (Mới nhất lên đầu)

if (isset($_GET['search'])) $search = trim($_GET['search']);
if (isset($_GET['shift']))  $filter_shift = $_GET['shift'];
if (isset($_GET['day']))    $filter_day = $_GET['day'];
if (isset($_GET['month']))  $filter_month = $_GET['month'];
if (isset($_GET['year']))   $filter_year = $_GET['year'];
if ($filter_year == 'all') $filter_year = '';

// =================================================================
// 3. XÂY DỰNG CÂU TRUY VẤN (QUERY BUILDER)
// =================================================================
$where_clause = " WHERE 1=1"; // Điều kiện mặc định luôn đúng

// A. Tìm kiếm theo từ khóa
if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_clause .= " AND (orders.id LIKE '%$s%' OR users.full_name LIKE '%$s%')";
}
// B. Lọc theo Ca làm việc
if (!empty($filter_shift) && $filter_shift != 'all') {
    $where_clause .= " AND users.shift = '$filter_shift'";
}
// C. Lọc theo Thời gian (Ngày/Tháng/Năm)
if (!empty($filter_day))   $where_clause .= " AND DAY(orders.order_date) = '$filter_day'";
if (!empty($filter_month)) $where_clause .= " AND MONTH(orders.order_date) = '$filter_month'";
if (!empty($filter_year))  $where_clause .= " AND YEAR(orders.order_date) = '$filter_year'";

// =================================================================
// 4. QUERY 1: TÍNH TỔNG SỐ LƯỢNG (ĐỂ PHÂN TRANG)
// =================================================================
$sql_count = "SELECT COUNT(DISTINCT orders.id) as total 
              FROM orders 
              JOIN users ON orders.user_id = users.id 
              $where_clause";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

// =================================================================
// 5. QUERY 2: TÍNH TỔNG QUÁT (DOANH THU, VỐN, LỢI NHUẬN)
// =================================================================
// Mục đích: Hiển thị 3 ô thống kê trên đầu trang
$sql_sum_all = "SELECT 
                    SUM(temp_table.total_amount) as grand_revenue,
                    SUM(temp_table.calculated_cost) as grand_cost
                FROM (
                    SELECT 
                        orders.total_amount,
                        COALESCE(SUM(order_details.quantity * products.original_price), 0) as calculated_cost
                    FROM orders 
                    JOIN users ON orders.user_id = users.id 
                    LEFT JOIN order_details ON orders.id = order_details.order_id
                    LEFT JOIN products ON order_details.product_id = products.id
                    $where_clause 
                    GROUP BY orders.id
                ) as temp_table";

$result_sum_all = mysqli_query($conn, $sql_sum_all);
$row_sum_all = mysqli_fetch_assoc($result_sum_all);

$sum_revenue = $row_sum_all['grand_revenue'] ?? 0;
$sum_cost    = $row_sum_all['grand_cost'] ?? 0;
$sum_profit  = $sum_revenue - $sum_cost;

// =================================================================
// 6. QUERY 3: LẤY DỮ LIỆU HIỂN THỊ (CÓ LIMIT & SORT)
// =================================================================
$sql = "SELECT 
            orders.id, 
            orders.order_date, 
            orders.total_amount, 
            users.full_name, 
            users.shift,
            COALESCE(SUM(order_details.quantity * products.original_price), 0) as calculated_cost
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        LEFT JOIN order_details ON orders.id = order_details.order_id
        LEFT JOIN products ON order_details.product_id = products.id
        $where_clause"; 

$sql .= " GROUP BY orders.id"; 

// Xử lý sắp xếp
$allowed_sort = [
    'id' => 'orders.id', 
    'date' => 'orders.order_date', 
    'amount' => 'orders.total_amount', 
    'profit' => '(orders.total_amount - COALESCE(SUM(order_details.quantity * products.original_price), 0))' 
];
if (isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $allowed_sort)) {
    $sort_by = $allowed_sort[$_GET['sort_by']];
}
if (isset($_GET['order_dir'])) {
    $order_dir = (strtoupper($_GET['order_dir']) == 'ASC') ? 'ASC' : 'DESC';
}
$sql .= " ORDER BY $sort_by $order_dir";
$sql .= " LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);

// Lưu dữ liệu vào mảng để dễ xử lý hiển thị
$data_rows = []; 
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $revenue = $row['total_amount'];
        $cost    = $row['calculated_cost']; 
        $profit  = $revenue - $cost;
        
        $row['profit'] = $profit;
        $row['cost']   = $cost;
        $data_rows[] = $row;
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="header-row">
        <h2 class="title-order" style="margin: 0;">Quản lý Dòng tiền & Hóa đơn</h2>
        
        <a href="export_orders_list_excel.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn-excel">
            📥 Xuất Excel Báo Cáo
        </a>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card card-revenue">
            <h4>Tổng Doanh thu (Tất cả)</h4>
            <div class="value"><?php echo number_format($sum_revenue); ?> ₫</div>
        </div>
        <div class="stat-card card-cost">
            <h4>Tổng Tiền Vốn (Tất cả)</h4>
            <div class="value"><?php echo number_format($sum_cost); ?> ₫</div>
        </div>
        <div class="stat-card card-profit">
            <h4>Tổng Lợi Nhuận (Tất cả)</h4>
            <div class="value"><?php echo number_format($sum_profit); ?> ₫</div>
        </div>
    </div>

    <div class="filter-card">
       <form method="GET" action="" class="filter-row">
           <div class="filter-group">
               <label>Mã HĐ / Tên NV</label>
               <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
           </div>
           
           <div class="filter-group">
               <label>Ca</label>
               <select name="shift" class="form-control">
                   <option value="all">Tất cả</option>
                   <option value="sang" <?php if($filter_shift == 'sang') echo 'selected'; ?>>Sáng</option>
                   <option value="chieu" <?php if($filter_shift == 'chieu') echo 'selected'; ?>>Chiều</option>
                   <option value="toi" <?php if($filter_shift == 'toi') echo 'selected'; ?>>Tối</option>
               </select>
           </div>
           
           <div class="filter-group">
               <label>Ngày</label>
               <select name="day" class="form-control">
                   <option value="">--</option>
                   <?php for($d=1; $d<=31; $d++): ?><option value="<?php echo $d; ?>" <?php if($filter_day == $d) echo 'selected'; ?>><?php echo $d; ?></option><?php endfor; ?>
               </select>
           </div>
           
           <div class="filter-group">
               <label>Tháng</label>
               <select name="month" class="form-control">
                   <option value="">Tất cả</option>
                   <?php for($m=1; $m<=12; $m++): ?><option value="<?php echo $m; ?>" <?php if($filter_month == $m) echo 'selected'; ?>>Tháng <?php echo $m; ?></option><?php endfor; ?>
               </select>
           </div>
           
           <div class="filter-group">
               <label>Năm</label>
               <select name="year" class="form-control">
                   <option value="all">Tất cả</option>
                   <?php $c=date('Y'); for($y=$c; $y>=$c-5; $y--): ?><option value="<?php echo $y; ?>" <?php if($filter_year == $y) echo 'selected'; ?>><?php echo $y; ?></option><?php endfor; ?>
               </select>
           </div>
           
           <div class="filter-group">
               <label>Sắp xếp</label>
               <select name="sort_by" class="form-control">
                   <option value="date" <?php if($sort_by == 'orders.order_date') echo 'selected'; ?>>Ngày tạo</option>
                   <option value="amount" <?php if($sort_by == 'orders.total_amount') echo 'selected'; ?>>Doanh thu</option>
                   <option value="profit" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'profit') echo 'selected'; ?>>Lợi nhuận</option>
               </select>
           </div>
           
           <div class="filter-group action-group" style="flex-direction: row; align-items: flex-end;">
               <button type="submit" class="btn-filter">🔍 Lọc</button>
               <?php if($search || $filter_shift || $filter_day || $filter_month || ($filter_year != date('Y'))): ?>
                   <a href="order_list.php" class="btn-reset" title="Đặt lại">↺</a>
               <?php endif; ?>
           </div>
       </form>
    </div>
    
    <div style="margin-bottom: 15px; font-size: 13px; color: #666; font-style: italic; display:flex; justify-content:space-between;">
        <span>Đang xem trang: <strong><?php echo $page; ?>/<?php echo $total_pages; ?></strong></span>
        <span>Tổng: <strong><?php echo $total_records; ?></strong> hóa đơn</span>
    </div>

    <?php if (!empty($data_rows)): ?>
        <table>
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Thời gian</th>
                    <th>Nhân viên / Ca</th>
                    <th class="text-right">Doanh thu</th>
                    <th class="text-right">Lợi nhuận</th> 
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_rows as $row): ?>
                <tr>
                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($row['order_date'])); ?><br>
                        <span class="text-muted"><?php echo date('H:i', strtotime($row['order_date'])); ?></span>
                    </td> 
                    <td>
                        <?php echo htmlspecialchars($row['full_name']); ?>
                        <?php 
                            if($row['shift'] == 'sang') echo '<span class="shift-badge shift-sang">Sáng</span>';
                            elseif($row['shift'] == 'chieu') echo '<span class="shift-badge shift-chieu">Chiều</span>';
                            elseif($row['shift'] == 'toi') echo '<span class="shift-badge shift-toi">Tối</span>';
                        ?>
                    </td>
                    <td class="text-right text-green font-bold">
                        <?php echo number_format($row['total_amount']); ?> ₫
                    </td>
                    <td class="text-right text-blue font-bold">
                        <?php echo number_format($row['profit']); ?> ₫
                    </td>
                    <td class="text-center">
                        <div class="action-buttons" style="justify-content: center;">
                            <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view">📄</a>
                            <a href="order_delete.php?id=<?php echo $row['id']; ?>" onclick="confirmDeleteOrder(event, this.href, '<?php echo $row['id']; ?>')" class="btn-action btn-delete">🗑</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            function get_url($p) {
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
        <div style="text-align:center; padding: 40px; background:white; border-radius:8px; color:#777;">
            Không tìm thấy dữ liệu hóa đơn phù hợp.
        </div>
    <?php endif; ?>
        
<?php 
// Đóng các thẻ div wrapper
echo '</div>'; 
echo '</div>'; 
?>

<script>
    function confirmDeleteOrder(event, deleteUrl, orderId) {
        event.preventDefault(); 
        Swal.fire({
            title: 'Xóa hóa đơn #' + orderId + '?',
            text: "Dữ liệu sẽ mất vĩnh viễn!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = deleteUrl; }
        });
    }
</script>