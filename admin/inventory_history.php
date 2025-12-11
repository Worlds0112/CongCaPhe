<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// --- 1. KHỞI TẠO BIẾN LỌC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, in, out

// --- 2. XÂY DỰNG QUERY ---
$sql = "SELECT h.*, p.name as product_name, p.image, p.id as prod_id 
        FROM inventory_history h 
        JOIN products p ON h.product_id = p.id 
        WHERE 1=1";

// Lọc theo tên sản phẩm
if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $sql .= " AND p.name LIKE '%$s%'";
}

// Lọc theo ngày
if (!empty($date)) {
    $sql .= " AND DATE(h.created_at) = '$date'";
}

// Lọc theo loại (Nhập/Xuất)
if ($type == 'in') {
    $sql .= " AND h.quantity > 0"; // Số dương là Nhập
} elseif ($type == 'out') {
    $sql .= " AND h.quantity < 0"; // Số âm là Xuất/Giảm
}

$sql .= " ORDER BY h.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* CSS Bộ lọc đẹp */
    .filter-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 4px solid #6f42c1; }
    .filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .filter-group { flex: 1; min-width: 150px; }
    .filter-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; color: #555; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    
    .btn-filter { background: #6f42c1; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 38px; }
    .btn-reset { background: #eee; color: #333; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; height: 38px; display: inline-flex; align-items: center; }

    /* CSS Bảng */
    table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    th { background: #f8f9fa; color: #555; text-transform: uppercase; font-size: 13px; font-weight: 700; }
    tr:hover { background: #f9f9f9; }
    
    /* Badge trạng thái */
    .badge-in { background: #e3fcef; color: #0f5132; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; min-width: 80px; text-align: center; }
    .badge-out { background: #f8d7da; color: #842029; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; min-width: 80px; text-align: center; }
    
    .product-cell { display: flex; align-items: center; gap: 10px; font-weight: 500; color: #333; }
    .product-img { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; border: 1px solid #eee; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
    <h2 style="margin:0; border-left: 5px solid #6f42c1; padding-left: 15px; color: #333;">Lịch sử Nhập/Xuất Kho</h2>
    <a href="inventory_import.php" class="btn-add" style="background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;">+ Nhập Hàng</a>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row">
        
        <div class="filter-group" style="flex: 2;">
            <label>🔍 Tên sản phẩm:</label>
            <input type="text" name="search" class="form-control" placeholder="Nhập tên món..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group">
            <label>📅 Ngày:</label>
            <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
        </div>

        <div class="filter-group">
            <label>🔄 Loại giao dịch:</label>
            <select name="type" class="form-control">
                <option value="all" <?php if($type == 'all') echo 'selected'; ?>>Tất cả</option>
                <option value="in" <?php if($type == 'in') echo 'selected'; ?>>📥 Nhập kho (+)</option>
                <option value="out" <?php if($type == 'out') echo 'selected'; ?>>📤 Xuất/Giảm (-)</option>
            </select>
        </div>

        <div class="filter-group" style="flex: 0; min-width: auto;">
            <button type="submit" class="btn-filter">Lọc</button>
        </div>
        
        <?php if(!empty($search) || !empty($date) || $type != 'all'): ?>
            <div class="filter-group" style="flex: 0; min-width: auto;">
                <a href="inventory_history.php" class="btn-reset">↺</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
<table>
    <thead>
        <tr>
            <th width="150">Thời gian</th>
            <th>Sản phẩm</th>
            <th width="120" style="text-align: center;">Loại</th>
            <th width="120" style="text-align: right;">Số lượng</th>
            <th>Ghi chú</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): 
            $qty = (int)$row['quantity'];
            $is_import = $qty > 0;
            $row_class = $is_import ? '' : 'style="background-color: #fff5f5;"'; // Tô nền nhẹ cho dòng xuất
        ?>
        <tr <?php echo $row_class; ?>>
            <td>
                <div style="font-weight:bold;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></div>
                <div style="color:#888; font-size:12px;"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></div>
            </td>
            
            <td>
                <div class="product-cell">
                    <?php if($row['image']): ?>
                        <img src="uploads/<?php echo $row['image']; ?>" class="product-img">
                    <?php else: ?>
                        <div class="product-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#999;">No</div>
                    <?php endif; ?>
                    <div>
                        <div><?php echo htmlspecialchars($row['product_name']); ?></div>
                        <small style="color:#999;">ID: #<?php echo $row['prod_id']; ?></small>
                    </div>
                </div>
            </td>

            <td style="text-align: center;">
                <?php if($is_import): ?>
                    <span class="badge-in">NHẬP KHO</span>
                <?php else: ?>
                    <span class="badge-out">XUẤT/GIẢM</span>
                <?php endif; ?>
            </td>

            <td style="text-align: right; font-size: 15px;">
                <?php if($is_import): ?>
                    <b style="color: #198754;">+<?php echo $qty; ?></b>
                <?php else: ?>
                    <b style="color: #dc3545;"><?php echo $qty; ?></b>
                <?php endif; ?>
            </td>

            <td style="color:#666;">
                <?php echo htmlspecialchars($row['note']); ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <div style="text-align:center; padding: 40px; color: #777; background:white; border-radius:8px; margin-top:10px;">
        🔍 Không tìm thấy dữ liệu phù hợp.
    </div>
<?php endif; ?>

<?php 
echo '</div></div>';
require '../includes/footer.php'; 
?>