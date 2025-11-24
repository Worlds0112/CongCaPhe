<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 

// 2. GỌI HEADER CHUNG
require '../includes/header.php'; 

// 3. LẤY DỮ LIỆU SẢN PHẨM
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Lỗi truy vấn: " . mysqli_error($conn);
}
?>

<style>
    /* ⭐️ SỬA LỖI Ở ĐÂY ⭐️
      Ghi đè CSS từ header.php, giảm khoảng cách trên cùng
    */
    main.content {
        padding-top: 1rem; /* Giảm từ 2rem (32px) xuống 1rem (16px) */
        /* Hoặc dùng padding-top: 0; nếu muốn sát hơn nữa */
    }

    /* CSS CŨ CỦA BẠN (giữ nguyên) */
    h2 {
        color: #333;
        margin-bottom: 1rem;
    }
    .btn-add {
        display: inline-block;
        background-color: #28a745; 
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .btn-add:hover { background-color: #218838; }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background-color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        overflow: hidden;
    }
    th, td { 
        border-bottom: 1px solid #ddd; 
        padding: 12px 15px; 
        text-align: left; 
        vertical-align: middle;
    }
    th { 
        background-color: #f2f2f2; 
        font-weight: 600;
        color: #333;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover { background-color: #f9f9f9; }
    img { max-width: 60px; border-radius: 4px; }
    .actions { width: 120px; text-align: center; }
    .actions a {
        text-decoration: none;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 4px;
        color: white;
        font-size: 14px;
        font-weight: 500;
        display: inline-block;
    }
    .btn-edit { background-color: #007bff; }
    .btn-edit:hover { background-color: #0069d9; }
    .btn-delete { background-color: #dc3545; }
    .btn-delete:hover { background-color: #c82333; }
</style>

<h2>Quản lý sản phẩm</h2>
<p><a href="product_add.php" class="btn-add">Thêm sản phẩm mới</a></p>

<?php if ($result): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Tồn kho</th>
            <th class="actions">Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td>
                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Ảnh">
                </td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo number_format($row['price']); ?> VNĐ</td>
                <td><?php echo $row['stock']; ?></td>
                <td class="actions">
                    <a href="product_edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">Sửa</a>
                    <a href="product_delete.php?id=<?php echo $row['id']; ?>" 
                       onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm: <?php echo htmlspecialchars($row['name']); ?> không?');"
                       class="btn-delete">Xóa</a>
                </td>
            </tr>
        <?php
        } // Kết thúc vòng lặp
        ?>
    </tbody>
</table>
<?php endif; ?>

<?php
if ($result) {
    mysqli_free_result($result);
}
disconnect_db();

require '../includes/footer.php'; 
?>