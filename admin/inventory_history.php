<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">'; 
echo '<div class="admin-wrapper" style="margin: 0; max-width: none; flex: 1;">';

// =================================================================
// 2. CẤU HÌNH PHÂN TRANG & LỌC DỮ LIỆU
// =================================================================
$limit = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; 

// Lấy tham số lọc
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type']) ? $_GET['type'] : 'all'; 

$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : ''; 
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";    
$filter_month = isset($_GET['month']) ? $_GET['month'] : ""; 
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y'); 
if ($filter_year == 'all') $filter_year = '';

// =================================================================
// 3. XÂY DỰNG ĐIỀU KIỆN WHERE
// =================================================================
$where_sql = "WHERE 1=1";

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_sql .= " AND p.name LIKE '%$s%'";
}
if ($type == 'in') {
    $where_sql .= " AND h.quantity > 0";
} elseif ($type == 'out') {
    $where_sql .= " AND h.quantity < 0";
}

// Lọc theo thời gian
if (!empty($filter_shift) && $filter_shift != 'all') {
    if ($filter_shift == 'sang')      $where_sql .= " AND HOUR(h.created_at) >= 6 AND HOUR(h.created_at) < 12";
    elseif ($filter_shift == 'chieu') $where_sql .= " AND HOUR(h.created_at) >= 12 AND HOUR(h.created_at) < 18";
    elseif ($filter_shift == 'toi')   $where_sql .= " AND HOUR(h.created_at) >= 18";
}
if (!empty($filter_day))   $where_sql .= " AND DAY(h.created_at) = '$filter_day'";
if (!empty($filter_month)) $where_sql .= " AND MONTH(h.created_at) = '$filter_month'";
if (!empty($filter_year))  $where_sql .= " AND YEAR(h.created_at) = '$filter_year'";

// =================================================================
// 4. QUERY 1: TÍNH TỔNG (DASHBOARD) - [FIX QUAN TRỌNG]
// =================================================================
// [FIX]: Thêm p.original_price vào câu SELECT để lấy giá vốn
$sql_stats = "SELECT h.quantity, h.import_price, p.original_price 
              FROM inventory_history h 
              JOIN products p ON h.product_id = p.id 
              $where_sql";
$result_stats = mysqli_query($conn, $sql_stats);

$total_records = 0;
$total_import_val = 0; 
$total_export_val = 0; 

if ($result_stats) {
    $total_records = mysqli_num_rows($result_stats);
    while ($row = mysqli_fetch_assoc($result_stats)) {
        $qty = (int)$row['quantity'];
        
        // --- [FIX] LOGIC TÍNH GIÁ ---
        // Lấy giá ghi trong lịch sử
        $hist_price = (float)$row['import_price'];
        // Lấy giá vốn hiện tại của sản phẩm
        $curr_cost  = (float)$row['original_price'];

        // Nếu giá lịch sử > 0 thì dùng nó (Phiếu nhập). 
        // Nếu bằng 0 (thường là phiếu xuất bán hàng) thì dùng Giá Vốn hiện tại.
        $price = ($hist_price > 0) ? $hist_price : $curr_cost;
        
        $val = abs($qty) * $price;
        
        if ($qty > 0) $total_import_val += $val;
        else $total_export_val += $val;
    }
}
$total_pages = ceil($total_records / $limit);

// =================================================================
// 5. QUERY 2: LẤY DỮ LIỆU HIỂN THỊ - [FIX QUAN TRỌNG]
// =================================================================
// [FIX]: Thêm p.original_price vào câu SELECT
$sql_data = "SELECT h.*, p.name as product_name, p.image, p.id as prod_id, p.original_price 
             FROM inventory_history h 
             JOIN products p ON h.product_id = p.id 
             $where_sql 
             ORDER BY h.created_at DESC 
             LIMIT $offset, $limit";
$result_data = mysqli_query($conn, $sql_data);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="header-row">
    <h2 class="title-order" style="margin: 0; border-left-color: #17a2b8;">Lịch sử Kho</h2>
    
    <div class="action-buttons">
        <a href="../excel/export_inventory_excel.php?<?php echo http_build_query($_GET); ?>" class="btn-excel" target="_blank">
            📥 Xuất Excel
        </a>
        
        <a href="inventory_import.php" class="btn-add" style="margin-bottom: 0;">
            + Nhập Hàng
        </a>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-card card-import">
        <h4>Vốn Bỏ Ra (Nhập)</h4>
        <div class="value"><?php echo number_format($total_import_val); ?> ₫</div>
    </div>
    <div class="stat-card card-export">
        <h4>Giá Vốn Hàng Bán (Xuất)</h4>
        <div class="value"><?php echo number_format($total_export_val); ?> ₫</div>
    </div>
    <div class="stat-card card-info">
        <h4>Tổng Số Giao Dịch</h4>
        <div class="value"><?php echo number_format($total_records); ?></div>
    </div>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row">
        <div class="filter-group">
            <label>Tên sản phẩm</label>
            <input type="text" name="search" class="form-control" placeholder="Nhập tên..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group">
            <label>Loại GD</label>
            <select name="type" class="form-control">
                <option value="all" <?php if($type == 'all') echo 'selected'; ?>>Tất cả</option>
                <option value="in" <?php if($type == 'in') echo 'selected'; ?>>Nhập kho (+)</option>
                <option value="out" <?php if($type == 'out') echo 'selected'; ?>>Xuất kho (-)</option>
            </select>
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

        <div class="filter-group action-group" style="flex-direction: row; align-items: flex-end;">
            <button type="submit" class="btn-filter">🔍 Lọc</button>
            <?php if($search || $type!='all' || $filter_shift || $filter_day || $filter_month || ($filter_year != date('Y'))): ?>
                <a href="inventory_history.php" class="btn-reset" title="Đặt lại">↺</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div style="margin-bottom: 15px; font-size: 13px; color: #666; font-style: italic; display:flex; justify-content:space-between;">
    <span>Đang xem trang: <strong><?php echo $page; ?>/<?php echo $total_pages; ?></strong></span>
    <span>Tổng: <strong><?php echo $total_records; ?></strong> dòng</span>
</div>

<?php if ($result_data && mysqli_num_rows($result_data) > 0): ?>
<table>
    <thead>
        <tr>
            <th width="150">Thời gian / Ca</th>
            <th>Sản phẩm</th>
            <th class="text-center">Loại</th>
            <th class="text-right">Số lượng</th>
            <th class="text-right">Giá vốn</th> 
            <th class="text-right">Thành tiền</th> 
            <th>Ghi chú</th>
            <th width="50" class="text-center">Xóa</th> </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result_data)): 
            $qty = (int)$row['quantity'];
            $is_import = $qty > 0;
            
            // --- [FIX] LOGIC HIỂN THỊ GIÁ ---
            $hist_price = (float)$row['import_price']; 
            $curr_cost  = (float)$row['original_price'];
            
            // Nếu giá lịch sử > 0 thì dùng, nếu = 0 thì lấy giá vốn hiện tại
            $display_price = ($hist_price > 0) ? $hist_price : $curr_cost;
            
            $total_value = abs($qty) * $display_price;
            
            $h = date('H', strtotime($row['created_at']));
            $shift_label = '';
            if ($h >= 6 && $h < 12) $shift_label = '<span class="shift-badge shift-sang">Sáng</span>';
            elseif ($h >= 12 && $h < 18) $shift_label = '<span class="shift-badge shift-chieu">Chiều</span>';
            else $shift_label = '<span class="shift-badge shift-toi">Tối</span>';
        ?>
        <tr style="background-color: <?php echo $is_import ? '#fff' : '#fffcfc'; ?>;">
            <td>
                <div class="font-bold" style="color: #555;">
                    <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                </div>
                <div>
                    <span class="text-muted"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
                    <?php echo $shift_label; ?>
                </div>
            </td>
            
            <td>
                <div class="product-cell">
                    <?php $img_src = !empty($row['image']) ? './uploads/'.$row['image'] : 'https://via.placeholder.com/40'; ?>
                    <img src="<?php echo $img_src; ?>" class="product-img" onerror="this.src='https://via.placeholder.com/40?text=No'">
                    <div>
                        <div class="font-bold" style="color: #333;"><?php echo htmlspecialchars($row['product_name']); ?></div>
                        <small class="text-muted">#<?php echo $row['prod_id']; ?></small>
                    </div>
                </div>
            </td>

            <td class="text-center">
                <?php if($is_import): ?>
                    <span class="badge-in">📥 Nhập</span>
                <?php else: ?>
                    <span class="badge-out">📤 Xuất</span>
                <?php endif; ?>
            </td>

            <td class="text-right" style="font-size: 15px;">
                <?php if($is_import): ?>
                    <b class="text-green">+<?php echo $qty; ?></b>
                <?php else: ?>
                    <b class="text-red"><?php echo $qty; ?></b>
                <?php endif; ?>
            </td>

            <td class="text-right text-muted">
                <?php echo number_format($display_price); ?> ₫
            </td>

            <td class="text-right font-bold">
                <?php if($is_import): ?>
                    <span class="text-green"><?php echo number_format($total_value); ?> ₫</span>
                <?php else: ?>
                    <span class="text-red">-<?php echo number_format($total_value); ?> ₫</span>
                <?php endif; ?>
            </td>

            <td style="color:#666; font-size: 13px;">
                <?php echo htmlspecialchars($row['note']); ?>
            </td>
            <td class="text-center">
                <a href="inventory_delete.php?id=<?php echo $row['id']; ?>" 
                onclick="return confirmDeleteHistory(event, this.href, '<?php echo $qty; ?>', '<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>')" 
                class="btn-action-delete" title="Xóa dòng này">
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
        Không tìm thấy dữ liệu phù hợp.
    </div>
<?php endif; ?>
<script>
    function confirmDeleteHistory(event, url, qty, name) {
        event.preventDefault(); // Chặn chuyển trang ngay lập tức

        let typeText = qty > 0 ? "NHẬP KHO" : "XUẤT KHO";
        let stockAction = qty > 0 ? "TRỪ ĐI" : "CỘNG LẠI";
        let qtyAbs = Math.abs(qty);

        Swal.fire({
            title: 'Xóa lịch sử ' + typeText + '?',
            html: `Bạn muốn xóa giao dịch của món: <b>${name}</b>?<br><br>
                   ⚠️ <b>CẢNH BÁO:</b> Hệ thống sẽ tự động <b>${stockAction} ${qtyAbs}</b> đơn vị vào kho hiện tại để cân bằng số liệu.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý xóa & Cân bằng kho',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>
<?php 
// Đóng kết nối
echo '</div>'; 
echo '</div>'; 
?>