<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập và quyền hạn
require '../includes/header.php';     // Gọi phần đầu trang (HTML head, CSS)
require '../includes/admin_sidebar.php'; // Gọi thanh Menu bên trái

echo '<div class="main-with-sidebar">'; // Mở khung nội dung chính
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// =================================================================
// 2. KHỞI TẠO BIẾN
// =================================================================
$error_msg = "";   // Biến chứa nội dung lỗi
$success_msg = ""; // Biến chứa nội dung thành công

// Biến lưu giữ giá trị cũ (để điền lại form nếu người dùng nhập sai)
$old_name = ""; $old_price = ""; $old_original = ""; $old_stock = ""; $old_desc = "";

// =================================================================
// 3. XỬ LÝ KHI NGƯỜI DÙNG BẤM "THÊM SẢN PHẨM" (POST)
// =================================================================
if (isset($_POST['add_product'])) {
    
    // --- A. LẤY DỮ LIỆU TỪ FORM ---
    $name = trim($_POST['name']);           // Tên sản phẩm
    $category_id = (int)$_POST['category_id']; // Danh mục
    $price = $_POST['price'];               // Giá bán
    $original_price = $_POST['original_price']; // Giá vốn
    $stock = $_POST['stock'];               // Tồn kho ban đầu
    $description = trim($_POST['description']); // Mô tả

    // Lưu lại giá trị cũ để hiển thị lại nếu có lỗi
    $old_name = $name; $old_price = $price; $old_original = $original_price; $old_stock = $stock; $old_desc = $description;

    // --- B. VALIDATION (KIỂM TRA DỮ LIỆU ĐẦU VÀO) ---
    if (empty($name)) {
        $error_msg = "Vui lòng nhập tên sản phẩm.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_msg = "Giá bán phải là số và lớn hơn 0.";
    } elseif (!is_numeric($original_price) || $original_price < 0) {
        $error_msg = "Giá vốn không hợp lệ (phải >= 0).";
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error_msg = "Tồn kho phải là số và không âm.";
    } elseif (empty($_FILES['image']['name'])) {
        $error_msg = "Vui lòng chọn ảnh sản phẩm.";
    } else {
        
        // --- C. KIỂM TRA FILE ẢNH ---
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Check đuôi file
        if (!in_array($ext, $allowed)) {
            $error_msg = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).";
        } 
        // Check dung lượng (Max 5MB)
        elseif ($_FILES['image']['size'] > 5000000) { 
            $error_msg = "File ảnh quá lớn (Max 5MB).";
        } else {
            
            // --- D. XỬ LÝ UPLOAD & INSERT ---
            $target_dir = "uploads/";
            // Tạo tên file mới ngẫu nhiên để tránh trùng
            $new_filename = uniqid() . '.' . $ext;
            
            // Di chuyển file từ thư mục tạm vào thư mục uploads
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                
                // Clean dữ liệu text trước khi đưa vào SQL
                $name = mysqli_real_escape_string($conn, $name);
                $desc = mysqli_real_escape_string($conn, $description);
                
                // Câu lệnh SQL thêm sản phẩm
                $sql = "INSERT INTO products (name, category_id, price, original_price, stock, image, description) 
                        VALUES ('$name', '$category_id', '$price', '$original_price', '$stock', '$new_filename', '$desc')";
                
                if (mysqli_query($conn, $sql)) {
                    
                    // [QUAN TRỌNG] Ghi log nhập kho lần đầu tiên
                    $last_id = mysqli_insert_id($conn); // Lấy ID sản phẩm vừa tạo
                    mysqli_query($conn, "INSERT INTO inventory_history (product_id, quantity, note) VALUES ('$last_id', '$stock', 'Khởi tạo sản phẩm mới')");

                    $success_msg = "Thêm sản phẩm thành công!";
                    
                    // Reset form về rỗng để nhập tiếp
                    $old_name = ""; $old_price = ""; $old_original = ""; $old_stock = ""; $old_desc = "";
                } else {
                    $error_msg = "Lỗi SQL: " . mysqli_error($conn); // Báo lỗi nếu SQL sai
                }
            } else {
                $error_msg = "Lỗi khi tải ảnh lên server."; // Báo lỗi nếu không move được file
            }
        }
    }
}
?>

<div class="header-row">
    <h2 class="title-product" style="margin: 0;">Thêm sản phẩm mới</h2>
    <a href="product_list.php" class="btn-back">← Quay lại danh sách</a>
</div>

<?php if($error_msg): ?>
    <div class="alert error">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>

<?php if($success_msg): ?>
    <div class="alert success">✅ <?php echo $success_msg; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label class="form-label">Tên sản phẩm (*)</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($old_name); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Danh mục (*)</label>
            <select name="category_id" class="form-control">
                <?php
                $cat_res = mysqli_query($conn, "SELECT * FROM categories");
                while ($cat = mysqli_fetch_assoc($cat_res)) {
                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-row">
            <div class="col form-group">
                <label class="form-label">Giá bán (*)</label>
                <input type="number" name="price" class="form-control" value="<?php echo $old_price; ?>" required min="0">
            </div>
            <div class="col form-group">
                <label class="form-label">Giá vốn (*)</label>
                <input type="number" name="original_price" class="form-control" value="<?php echo $old_original; ?>" required min="0">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Số lượng nhập ban đầu (*)</label>
            <input type="number" name="stock" class="form-control" value="<?php echo $old_stock; ?>" required min="0">
        </div>

        <div class="form-group">
            <label class="form-label">Hình ảnh (*)</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
            <small style="color:#888">Chấp nhận: jpg, png, webp (Max 5MB)</small>
        </div>

        <div class="form-group">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($old_desc); ?></textarea>
        </div>

        <button type="submit" name="add_product" class="btn-submit">Thêm sản phẩm</button>
        <a href="product_list.php" class="btn-cancel">Hủy bỏ</a>

    </form>
</div>

<?php 
// Đóng các thẻ div wrapper
echo '</div></div>'; 
?>