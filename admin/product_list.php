<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// 3. LẤY DỮ LIỆU SẢN PHẨM
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* 🟢 CSS MỚI: Tạo khung bao quanh để căn lề */
    .admin-wrapper {
        max-width: 1200px;
        margin: 0 auto;      /* Căn giữa màn hình */
        padding: 30px 20px;  /* Cách trên dưới 30px, trái phải 20px */
    }

    h2 {
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 24px;
        border-left: 5px solid #28a745; /* Tạo điểm nhấn xanh lá bên trái */
        padding-left: 15px;
    }

    /* CSS cho nút Thêm mới */
    .btn-add {
        display: inline-block;
        background-color: #28a745; 
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .btn-add:hover { background-color: #218838; transform: translateY(-2px); }

    /* CSS Bảng */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background-color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border-radius: 10px; /* Bo tròn góc bảng */
        overflow: hidden;
    }
    th, td { 
        border-bottom: 1px solid #eee; 
        padding: 15px 20px; /* Tăng khoảng cách trong ô cho thoáng */
        text-align: left; 
        vertical-align: middle;
    }
    th { 
        background-color: #f8f9fa; 
        font-weight: 700;
        color: #555;
        text-transform: uppercase;
        font-size: 13px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover { background-color: #f1f3f5; }
    
    img { 
        max-width: 60px; 
        border-radius: 6px; 
        border: 1px solid #ddd;
        padding: 2px;
    }
    .actions { width: 140px; text-align: center; }
    
    /* CSS cho nút Sửa/Xóa */
    .actions a {
        text-decoration: none;
        padding: 6px 12px;
        margin: 0 3px;
        border-radius: 4px;
        color: white;
        font-size: 13px;
        font-weight: 500;
        display: inline-block;
        transition: opacity 0.2s;
    }
    .actions a:hover { opacity: 0.9; }
    .btn-edit { background-color: #007bff; }
    .btn-delete { background-color: #dc3545; }
</style>

<div class="admin-wrapper">

    <h2>Quản lý sản phẩm</h2>
    <p><a href="product_add.php" class="btn-add">+ Thêm sản phẩm mới</a></p>

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
                        else echo '<span style="color:red;">Hết hàng</span>';
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
    <?php endif; ?>

</div> <?php
if ($result) mysqli_free_result($result);
disconnect_db();
require '../includes/footer.php'; 
?>