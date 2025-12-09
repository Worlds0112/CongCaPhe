<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// --- XỬ LÝ TÌM KIẾM & SẮP XẾP ---

// Mặc định
$search = "";
$sort_by = "id";
$order_dir = "DESC";

// 1. Lấy từ khóa tìm kiếm
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// 2. Lấy tiêu chí sắp xếp (Whitelist để bảo mật)
$allowed_sort = ['id', 'name', 'price', 'stock'];
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort)) {
    $sort_by = $_GET['sort_by'];
}

// 3. Lấy chiều sắp xếp (ASC/DESC)
if (isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC'])) {
    $order_dir = strtoupper($_GET['order_dir']);
}

// 4. Xây dựng câu truy vấn
$sql = "SELECT * FROM products WHERE 1=1"; // Kỹ thuật 1=1 để dễ nối chuỗi AND

// Nếu có tìm kiếm
if (!empty($search)) {
    // Dùng mysqli_real_escape_string để chống SQL Injection
    $s = mysqli_real_escape_string($conn, $search);
    // Tìm theo ID hoặc Tên (dùng LIKE để tìm gần đúng)
    $sql .= " AND (id LIKE '%$s%' OR name LIKE '%$s%')";
}

// Thêm sắp xếp
$sql .= " ORDER BY $sort_by $order_dir";

$result = mysqli_query($conn, $sql);
?>



<div class="admin-wrapper">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
        <h2 style="margin-bottom:0;">Quản lý sản phẩm</h2>
        <a href="product_add.php" class="btn-add">+ Thêm sản phẩm mới</a>
    </div>

    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="search" class="filter-input" 
                   placeholder="Nhập tên hoặc ID..." 
                   value="<?php echo htmlspecialchars($search); ?>">

            <select name="sort_by" class="filter-select">
                <option value="id" <?php if($sort_by == 'id') echo 'selected'; ?>>Theo ID</option>
                <option value="name" <?php if($sort_by == 'name') echo 'selected'; ?>>Theo Tên</option>
                <option value="price" <?php if($sort_by == 'price') echo 'selected'; ?>>Theo Giá</option>
                <option value="stock" <?php if($sort_by == 'stock') echo 'selected'; ?>>Theo Kho</option>
            </select>

            <select name="order_dir" class="filter-select">
                <option value="DESC" <?php if($order_dir == 'DESC') echo 'selected'; ?>>Mới nhất / Giảm dần</option>
                <option value="ASC" <?php if($order_dir == 'ASC') echo 'selected'; ?>>Cũ nhất / Tăng dần</option>
            </select>

            <button type="submit" class="btn-search">Lọc</button>
            
            <?php if(!empty($search) || $sort_by != 'id'): ?>
                <a href="product_list.php" class="btn-reset">Đặt lại</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID <?php if($sort_by=='id') echo ($order_dir=='ASC' ? '▲' : '▼'); ?></th>
                <th>Ảnh</th>
                <th>Tên sản phẩm <?php if($sort_by=='name') echo ($order_dir=='ASC' ? '▲' : '▼'); ?></th>
                <th>Giá <?php if($sort_by=='price') echo ($order_dir=='ASC' ? '▲' : '▼'); ?></th>
                <th>Tồn kho <?php if($sort_by=='stock') echo ($order_dir=='ASC' ? '▲' : '▼'); ?></th>
                <th class="actions">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td>
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Img">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                    </td>
                    <td style="color: #d32f2f; font-weight: bold;">
                        <?php echo number_format($row['price']); ?> ₫
                    </td>
                    <td>
                        <?php 
                        if($row['stock'] > 0) echo $row['stock']; 
                        else echo '<span style="color:red; font-weight:bold;">Hết hàng</span>';
                        ?>
                    </td>
                    <td class="actions">
                        <a href="product_edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">Sửa</a>
                        <a href="product_delete.php?id=<?php echo $row['id']; ?>" 
                           onclick="return confirm('Xóa sản phẩm: <?php echo htmlspecialchars($row['name']); ?>?');"
                           class="btn-delete">Xóa</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; color: #777; background:white; border-radius:8px;">
            Không tìm thấy kết quả nào phù hợp.
        </div>
    <?php endif; ?>

</div> 

<?php
if ($result) mysqli_free_result($result);
disconnect_db();
require '../includes/footer.php'; 
?>