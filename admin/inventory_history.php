<?php
// 1. KẾT NỐI & BẢO VỆ
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none; flex: 1;">';

// --- 2. XỬ LÝ LỌC & PHÂN TRANG ---
$limit = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Nhận dữ liệu tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type']) ? $_GET['type'] : 'all'; 

// Lọc thời gian & Ca (Mới thêm)
$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : ''; 
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";    
$filter_month = isset($_GET['month']) ? $_GET['month'] : ""; 
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y'); 
if ($filter_year == 'all') $filter_year = '';

// --- 3. XÂY DỰNG ĐIỀU KIỆN WHERE ---
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

// --- LOGIC LỌC CA (Dựa trên giờ tạo phiếu) ---
if (!empty($filter_shift) && $filter_shift != 'all') {
    if ($filter_shift == 'sang') {
        // Ca sáng: 06:00 - 11:59
        $where_sql .= " AND HOUR(h.created_at) >= 6 AND HOUR(h.created_at) < 12";
    } elseif ($filter_shift == 'chieu') {
        // Ca chiều: 12:00 - 17:59
        $where_sql .= " AND HOUR(h.created_at) >= 12 AND HOUR(h.created_at) < 18";
    } elseif ($filter_shift == 'toi') {
        // Ca tối: 18:00 trở đi
        $where_sql .= " AND HOUR(h.created_at) >= 18";
    }
}

if (!empty($filter_day))   $where_sql .= " AND DAY(h.created_at) = '$filter_day'";
if (!empty($filter_month)) $where_sql .= " AND MONTH(h.created_at) = '$filter_month'";
if (!empty($filter_year))  $where_sql .= " AND YEAR(h.created_at) = '$filter_year'";

// --- 4. QUERY TÍNH TỔNG (DASHBOARD) ---
$sql_stats = "SELECT h.quantity, h.import_price, p.price 
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
        $price = isset($row['import_price']) ? $row['import_price'] : (isset($row['price']) ? $row['price'] : 0);
        $val = abs($qty) * $price;
        
        if ($qty > 0) $total_import_val += $val;
        else $total_export_val += $val;
    }
}
$total_pages = ceil($total_records / $limit);

// --- 5. QUERY HIỂN THỊ ---
$sql_data = "SELECT h.*, p.name as product_name, p.image, p.id as prod_id 
             FROM inventory_history h 
             JOIN products p ON h.product_id = p.id 
             $where_sql 
             ORDER BY h.created_at DESC 
             LIMIT $offset, $limit";
$result_data = mysqli_query($conn, $sql_data);
?>

<style>
    /* 1. CSS GIAO DIỆN (Đồng bộ với Order List) */
    .dashboard-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
    .stat-card { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #ccc; }
    .stat-card h4 { margin: 0 0 5px 0; font-size: 14px; color: #666; text-transform: uppercase; }
    .stat-card .value { font-size: 22px; font-weight: bold; }
    
    .card-import { border-color: #28a745; } .card-import .value { color: #28a745; }
    .card-export { border-color: #dc3545; } .card-export .value { color: #dc3545; }
    .card-info { border-color: #17a2b8; } .card-info .value { color: #17a2b8; }

    /* Filter Styles (Giống Order List) */
    .filter-card { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px; color: #555; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    .btn-filter { background: #17A2B8; color: white; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer; height: 38px; font-weight: bold;}
    .btn-reset { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; color: #666; text-decoration: none; margin-left: 5px; }
    .btn-add { background: #28a745; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; }

    /* Table Styles */
    table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
    th, td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    th { background: #f8f9fa; color: #555; text-transform: uppercase; font-size: 12px; font-weight: 700; }
    
    /* Badges */
    .badge-in { background: #d1e7dd; color: #0f5132; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; text-transform: uppercase; border: 1px solid #badbcc; }
    .badge-out { background: #f8d7da; color: #842029; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; text-transform: uppercase; border: 1px solid #f5c2c7; }
    
    /* Shift Badges (Mới) */
    .shift-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: bold; text-transform: uppercase; margin-left: 5px; display: inline-block; }
    .shift-sang { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .shift-chieu { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .shift-toi { background: #e0cffc; color: #59359a; border: 1px solid #cff4fc; }

    .product-cell { display: flex; align-items: center; gap: 10px; }
    .product-img { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; border: 1px solid #eee; }

    /* Pagination (Giống Order List) */
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; background: white; text-decoration: none; color: #333; border-radius: 4px; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination .active { background: #17A2B8; color: white; border-color: #17A2B8; }
    .pagination .disabled { color: #ccc; pointer-events: none; }
</style>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2 style="margin:0; border-left: 5px solid #17a2b8; padding-left: 15px; color: #333;">Lịch sử Kho</h2>
        <a href="inventory_import.php" class="btn-add">+ Nhập Hàng</a>
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
        <form method="GET" class="filter-row" style="flex-wrap: wrap; gap: 10px;">
            <div class="filter-group" style="flex: 1 1 200px;">
                <label>Tên sản phẩm</label>
                <input type="text" name="search" class="form-control" placeholder="Nhập tên..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group" style="width: 120px;">
                <label>Loại GD</label>
                <select name="type" class="form-control">
                    <option value="all" <?php if($type == 'all') echo 'selected'; ?>>Tất cả</option>
                    <option value="in" <?php if($type == 'in') echo 'selected'; ?>>Nhập kho (+)</option>
                    <option value="out" <?php if($type == 'out') echo 'selected'; ?>>Xuất kho (-)</option>
                </select>
            </div>

            <div class="filter-group" style="width: 100px;">
                <label>Ca</label>
                <select name="shift" class="form-control">
                    <option value="all">Tất cả</option>
                    <option value="sang" <?php if($filter_shift == 'sang') echo 'selected'; ?>>Sáng</option>
                    <option value="chieu" <?php if($filter_shift == 'chieu') echo 'selected'; ?>>Chiều</option>
                    <option value="toi" <?php if($filter_shift == 'toi') echo 'selected'; ?>>Tối</option>
                </select>
            </div>

            <div class="filter-group" style="width: 80px;">
                <label>Ngày</label>
                <select name="day" class="form-control">
                    <option value="">--</option>
                    <?php for($d=1; $d<=31; $d++): ?><option value="<?php echo $d; ?>" <?php if($filter_day == $d) echo 'selected'; ?>><?php echo $d; ?></option><?php endfor; ?>
                </select>
            </div>

            <div class="filter-group" style="width: 100px;">
                <label>Tháng</label>
                <select name="month" class="form-control">
                    <option value="">Tất cả</option>
                    <?php for($m=1; $m<=12; $m++): ?><option value="<?php echo $m; ?>" <?php if($filter_month == $m) echo 'selected'; ?>>Tháng <?php echo $m; ?></option><?php endfor; ?>
                </select>
            </div>

            <div class="filter-group" style="width: 100px;">
                <label>Năm</label>
                <select name="year" class="form-control">
                    <option value="all">Tất cả</option>
                    <?php $c=date('Y'); for($y=$c; $y>=$c-5; $y--): ?><option value="<?php echo $y; ?>" <?php if($filter_year == $y) echo 'selected'; ?>><?php echo $y; ?></option><?php endfor; ?>
                </select>
            </div>

            <div class="filter-group action-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn-filter">🔍 Lọc</button>
                <?php if($search || $type!='all' || $filter_shift || $filter_day || $filter_month || ($filter_year != date('Y'))): ?>
                    <a href="inventory_history.php" class="btn-reset" title="Đặt lại" style="margin-left: 5px; line-height: 38px;">↺</a>
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
                <th width="100" style="text-align: center;">Loại</th>
                <th width="100" style="text-align: right;">Số lượng</th>
                <th width="130" style="text-align: right;">Giá vốn</th> 
                <th width="150" style="text-align: right;">Thành tiền</th> 
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result_data)): 
                $qty = (int)$row['quantity'];
                $is_import = $qty > 0;
                $price = isset($row['import_price']) ? (float)$row['import_price'] : (isset($row['price']) ? (float)$row['price'] : 0);
                $total_value = abs($qty) * $price;
                
                // TÍNH TOÁN CA DỰA TRÊN GIỜ
                $h = date('H', strtotime($row['created_at']));
                $shift_label = '';
                if ($h >= 6 && $h < 12) $shift_label = '<span class="shift-badge shift-sang">Sáng</span>';
                elseif ($h >= 12 && $h < 18) $shift_label = '<span class="shift-badge shift-chieu">Chiều</span>';
                else $shift_label = '<span class="shift-badge shift-toi">Tối</span>';
            ?>
            <tr style="background-color: <?php echo $is_import ? '#fff' : '#fffcfc'; ?>;">
                <td>
                    <div style="font-weight:bold; color: #555;">
                        <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                    </div>
                    <div>
                        <small style="color:#999;"><?php echo date('H:i', strtotime($row['created_at'])); ?></small>
                        <?php echo $shift_label; ?>
                    </div>
                </td>
                
                <td>
                    <div class="product-cell">
                        <?php $img_src = !empty($row['image']) ? '../uploads/'.$row['image'] : 'https://via.placeholder.com/40'; ?>
                        <img src="<?php echo $img_src; ?>" class="product-img" onerror="this.src='https://via.placeholder.com/40?text=No'">
                        <div>
                            <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($row['product_name']); ?></div>
                            <small style="color:#999;">#<?php echo $row['prod_id']; ?></small>
                        </div>
                    </div>
                </td>

                <td style="text-align: center;">
                    <?php if($is_import): ?>
                        <span class="badge-in">📥 Nhập</span>
                    <?php else: ?>
                        <span class="badge-out">📤 Xuất</span>
                    <?php endif; ?>
                </td>

                <td style="text-align: right; font-size: 15px;">
                    <?php if($is_import): ?>
                        <b style="color: #198754;">+<?php echo $qty; ?></b>
                    <?php else: ?>
                        <b style="color: #dc3545;"><?php echo $qty; ?></b>
                    <?php endif; ?>
                </td>

                <td style="text-align: right; color: #666;">
                    <?php echo number_format($price); ?> ₫
                </td>

                <td style="text-align: right; font-weight: bold;">
                    <?php if($is_import): ?>
                        <span style="color: #198754;"><?php echo number_format($total_value); ?> ₫</span>
                    <?php else: ?>
                        <span style="color: #dc3545;">-<?php echo number_format($total_value); ?> ₫</span>
                    <?php endif; ?>
                </td>

                <td style="color:#666; font-size: 13px;">
                    <?php echo htmlspecialchars($row['note']); ?>
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

<?php 
echo '</div>'; 
echo '</div>'; 
?>