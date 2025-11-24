<?php
// 1. BẢO VỆ TRANG (File này tự động session_start, check admin, và connect_db)
require '../includes/auth_admin.php';

$message = ""; // Biến để lưu thông báo

// 2. XỬ LÝ POST (NẾU CÓ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    
    // $conn đã có từ auth_admin.php (KHÔNG CẦN connect_db() nữa)
    
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = (int)$_POST['product_price'];
    $stock = (int)$_POST['product_stock'];
    $category_id = (int)$_POST['category_id'];
    
    $image_name = "default.jpg"; 

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        // Đường dẫn uploads nằm trong admin/, nên 'uploads/' là đúng
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $original_name = basename($_FILES["product_image"]["name"]);
        $image_name = time() . "_" . str_replace(" ", "-", $original_name);
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $message = "Lỗi khi tải file lên. Sử dụng ảnh mặc định.";
            $image_name = "default.jpg";
        }
    }

    $sql = "INSERT INTO products (category_id, name, price, stock, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isiss", $category_id, $name, $price, $stock, $image_name);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Thêm sản phẩm mới thành công!";
        } else {
            $message = "Lỗi khi thêm sản phẩm: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn);
    }
    
    // Không ngắt kết nối ở đây, để ở cuối file
}

// 3. GỌI HEADER CHUNG
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
    .btn-add { background-color: #28a745; }
    .btn-add:hover { background-color: #218838; }
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
</style>

<h2>Thêm sản phẩm mới</h2>
<p><a href="product_list.php" class="btn btn-back">Quay về danh sách</a></p>

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
            <input type="text" name="product_name" required>
        </div>
        <div class="form-group">
            <label>Giá:</label>
            <input type="number" name="product_price" required>
        </div>
        <div class="form-group">
            <label>Tồn kho:</label>
            <input type="number" name="product_stock" required>
        </div>
        <div class="form-group">
            <label>Danh mục (ID):</label>
            <input type="number" name="category_id" required placeholder="Ví dụ: 3 (cho Cà phê)">
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

<?php
// 6. DỌN DẸP VÀ GỌI FOOTER
disconnect_db(); // Ngắt kết nối ở cuối
require '../includes/footer.php'; 
?>