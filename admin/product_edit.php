<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php';

$message = ""; 
$product = null; 

// 2. XỬ LÝ UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    
    $id = (int)$_POST['product_id'];
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = (int)$_POST['product_price'];
    $stock = (int)$_POST['product_stock'];
    $category_id = (int)$_POST['category_id'];
    $image_name = mysqli_real_escape_string($conn, $_POST['old_image']);

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        $original_name = basename($_FILES["product_image"]["name"]);
        $new_image_name = time() . "_" . str_replace(" ", "-", $original_name);
        $target_file = $target_dir . $new_image_name;

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_name = $new_image_name;
            if ($_POST['old_image'] != "default.jpg" && file_exists($target_dir . $_POST['old_image'])) {
                 unlink($target_dir . $_POST['old_image']);
            }
        } else {
            $message = "Lỗi upload ảnh.";
        }
    }

    $sql = "UPDATE products SET category_id = ?, name = ?, price = ?, stock = ?, image = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isissi", $category_id, $name, $price, $stock, $image_name, $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Cập nhật thành công!";
        } else {
            $message = "Lỗi SQL: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Lỗi chuẩn bị: " . mysqli_error($conn);
    }
}

// 3. LẤY DỮ LIỆU CŨ
$id_to_fetch = 0;
if (isset($_POST['product_id'])) $id_to_fetch = (int)$_POST['product_id'];
elseif (isset($_GET['id'])) $id_to_fetch = (int)$_GET['id'];

if ($id_to_fetch > 0) {
    $sql_select = "SELECT * FROM products WHERE id = ? LIMIT 1";
    $stmt_select = mysqli_prepare($conn, $sql_select);
    if ($stmt_select) {
        mysqli_stmt_bind_param($stmt_select, "i", $id_to_fetch);
        mysqli_stmt_execute($stmt_select);
        $result = mysqli_stmt_get_result($stmt_select);
        $product = mysqli_fetch_assoc($result); 
        mysqli_stmt_close($stmt_select);
    }
}

if (!$product) {
    echo "<h3 style='text-align:center; margin-top:50px;'>Không tìm thấy sản phẩm.</h3>";
    die();
}

// 4. GỌI HEADER
require '../includes/header.php'; 
?>

<style>
    .admin-wrapper {
        max-width: 1200px; margin: 0 auto; padding: 30px 20px;
    }
    h2 { 
        color: #333; margin-bottom: 1.5rem;
        border-left: 5px solid #007bff; /* Màu xanh dương cho trang Sửa */
        padding-left: 15px;
    }
    .form-container {
        background: #fff; padding: 30px; border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        max-width: 700px; margin: 0 auto;
    }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="file"] {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; box-sizing: border-box;
    }
    .btn {
        display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; color: white; border: none; cursor: pointer; font-size: 16px; transition: background 0.2s;
    }
    .btn-update { background-color: #007bff; width: 100%; }
    .btn-update:hover { background-color: #0069d9; }
    .btn-back { background-color: #6c757d; font-size: 14px; margin-bottom: 20px; }
    .btn-back:hover { background-color: #5a6268; }
    .message {
        padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;
        max-width: 700px; margin-left: auto; margin-right: auto;
    }
    .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .current-image { max-width: 120px; border-radius: 5px; margin-top: 10px; border: 1px solid #ddd; padding: 3px; }
</style>

<div class="admin-wrapper">

    <h2>Sửa sản phẩm: <?php echo htmlspecialchars($product['name']); ?></h2>
    <p><a href="product_list.php" class="btn btn-back">← Quay về danh sách</a></p>

    <?php
    if ($message != "") {
        $msg_class = (strpos($message, 'Lỗi') !== false) ? 'error' : 'success';
        echo "<div class='message $msg_class'>" . htmlspecialchars($message) . "</div>";
    }
    ?>

    <div class="form-container">
        <form action="" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($product['image']); ?>">

            <div class="form-group">
                <label>Tên sản phẩm:</label>
                <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Giá bán (VNĐ):</label>
                <input type="number" name="product_price" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Số lượng tồn kho:</label>
                <input type="number" name="product_stock" value="<?php echo $product['stock']; ?>" required>
            </div>
            <div class="form-group">
                <label>Danh mục (ID):</label>
                <input type="number" name="category_id" value="<?php echo $product['category_id']; ?>" required>
            </div>
            <div class="form-group">
                <label>Ảnh sản phẩm hiện tại:</label><br>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="current-image" alt="Ảnh sản phẩm">
            </div>
            <div class="form-group">
                <label>Tải ảnh mới (Nếu muốn thay đổi):</label>
                <input type="file" name="product_image" accept="image/*">
                <br><small style="color:#666;">Để trống nếu không muốn đổi ảnh</small>
            </div>
            <div class="form-group">
                <button type="submit" name="update_product" class="btn btn-update">Cập nhật sản phẩm</button>
            </div>
        </form>
    </div>

</div>

<?php
disconnect_db(); 
require '../includes/footer.php'; 
?>