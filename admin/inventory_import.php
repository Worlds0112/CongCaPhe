<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

$message = "";

// 1. XỬ LÝ LƯU KHO (Giữ nguyên logic cũ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_inventory'])) {
    $import_data = $_POST['products']; 
    $count_updated = 0;

    foreach ($import_data as $pid => $data) {
        $qty_in = (int)$data['qty'];
        $price_in = (int)$data['price']; 

        if ($qty_in > 0) {
            $sql_update = "UPDATE products SET stock = stock + $qty_in";
            if ($price_in > 0) $sql_update .= ", original_price = $price_in";
            $sql_update .= " WHERE id = $pid";
            mysqli_query($conn, $sql_update);

            $note = "Nhập hàng nhanh";
            $sql_log = "INSERT INTO inventory_history (product_id, quantity, note) VALUES ('$pid', '$qty_in', '$note')";
            mysqli_query($conn, $sql_log);
            $count_updated++;
        }
    }

    if ($count_updated > 0) {
        $message = "<div class='alert alert-success'>✅ Đã nhập kho thành công cho $count_updated sản phẩm!</div>";
    }
}

// 2. BỘ LỌC TÌM KIẾM (Lấy từ product_list sang)
$search = "";
$stock_filter = "all";
$sql_prod = "SELECT * FROM products WHERE 1=1";

// a. Tìm kiếm từ khóa
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $s = mysqli_real_escape_string($conn, $search);
        $sql_prod .= " AND (id LIKE '%$s%' OR name LIKE '%$s%')";
    }
}

// b. Lọc theo trạng thái kho
if (isset($_GET['stock_filter'])) {
    $stock_filter = $_GET['stock_filter'];
    switch ($stock_filter) {
        case 'out': $sql_prod .= " AND stock = 0"; break;
        case 'low': $sql_prod .= " AND stock > 0 AND stock <= 5"; break;
        case 'high': $sql_prod .= " AND stock > 5"; break;
    }
}

$sql_prod .= " ORDER BY name ASC";
$result_prod = mysqli_query($conn, $sql_prod);

// 3. LẤY LỊCH SỬ GẦN NHẤT
$sql_hist = "SELECT h.*, p.name as product_name FROM inventory_history h 
             JOIN products p ON h.product_id = p.id 
             ORDER BY h.created_at DESC LIMIT 5";
$result_hist = mysqli_query($conn, $sql_hist);
?>

<style>
    /* CSS cho thanh tìm kiếm (Giống product_list) */
    .filter-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 4px solid #6f42c1; }
    .filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .filter-group { flex: 1; min-width: 200px; }
    .filter-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; color: #555; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    
    .btn-filter { background: #6f42c1; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 38px; }
    .btn-reset { background: #eee; color: #333; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; height: 38px; display: inline-flex; align-items: center; }

    /* CSS Bảng nhập liệu */
    .import-table { width: 100%; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-collapse: collapse; margin-bottom: 30px; }
    .import-table th, .import-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .import-table th { background: #f8f9fa; text-align: left; color: #555; }
    
    .inp-qty { width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-weight: bold; color: #217346; background: #f9fff9; }
    .inp-qty:focus { border-color: #217346; background: white; outline: none; box-shadow: 0 0 5px rgba(33, 115, 70, 0.2); }
    
    .inp-price { width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: right; }
    
    .btn-save { position: fixed; bottom: 30px; right: 30px; background: #217346; color: white; padding: 15px 30px; border: none; border-radius: 50px; font-size: 16px; font-weight: bold; box-shadow: 0 5px 20px rgba(33, 115, 70, 0.4); cursor: pointer; transition: 0.3s; z-index: 100; display: flex; align-items: center; gap: 10px; }
    .btn-save:hover { transform: translateY(-5px); background: #1e6b41; }
    
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

    /* Lịch sử mini */
    .mini-history { background: #fff; padding: 15px; border-radius: 8px; margin-top: 30px; border: 1px dashed #ccc; }
    .mini-history h4 { margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; }
    .hist-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 13px; }
    .hist-item:last-child { border-bottom: none; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
    <h2 style="margin:0; border-left: 5px solid #217346; padding-left: 15px; color: #217346;">Nhập Hàng Nhanh</h2>
</div>

<?php echo $message; ?>

<div class="filter-card">
    <form method="GET" class="filter-row">
        
        <div class="filter-group" style="flex: 2;">
            <label>🔍 Tìm tên món:</label>
            <input type="text" name="search" class="form-control" placeholder="Nhập tên sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group">
            <label>📦 Trạng thái kho:</label>
            <select name="stock_filter" class="form-control">
                <option value="all" <?php if($stock_filter == 'all') echo 'selected'; ?>>Tất cả</option>
                <option value="out" <?php if($stock_filter == 'out') echo 'selected'; ?>>🔥 Hết hàng (0)</option>
                <option value="low" <?php if($stock_filter == 'low') echo 'selected'; ?>>⚠️ Sắp hết (1-5)</option>
                <option value="high" <?php if($stock_filter == 'high') echo 'selected'; ?>>✅ Còn nhiều</option>
            </select>
        </div>

        <div class="filter-group" style="flex: 0; min-width: auto;">
            <button type="submit" class="btn-filter">Lọc</button>
        </div>
        
        <?php if(!empty($search) || $stock_filter != 'all'): ?>
            <div class="filter-group" style="flex: 0; min-width: auto;">
                <a href="inventory_import.php" class="btn-reset">↺</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<form method="POST" action="">
    <table class="import-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 60px;">Ảnh</th>
                <th>Tên sản phẩm</th>
                <th style="width: 120px;">Tồn kho</th>
                <th style="width: 120px; background: #e3fcef; color: #0f5132;">+ Nhập thêm</th>
                <th style="width: 130px;">Giá vốn mới</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (mysqli_num_rows($result_prod) > 0) {
                while($row = mysqli_fetch_assoc($result_prod)): 
                    // Style cho tồn kho thấp
                    $stock_style = "background: #eee; color: #333;";
                    if($row['stock'] == 0) $stock_style = "background: #f8d7da; color: #721c24; font-weight:bold;";
                    elseif($row['stock'] <= 5) $stock_style = "background: #fff3cd; color: #856404; font-weight:bold;";
            ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td>
                    <?php if($row['image']): ?>
                        <img src="uploads/<?php echo $row['image']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                    <?php endif; ?>
                </td>
                <td style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></td>
                
                <td>
                    <span style="padding: 5px 10px; border-radius: 10px; font-size: 12px; <?php echo $stock_style; ?>">
                        <?php echo $row['stock']; ?>
                    </span>
                </td>
                
                <td style="background: #f9fff9;">
                    <input type="number" name="products[<?php echo $row['id']; ?>][qty]" class="inp-qty" placeholder="0" min="0">
                </td>
                
                <td>
                    <input type="number" name="products[<?php echo $row['id']; ?>][price]" class="inp-price" value="<?php echo $row['original_price']; ?>">
                </td>
            </tr>
            <?php 
                endwhile; 
            } else {
                echo '<tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">Không tìm thấy sản phẩm nào phù hợp.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <button type="submit" name="save_inventory" class="btn-save">💾 LƯU KHO NGAY</button>
</form>

<div class="mini-history">
    <h4>🕒 Vừa nhập gần đây</h4>
    <?php if(mysqli_num_rows($result_hist) > 0): ?>
        <?php while($h = mysqli_fetch_assoc($result_hist)): ?>
            <div class="hist-item">
                <div style="flex: 1;">
                    <b><?php echo htmlspecialchars($h['product_name']); ?></b>
                    <span style="color:#999; margin-left:10px; font-size:12px;"><?php echo date('H:i:s d/m', strtotime($h['created_at'])); ?></span>
                </div>
                <div style="color: green; font-weight: bold;">+<?php echo $h['quantity']; ?></div>
            </div>
        <?php endwhile; ?>
        <div style="text-align: center; margin-top: 10px;">
            <a href="inventory_history.php" style="font-size: 13px; text-decoration: none; color: #217346;">Xem tất cả lịch sử →</a>
        </div>
    <?php else: ?>
        <div style="color:#999; font-style:italic;">Chưa có lịch sử nhập.</div>
    <?php endif; ?>
</div>

<?php 
echo '</div></div>';
?>