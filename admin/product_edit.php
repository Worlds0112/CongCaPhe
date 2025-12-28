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
$toast_message = ""; // Nội dung thông báo
$toast_type = "";    // Loại thông báo (success/error)

// =================================================================
// 3. LẤY DỮ LIỆU SẢN PHẨM CŨ (ĐỂ HIỂN THỊ LÊN FORM)
// =================================================================
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Truy vấn thông tin sản phẩm theo ID
    $sql = "SELECT * FROM products WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);

    // Nếu không tìm thấy sản phẩm -> Chuyển hướng về danh sách
    if (!$product) {
        echo "<script>alert('Sản phẩm không tồn tại!'); window.location.href='product_list.php';</script>";
        exit();
    }
} else {
    // Nếu không có ID trên URL -> Chuyển hướng về danh sách
    header("Location: product_list.php");
    exit();
}

// =================================================================
// 4. XỬ LÝ KHI NGƯỜI DÙNG BẤM "LƯU THAY ĐỔI" (POST)
// =================================================================
if (isset($_POST['update_product'])) {
    
    // --- A. LẤY DỮ LIỆU TỪ FORM ---
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = $_POST['price'];
    $original_price = $_POST['original_price']; 
    $stock_new = $_POST['stock']; 
    $description = trim($_POST['description']);

    // --- B. VALIDATION (KIỂM TRA DỮ LIỆU) ---
    if (empty($name)) {
        $toast_message = "Tên sản phẩm không được để trống.";
        $toast_type = "error";
    } elseif (!is_numeric($price) || $price <= 0) {
        $toast_message = "Giá bán không hợp lệ.";
        $toast_type = "error";
    } elseif (!is_numeric($stock_new) || $stock_new < 0) {
        $toast_message = "Tồn kho không được âm.";
        $toast_type = "error";
    } else {
        
        // --- C. XỬ LÝ ẢNH (NẾU CÓ UPLOAD MỚI) ---
        $image_update_query = ""; // Chuỗi query cập nhật ảnh (mặc định rỗng)
        $upload_ok = true;        // Cờ kiểm tra upload

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            // Kiểm tra định dạng ảnh
            if (!in_array($ext, $allowed)) {
                $toast_message = "Chỉ chấp nhận file ảnh (JPG, PNG...).";
                $toast_type = "error";
                $upload_ok = false;
            } 
            // Kiểm tra dung lượng ảnh (Max 5MB)
            elseif ($_FILES['image']['size'] > 5000000) {
                $toast_message = "File ảnh quá lớn (>5MB).";
                $toast_type = "error";
                $upload_ok = false;
            } else {
                // Upload ảnh
                $target_dir = "uploads/";
                $new_filename = uniqid() . '.' . $ext; // Đổi tên file ngẫu nhiên
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                    // Nếu upload thành công -> Tạo chuỗi query cập nhật ảnh
                    $image_update_query = ", image = '$new_filename'";
                } else {
                    $toast_message = "Lỗi khi lưu file ảnh.";
                    $toast_type = "error";
                    $upload_ok = false;
                }
            }
        }

        // --- D. CẬP NHẬT DATABASE (NẾU KHÔNG CÓ LỖI ẢNH) ---
        if ($upload_ok) {
            $name = mysqli_real_escape_string($conn, $name);
            $description = mysqli_real_escape_string($conn, $description);

            // [QUAN TRỌNG] GHI LỊCH SỬ KHO NẾU SỐ LƯỢNG THAY ĐỔI
            $stock_old = (int)$product['stock'];
            $diff = (int)$stock_new - $stock_old; // Chênh lệch (Mới - Cũ)

            if ($diff != 0) {
                $note = "Cập nhật thủ công (Sửa SP)";
                // Thêm vào bảng lịch sử kho
                $sql_hist = "INSERT INTO inventory_history (product_id, quantity, note) VALUES ('$id', '$diff', '$note')";
                mysqli_query($conn, $sql_hist);
            }

            // Câu lệnh SQL cập nhật sản phẩm
            $sql_update = "UPDATE products 
                           SET name='$name', category_id='$category_id', price='$price', 
                               original_price='$original_price', stock='$stock_new', 
                               description='$description' 
                               $image_update_query 
                           WHERE id=$id";

            if (mysqli_query($conn, $sql_update)) {
                $toast_message = "Cập nhật thành công!";
                $toast_type = "success";
                
                // Cập nhật lại biến $product để hiển thị dữ liệu mới nhất lên form ngay lập tức
                $product['name'] = $name;
                $product['category_id'] = $category_id;
                $product['price'] = $price;
                $product['original_price'] = $original_price;
                $product['stock'] = $stock_new;
                $product['description'] = $description;
                if(!empty($image_update_query)) $product['image'] = $new_filename;
            } else {
                $toast_message = "Lỗi SQL: " . mysqli_error($conn);
                $toast_type = "error";
            }
        }
    }
}
?>

<div class="header-row">
        <h2 class="title-product" style="margin: 0;">Sửa sản phẩm: <?php echo htmlspecialchars($product['name']); ?></h2>
        <a href="product_list.php" class="btn-back">← Quay lại danh sách</a>
    </div>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label class="form-label">Tên sản phẩm</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-control">
                    <?php
                    // Lấy danh sách danh mục để đổ vào select box
                    $cat_res = mysqli_query($conn, "SELECT * FROM categories");
                    while ($cat = mysqli_fetch_assoc($cat_res)) {
                        // Kiểm tra nếu ID danh mục trùng với sản phẩm thì chọn (selected)
                        $selected = ($cat['id'] == $product['category_id']) ? 'selected' : '';
                        echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-row">
                <div class="col form-group">
                    <label class="form-label">Giá bán</label>
                    <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" required min="0">
                </div>
                <div class="col form-group">
                    <label class="form-label">Giá vốn</label>
                    <input type="number" name="original_price" class="form-control" value="<?php echo isset($product['original_price']) ? $product['original_price'] : 0; ?>" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="col form-group">
                    <label class="form-label">Tồn kho</label>
                    <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" required min="0">
                    <div class="text-hint" style="color: #d63384;">* Thay đổi số lượng tại đây sẽ được ghi vào lịch sử kho.</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Hình ảnh (Chỉ chọn nếu muốn thay đổi)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($product['image'])): ?>
                    <img src="uploads/<?php echo $product['image']; ?>" class="current-img" alt="Img">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="update_product" class="btn-save">Lưu thay đổi</button>
            </div>
        </form>
    </div>

    <div id="toast-container"></div>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Icon và màu sắc dựa trên loại thông báo
            let icon = type === 'success' ? '✅' : '⚠️';
            let colorClass = type; // class 'success' hoặc 'error' trong CSS

            toast.className = `toast ${colorClass}`;
            toast.innerHTML = `<span class="toast-icon">${icon}</span> <span>${message}</span>`;
            
            container.appendChild(toast);
            
            // Hiệu ứng hiện lên
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Tự động ẩn sau 3 giây
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400); // Xóa khỏi DOM sau khi ẩn
            }, 3000);
        }

        // Nếu có thông báo từ PHP -> Gọi hàm JS để hiển thị
        <?php if (!empty($toast_message)): ?>
            showToast("<?php echo $toast_message; ?>", "<?php echo $toast_type; ?>");
        <?php endif; ?>
    </script>

<?php 
// Đóng các thẻ div wrapper
echo '</div></div>'; 
?>