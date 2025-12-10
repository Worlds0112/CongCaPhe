<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// Lọc theo ngày
$date = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT h.*, p.name as product_name, p.image 
        FROM inventory_history h 
        JOIN products p ON h.product_id = p.id 
        WHERE 1=1";

if ($date) {
    $sql .= " AND DATE(h.created_at) = '$date'";
}

elseif ($month) {
    $sql .= " AND DATE_FORMAT(h.created_at, '%Y-%m') = '$month'";
}

$sql .= " ORDER BY h.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* Dùng lại CSS bảng chuẩn */
    table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden; }
    th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
    th { background: #6f42c1; color: white; text-transform: uppercase; font-size: 13px; }
    tr:hover { background: #f9f9f9; }
    .qty-badge { background: #e3fcef; color: #0f5132; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
</style>

<h2 style="border-left: 5px solid #6f42c1; padding-left: 15px;">Lịch sử Nhập Kho</h2>

<div style="margin-bottom: 20px;">
    <form method="GET" style="display:flex; gap:10px; align-items:center;">
        <input type="date" name="date" value="<?php echo $date; ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
        <button type="submit" class="btn-search">Xem</button>
        <?php if($date): ?><a href="inventory_history.php" class="btn-reset">Xóa lọc</a><?php endif; ?>
    </form>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
<table>
    <thead><tr><th>Thời gian</th><th>Sản phẩm</th><th>Số lượng nhập</th><th>Ghi chú</th></tr></thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td>
                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?><br>
                <small style="color:#888"><?php echo date('H:i', strtotime($row['created_at'])); ?></small>
            </td>
            <td style="font-weight:bold; color:#333; display:flex; align-items:center; gap:10px;">
                <img src="uploads/<?php echo $row['image']; ?>" style="width:40px; height:40px; border-radius:4px; object-fit:cover;">
                <?php echo htmlspecialchars($row['product_name']); ?>
            </td>
            <td><span class="qty-badge">+<?php echo number_format($row['quantity']); ?></span></td>
            <td style="color:#666; font-style:italic;"><?php echo htmlspecialchars($row['note']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center; padding:30px; background:white;">Chưa có dữ liệu nhập kho.</p>
<?php endif; ?>

<?php 
echo '</div></div>';
?>