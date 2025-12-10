<?php
// 1. BẢO VỆ & GIAO DIỆN
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// --- XỬ LÝ LỌC & TÌM KIẾM ---

// Mặc định
$search = "";
$filter_date = ""; 
$filter_month = "";
$filter_shift = ""; // Biến lọc ca
$sort_by = "orders.order_date";
$order_dir = "DESC";

$sql = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name, users.shift 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE 1=1";

// 1. LỌC THEO TỪ KHÓA (ID hoặc Tên NV)
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $s = mysqli_real_escape_string($conn, $search);
        $sql .= " AND (orders.id LIKE '%$s%' OR users.full_name LIKE '%$s%')";
    }
}

// 2. LỌC THEO CA LÀM VIỆC
if (isset($_GET['shift']) && !empty($_GET['shift']) && $_GET['shift'] != 'all') {
    $filter_shift = $_GET['shift'];
    $sql .= " AND users.shift = '$filter_shift'";
}

// 3. LỌC THEO THỜI GIAN (Ưu tiên Ngày -> Tháng)
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $filter_date = $_GET['date'];
    $sql .= " AND DATE(orders.order_date) = '$filter_date'";
} 
elseif (isset($_GET['month']) && !empty($_GET['month'])) {
    $filter_month = $_GET['month'];
    $sql .= " AND DATE_FORMAT(orders.order_date, '%Y-%m') = '$filter_month'";
}

// 4. SẮP XẾP
$allowed_sort = ['id' => 'orders.id', 'date' => 'orders.order_date', 'amount' => 'orders.total_amount'];
if (isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $allowed_sort)) $sort_by = $allowed_sort[$_GET['sort_by']];
if (isset($_GET['order_dir'])) $order_dir = (strtoupper($_GET['order_dir']) == 'ASC') ? 'ASC' : 'DESC';

$sql .= " ORDER BY $sort_by $order_dir";

$result = mysqli_query($conn, $sql);

// --- TÍNH TỔNG TIỀN CHO DANH SÁCH HIỆN TẠI ---
$current_total = 0;
$data_rows = []; 
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_rows[] = $row;
        $current_total += $row['total_amount'];
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- GHI ĐÈ MÀU CHUẨN CYAN/TEAL (#17A2B8) CHO TRANG HÓA ĐƠN --- */
    
    /* Màu focus cho input/select */
    .form-control:focus {
        border-color: #17A2B8; 
        box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.2); /* Sử dụng RGBA của #17A2B8 */
    }
    
    /* Nút Lọc (Filter) */
    .btn-filter {
        background: #17A2B8; 
    }
    .btn-filter:hover {
        background: #148f9f; /* Darker shade of #17A2B8 */
    }

    /* Badge tổng tiền */
    .total-badge {
        background: #e6f5f7; /* Rất nhạt của #17A2B8 */
        border: 1px dashed #17A2B8;
        color: #17A2B8;
    }
    .total-badge span {
        color: #17A2B8;
    }

    /* * CHỈ GIỮ LẠI CÁC STYLE ĐẶC TRƯNG CHO CA LÀM VIỆC (UNIQUE) */
    .shift-badge {
        font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: bold; text-transform: uppercase;
        margin-left: 5px; display: inline-block;
    }
    .shift-sang { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .shift-chieu { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .shift-toi { background: #e0cffc; color: #59359a; border: 1px solid #cff4fc; }
</style>

    <h2 class="title-order">Quản lý Hóa đơn</h2>

    <div class="filter-card">
        <form method="GET" action="" class="filter-row">
            
            <div class="filter-group">
                <label>Mã HĐ / Tên NV</label>
                <input type="text" name="search" class="form-control" placeholder="Mã HĐ / Tên NV..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group">
                <label>Ca làm việc</label>
                <select name="shift" class="form-control">
                    <option value="all" <?php if($filter_shift == 'all') echo 'selected'; ?>>Tất cả</option>
                    <option value="sang" <?php if($filter_shift == 'sang') echo 'selected'; ?>>Sáng</option>
                    <option value="chieu" <?php if($filter_shift == 'chieu') echo 'selected'; ?>>Chiều</option>
                    <option value="toi" <?php if($filter_shift == 'toi') echo 'selected'; ?>>Tối</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Tháng</label>
                <input type="month" name="month" class="form-control" value="<?php echo $filter_month; ?>">
            </div>

            <div class="filter-group">
                <label>Ngày cụ thể</label>
                <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
            </div>

            <div class="filter-group">
                <label>Sắp xếp theo</label>
                <select name="sort_by" class="form-control">
                    <option value="date" <?php if($sort_by == 'orders.order_date') echo 'selected'; ?>>Ngày tạo</option>
                    <option value="amount" <?php if($sort_by == 'orders.total_amount') echo 'selected'; ?>>Tổng tiền</option>
                </select>
            </div>
            
            <div class="filter-group action-group" style="flex-direction: row; align-items: flex-end;">
                <select name="order_dir" class="form-control" style="min-width: 100px; margin-right: 5px;">
                    <option value="DESC" <?php if($order_dir == 'DESC') echo 'selected'; ?>>Mới nhất</option>
                    <option value="ASC" <?php if($order_dir == 'ASC') echo 'selected'; ?>>Cũ nhất</option>
                </select>

                <button type="submit" class="btn-filter">🔍 Lọc</button>
                
                <?php if($search || $filter_date || $filter_month || $filter_shift): ?>
                    <a href="order_list.php" class="btn-reset" title="Đặt lại">↺</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if(!empty($data_rows)): ?>
            <div class="total-badge">
                Tổng: <span><?php echo number_format($current_total); ?> ₫</span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if($filter_shift || $filter_date || $filter_month): ?>
        <div style="margin-bottom: 10px; font-size: 13px; color: #666; font-style: italic; padding-left: 5px;">
            Đang xem: 
            <?php if($filter_shift) echo "Ca <strong>" . ucfirst($filter_shift) . "</strong>"; ?>
            <?php if($filter_shift && ($filter_date || $filter_month)) echo " - "; ?>
            <?php 
                if($filter_date) echo "Ngày <strong>".date('d/m/Y', strtotime($filter_date))."</strong>";
                elseif($filter_month) echo "Tháng <strong>".date('m/Y', strtotime($filter_month))."</strong>";
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($data_rows)): ?>
        <table>
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Ngày tạo</th>
                    <th>Nhân viên / Ca</th>
                    <th>Tổng tiền</th>
                    <th style="text-align: center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_rows as $row): ?>
                <tr>
                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($row['order_date'])); ?><br>
                        <small style="color:#888"><?php echo date('H:i', strtotime($row['order_date'])); ?></small>
                    </td> 
                    <td>
                        <?php echo htmlspecialchars($row['full_name']); ?>
                        <?php 
                            if($row['shift'] == 'sang') echo '<span class="shift-badge shift-sang">Sáng</span>';
                            elseif($row['shift'] == 'chieu') echo '<span class="shift-badge shift-chieu">Chiều</span>';
                            elseif($row['shift'] == 'toi') echo '<span class="shift-badge shift-toi">Tối</span>';
                        ?>
                    </td>
                    <td style="color: #28a745; font-weight: bold;"><?php echo number_format($row['total_amount']); ?> ₫</td>
                    <td style="text-align: center;">
                        <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view">
                            📄 Chi tiết
                        </a>
                        
                        <a href="order_delete.php?id=<?php echo $row['id']; ?>" 
                           onclick="confirmDeleteOrder(event, this.href, '<?php echo $row['id']; ?>')"
                           class="btn-action btn-delete">
                           🗑 Xóa
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; background:white; border-radius:8px; color:#777;">
            Không tìm thấy dữ liệu hóa đơn phù hợp với bộ lọc.
        </div>
    <?php endif; ?>
        
<?php 
echo '</div>'; // Đóng admin-wrapper
echo '</div>'; // Đóng main-with-sidebar
?>

<script>
    function confirmDeleteOrder(event, deleteUrl, orderId) {
        event.preventDefault(); 

        // Bước 1: Hỏi xác nhận lần đầu
        Swal.fire({
            title: 'Xóa hóa đơn #' + orderId + '?',
            text: "Bạn có chắc muốn xóa hóa đơn này không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đúng, xóa nó',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Bước 2: Cảnh báo nghiêm trọng
                Swal.fire({
                    title: 'CẢNH BÁO QUAN TRỌNG!',
                    text: "Việc xóa hóa đơn sẽ làm sai lệch doanh thu và thống kê. Dữ liệu KHÔNG THỂ khôi phục. Bạn chắc chắn chứ?",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'TÔI HIỂU, XÓA NGAY',
                    cancelButtonText: 'Thôi, đừng xóa'
                }).then((result2) => {
                    if (result2.isConfirmed) {
                        window.location.href = deleteUrl; 
                    }
                });
            }
        });
    }
</script>

<?php require '../includes/footer.php'; ?>