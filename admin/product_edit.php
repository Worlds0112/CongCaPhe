<?php
// 1. BẢO VỆ TRANG (File này tự động session_start, check admin, và connect_db)
require '../includes/auth_admin.php';

$message = ""; 
$product = null; 

// 2. XỬ LÝ POST (NẾU CÓ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    
    // $conn đã có (KHÔNG CẦN connect_db() nữa)
    
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
            // Xóa ảnh cũ (nếu không phải là default)
            if ($_POST['old_image'] != "default.jpg" && file_exists($target_dir . $_POST['old_image'])) {
                 unlink($target_dir . $_POST['old_image']);
            }
        } else {
            $message = "Lỗi khi tải file mới lên. Vẫn giữ ảnh cũ.";
        }
    }

    $sql = "UPDATE products SET category_id = ?, name = ?, price = ?, stock = ?, image = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isissi", 
            $category_id, $name, $price, $stock, $image_name, $id
        );
        if (mysqli_stmt_execute($stmt)) {
            $message = "Cập nhật sản phẩm thành công!";
        } else {
            $message = "Lỗi khi cập nhật: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn);
    }
    // Không ngắt kết nối ở đây
}

// 3. LẤY DỮ LIỆU SẢN PHẨM ĐỂ HIỂN THỊ
$id_to_fetch = 0;
if (isset($_POST['product_id'])) {
    $id_to_fetch = (int)$_POST['product_id']; // Lấy ID từ form vừa submit
} elseif (isset($_GET['id'])) {
    $id_to_fetch = (int)$_GET['id']; // Lấy ID từ URL
}

if ($id_to_fetch > 0) {
    // $conn đã có (KHÔNG CẦN connect_db() nữa)
    
    $sql_select = "SELECT * FROM products WHERE id = ? LIMIT 1";
    $stmt_select = mysqli_prepare($conn, $sql_select);
    
    if ($stmt_select) {
        mysqli_stmt_bind_param($stmt_select, "i", $id_to_fetch);
        mysqli_stmt_execute($stmt_select);
        $result = mysqli_stmt_get_result($stmt_select);
        $product = mysqli_fetch_assoc($result); 
        mysqli_stmt_close($stmt_select);
    } else {
        $message = "Lỗi khi lấy dữ liệu: " . mysqli_error($conn);
    }
    // Không ngắt kết nối ở đây
}

// Nếu không tìm thấy sản phẩm, dừng lại TRƯỚC KHI GỌI HEADER
if (!$product) {
    echo "Không tìm thấy sản phẩm hoặc ID không hợp lệ.";
    disconnect_db(); // Ngắt kết nối rồi thoát
    exit();
}

// 4. GỌI HEADER CHUNG
require '../includes/header.php'; 
?>

<style>
    h2 { color: #333; margin-bottom: 1rem; }
    .form-container {
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #555;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }
    .btn-update { background-color: #007bff; }
    .btn-update:hover { background-color: #0069d9; }
    .btn-back {
        background-color: #6c757d;
        margin-bottom: 20px;
    }
    .btn-back:hover { background-color: #5a6268; }
    .message {
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-weight: 500;
    }
    .message.success { background-color: #d4edda; color: #155724; }
    .message.error { background-color: #f8d7da; color: #721c24; }
    .current-image { max-width: 100px; border-radius: 5px; margin-top: 10px; }
</style>

<h2>Sửa sản phẩm: <?php echo htmlspecialchars($product['name']); ?></h2>
<p><a href="product_list.php" class="btn btn-back">Quay về danh sách</a></p>

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
            <label>Giá:</label>
            <input type="number" name="product_price" value="<?php echo $product['price']; ?>" required>
        </div>
        <div class="form-group">
            <label>Tồn kho:</label>
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
            <br><small>Để trống nếu không muốn đổi ảnh</small>
        </div>
        <div class="form-group">
            <button type="submit" name="update_product" class="btn btn-update">Cập nhật sản phẩm</button>
        </div>
    </form>
</div>

<?php
// 7. DỌN DẸP VÀ GỌI FOOTER
disconnect_db(); // Ngắt kết nối ở cuối
require '../includes/footer.php'; 
?>