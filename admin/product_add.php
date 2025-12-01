<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php';

$message = ""; 

// 2. XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = (int)$_POST['product_price'];
    $stock = (int)$_POST['product_stock'];
    $category_id = (int)$_POST['category_id'];
    
    $image_name = "default.jpg"; 

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $original_name = basename($_FILES["product_image"]["name"]);
        $image_name = time() . "_" . str_replace(" ", "-", $original_name);
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $message = "Lỗi upload ảnh. Dùng ảnh mặc định.";
            $image_name = "default.jpg";
        }
    }

    $sql = "INSERT INTO products (category_id, name, price, stock, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isiss", $category_id, $name, $price, $stock, $image_name);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Thêm thành công: " . $name;
        } else {
            $message = "Lỗi SQL: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Lỗi chuẩn bị: " . mysqli_error($conn);
    }
}

// 3. GỌI HEADER
require '../includes/header.php'; 
?>

<style>
    /* Khung bao quanh toàn bộ (Giống trang danh sách) */
    .admin-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    h2 { 
        color: #333; 
        margin-bottom: 1.5rem;
        /* Thêm đường viền xanh lá cho đồng bộ */
        border-left: 5px solid #28a745; 
        padding-left: 15px;
    }

    /* Khung Form: Căn giữa và giới hạn chiều rộng */
    .form-container {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        max-width: 700px; /* Chỉ rộng tối đa 700px */
        margin: 0 auto;   /* Căn giữa khung form */
    }

    .form-group { margin-bottom: 1.5rem; }
    
    .form-group label {
        display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="file"] {
        width: 100%; padding: 12px;
        border: 1px solid #ddd; border-radius: 6px; font-size: 16px;
        box-sizing: border-box;
    }
    
    /* Nút bấm */
    .btn {
        display: inline-block; padding: 10px 20px;
        text-decoration: none; border-radius: 6px; font-weight: bold;
        color: white; border: none; cursor: pointer; font-size: 16px;
        transition: background 0.2s;
    }
    .btn-add { background-color: #28a745; width: 100%; } /* Nút Lưu full width */
    .btn-add:hover { background-color: #218838; }
    
    .btn-back { background-color: #6c757d; font-size: 14px; margin-bottom: 20px;}
    .btn-back:hover { background-color: #5a6268; }

    .message {
        padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;
        max-width: 700px; margin-left: auto; margin-right: auto; /* Căn giữa thông báo */
    }
    .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div class="admin-wrapper">

    <h2>Thêm sản phẩm mới</h2>
    <p><a href="product_list.php" class="btn btn-back">← Quay về danh sách</a></p>

    <?php
    if ($message != "") {
        $msg_class = (strpos($message, 'Lỗi') !== false) ? 'error' : 'success';
        echo "<div class='message $msg_class'>" . htmlspecialchars($message) . "</div>";
    }
    ?>

    <div class="form-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tên sản phẩm:</label>
                <input type="text" name="product_name" required placeholder="Ví dụ: Cà phê sữa đá">
            </div>
            <div class="form-group">
                <label>Giá bán (VNĐ):</label>
                <input type="number" name="product_price" required placeholder="Ví dụ: 35000">
            </div>
            <div class="form-group">
                <label>Số lượng tồn kho:</label>
                <input type="number" name="product_stock" required placeholder="Ví dụ: 100">
            </div>
            <div class="form-group">
                <label>Danh mục (ID):</label>
                <input type="number" name="category_id" required placeholder="Nhập ID danh mục (vd: 1, 2, 3)">
            </div>
            <div class="form-group">
                <label>Ảnh sản phẩm:</label>
                <input type="file" name="product_image" accept="image/*">
            </div>
            <div class="form-group">
                <button type="submit" name="save_product" class="btn btn-add">Lưu sản phẩm</button>
            </div>
        </form>
    </div>

</div>

<?php
disconnect_db(); 
require '../includes/footer.php'; 
?>