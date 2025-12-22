<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php';

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// Biến lưu trạng thái thông báo
$toast_message = "";
$toast_type = "";

// 1. LẤY ID SẢN PHẨM & KIỂM TRA TỒN TẠI
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $sql = "SELECT * FROM products WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);

    if (!$product) {
        echo "<script>alert('Sản phẩm không tồn tại!'); window.location.href='product_list.php';</script>";
        exit();
    }
} else {
    header("Location: product_list.php");
    exit();
}

// 2. XỬ LÝ POST
if (isset($_POST['update_product'])) {
    // Lấy dữ liệu & Clean
    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $price = $_POST['price'];
    $original_price = $_POST['original_price'];
    $cost_price = isset($_POST['cost_price']) ? $_POST['cost_price'] : $original_price;
    $stock_new = $_POST['stock'];
    $description = trim($_POST['description']);

    // --- VALIDATION ---
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
        // --- NẾU KHÔNG CÓ LỖI NHẬP LIỆU ---

        // 1. Xử lý ảnh (Chỉ khi có upload mới)
        $image_update_query = "";
        $upload_ok = true;

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $toast_message = "Chỉ chấp nhận file ảnh (JPG, PNG...).";
                $toast_type = "error";
                $upload_ok = false;
            } elseif ($_FILES['image']['size'] > 5000000) {
                $toast_message = "File ảnh quá lớn (>5MB).";
                $toast_type = "error";
                $upload_ok = false;
            } else {
                $target_dir = "uploads/";
                $new_filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                    $image_update_query = ", image = '$new_filename'";
                } else {
                    $toast_message = "Lỗi khi lưu file ảnh.";
                    $toast_type = "error";
                    $upload_ok = false;
                }
            }
        }

        // 2. Update Database (Nếu ảnh OK hoặc không đổi ảnh)
        if ($upload_ok) {
            $name = mysqli_real_escape_string($conn, $name);
            $description = mysqli_real_escape_string($conn, $description);

            // GHI LỊCH SỬ KHO (Nếu số lượng thay đổi)
            $stock_old = (int) $product['stock'];
            $diff = (int) $stock_new - $stock_old;

            if ($diff != 0) {
                $note = "Cập nhật thủ công (Sửa SP)";
                // Nếu $diff > 0 là nhập, < 0 là xuất (hoặc điều chỉnh giảm)
                $sql_hist = "INSERT INTO inventory_history (product_id, quantity, note) VALUES ('$id', '$diff', '$note')";
                mysqli_query($conn, $sql_hist);
            }

            $sql_update = "UPDATE products SET name='$name', category_id='$category_id', price='$price', original_price='$original_price', cost_price='$cost_price', stock='$stock_new', description='$description' $image_update_query WHERE id=$id";

            if (mysqli_query($conn, $sql_update)) {
                $toast_message = "Cập nhật thành công!";
                $toast_type = "success";

                // Refresh dữ liệu biến $product để form hiển thị cái mới nhất
                $product['name'] = $name;
                $product['category_id'] = $category_id;
                $product['price'] = $price;
                $product['original_price'] = $original_price;
                $product['cost_price'] = $cost_price;
                $product['stock'] = $stock_new;
                $product['description'] = $description;
                if (!empty($image_update_query))
                    $product['image'] = $new_filename;
            } else {
                $toast_message = "Lỗi SQL: " . mysqli_error($conn);
                $toast_type = "error";
            }
        }
    }
}
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        max-width: 800px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: bold;
        margin-bottom: 8px;
        display: block;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
    }

    .form-control:focus {
        border-color: #28a745;
    }

    .btn-submit {
        background: #28a745;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        text-decoration: none;
        margin-left: 10px;
    }

    .form-row {
        display: flex;
        gap: 20px;
    }

    .col {
        flex: 1;
    }

    .current-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-top: 10px;
    }

    /* TOAST NOTIFICATION STYLES */
    #toast-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast {
        background: #333;
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s ease;
        border-left: 5px solid transparent;
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast.success {
        background: white;
        color: #333;
        border-left-color: #28a745;
    }

    .toast.error {
        background: white;
        color: #333;
        border-left-color: #dc3545;
    }

    .toast-icon {
        font-size: 18px;
    }
</style>

<h2 class="title-product">Sửa sản phẩm: <?php echo htmlspecialchars($product['name']); ?></h2>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Tên sản phẩm</label>
            <input type="text" name="name" class="form-control"
                value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Danh mục</label>
            <select name="category_id" class="form-control">
                <?php
                $cat_res = mysqli_query($conn, "SELECT * FROM categories");
                while ($cat = mysqli_fetch_assoc($cat_res)) {
                    $selected = ($cat['id'] == $product['category_id']) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-row">
            <div class="col form-group">
                <label class="form-label">Giá bán</label>
                <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" required
                    min="0">
            </div>
            <div class="col form-group">
                <label class="form-label">Giá gốc (hiển thị)</label>
                <input type="number" name="original_price" class="form-control"
                    value="<?php echo isset($product['original_price']) ? $product['original_price'] : 0; ?>" min="0">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Giá vốn (Cost Price) (*)</label>
            <input type="number" name="cost_price" class="form-control"
                value="<?php echo isset($product['cost_price']) ? $product['cost_price'] : 0; ?>" required min="0"
                step="0.01">
            <small style="color:#888">Giá vốn thực tế để tính lợi nhuận</small>
        </div>

        <div class="form-row">
            <div class="col form-group">
                <label class="form-label">Tồn kho</label>
                <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" required
                    min="0">
                <small style="color: #d63384;">* Thay đổi số lượng tại đây sẽ được ghi vào lịch sử kho.</small>
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
            <textarea name="description" class="form-control"
                rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" name="update_product" class="btn-submit">Lưu thay đổi</button>
            <a href="product_list.php" class="btn-cancel">Quay lại</a>
        </div>
    </form>
</div>

<div id="toast-container"></div>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');

        let icon = type === 'success' ? '✅' : '⚠️';
        let colorClass = type;

        toast.className = `toast ${colorClass}`;
        toast.innerHTML = `<span class="toast-icon">${icon}</span> <span>${message}</span>`;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    <?php if (!empty($toast_message)): ?>
        showToast("<?php echo $toast_message; ?>", "<?php echo $toast_type; ?>");
    <?php endif; ?>
</script>

<?php
echo '</div></div>';
?>