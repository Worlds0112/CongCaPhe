<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// --- XỬ LÝ TÌM KIẾM & SẮP XẾP ---
$search = "";
$sort_by = "orders.order_date"; // Mặc định sắp xếp theo ngày
$order_dir = "DESC";            // Mới nhất lên đầu

// 1. Lấy từ khóa
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// 2. Whitelist cột sắp xếp (Phải map đúng tên cột trong SQL có JOIN)
$allowed_sort = [
    'id' => 'orders.id',
    'date' => 'orders.order_date',
    'amount' => 'orders.total_amount',
    'user' => 'users.full_name'
];

if (isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $allowed_sort)) {
    $sort_by = $allowed_sort[$_GET['sort_by']];
}

// 3. Chiều sắp xếp
if (isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC'])) {
    $order_dir = strtoupper($_GET['order_dir']);
}

// 4. Query SQL
$sql = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
        FROM orders
        JOIN users ON orders.user_id = users.id
        WHERE 1=1";

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    // Tìm theo ID đơn hoặc Tên nhân viên
    $sql .= " AND (orders.id LIKE '%$s%' OR users.full_name LIKE '%$s%')";
}

$sql .= " ORDER BY $sort_by $order_dir";
$result = mysqli_query($conn, $sql);
?>

<div class="admin-wrapper">

    <h2>Quản lý Hóa đơn</h2>

    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="search" class="filter-input" placeholder="Tìm ID hoặc Tên NV..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="sort_by" class="filter-select">
                <option value="date" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'date') echo 'selected'; ?>>Theo Ngày</option>
                <option value="amount" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'amount') echo 'selected'; ?>>Theo Tổng tiền</option>
                <option value="id" <?php if(isset($_GET['sort_by']) && $_GET['sort_by'] == 'id') echo 'selected'; ?>>Theo Mã HĐ</option>
            </select>

            <select name="order_dir" class="filter-select">
                <option value="DESC" <?php if($order_dir == 'DESC') echo 'selected'; ?>>Giảm dần (Mới/Cao nhất)</option>
                <option value="ASC" <?php if($order_dir == 'ASC') echo 'selected'; ?>>Tăng dần (Cũ/Thấp nhất)</option>
            </select>

            <button type="submit" class="btn-search">Lọc</button>
            <?php if(!empty($search) || isset($_GET['sort_by'])): ?>
                <a href="order_list.php" class="btn-reset">Đặt lại</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Mã HĐ</th>
                <th>Ngày tạo</th>
                <th>Nhân viên</th>
                <th>Tổng tiền</th>
                <th style="text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><strong>#<?php echo $row['id']; ?></strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td> 
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td style="color: #28a745; font-weight: bold;"><?php echo number_format($row['total_amount']); ?> ₫</td>
                <td style="text-align: center;">
                    <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn-view">Chi tiết</a>
                    <a href="order_delete.php?id=<?php echo $row['id']; ?>" 
                       class="btn-delete" 
                       onclick="return confirm('Xóa hóa đơn #<?php echo $row['id']; ?>?');">Xóa</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#999; margin-top: 30px; background: white; padding: 20px; border-radius: 8px;">Không tìm thấy hóa đơn nào.</p>
    <?php endif; ?>

</div> 
<?php
if ($result) mysqli_free_result($result);
disconnect_db();
require '../includes/footer.php'; 
?>