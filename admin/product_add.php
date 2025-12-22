<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php';

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

$error_msg = ""; // Biến chứa lỗi
$success_msg = "";

// Biến giữ lại giá trị cũ khi lỗi (để người dùng không phải nhập lại từ đầu)
$old_name = "";
$old_price = "";
$old_original = "";
$old_stock = "";
$old_desc = "";

if (isset($_POST['add_product'])) {
    // 1. LẤY DỮ LIỆU VÀ CLEAN
    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $price = $_POST['price'];
    $original_price = $_POST['original_price'];
    $cost_price = isset($_POST['cost_price']) ? $_POST['cost_price'] : $_POST['original_price'];
    $stock = $_POST['stock'];
    $description = trim($_POST['description']);

    // Giữ lại giá trị cũ
    $old_name = $name;
    $old_price = $price;
    $old_original = $original_price;
    $old_cost = $cost_price;
    $old_stock = $stock;
    $old_desc = $description;

    // 2. BẮT LỖI (VALIDATION)
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
        // Kiểm tra file ảnh
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error_msg = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).";
        } elseif ($_FILES['image']['size'] > 5000000) { // 5MB
            $error_msg = "File ảnh quá lớn (Max 5MB).";
        } else {
            // 3. XỬ LÝ KHI KHÔNG CÓ LỖI
            $target_dir = "uploads/";
            $new_filename = uniqid() . '.' . $ext;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename)) {
                // Insert SQL
                $name = mysqli_real_escape_string($conn, $name);
                $desc = mysqli_real_escape_string($conn, $description);

                $sql = "INSERT INTO products (name, category_id, price, original_price, cost_price, stock, image, description) 
                        VALUES ('$name', '$category_id', '$price', '$original_price', '$cost_price', '$stock', '$new_filename', '$desc')";

                if (mysqli_query($conn, $sql)) {
                    // Ghi log nhập kho lần đầu
                    $last_id = mysqli_insert_id($conn);
                    mysqli_query($conn, "INSERT INTO inventory_history (product_id, quantity, note) VALUES ('$last_id', '$stock', 'Khởi tạo sản phẩm mới')");

                    $success_msg = "Thêm sản phẩm thành công!";
                    // Reset form
                    $old_name = "";
                    $old_price = "";
                    $old_original = "";
                    $old_cost = "";
                    $old_stock = "";
                    $old_desc = "";
                } else {
                    $error_msg = "Lỗi SQL: " . mysqli_error($conn);
                }
            } else {
                $error_msg = "Lỗi khi tải ảnh lên server.";
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

    /* Style thông báo lỗi */
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .form-row {
        display: flex;
        gap: 20px;
    }

    .col {
        flex: 1;
    }
</style>

<h2 class="title-product">Thêm sản phẩm mới</h2>

<?php if ($error_msg): ?>
    <div class="alert alert-danger">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if ($success_msg): ?>
    <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label class="form-label">Tên sản phẩm (*)</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($old_name); ?>"
                required>
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
                <input type="number" name="price" class="form-control" value="<?php echo $old_price; ?>" required
                    min="0">
            </div>
            <div class="col form-group">
                <label class="form-label">Giá gốc (hiển thị)</label>
                <input type="number" name="original_price" class="form-control" value="<?php echo $old_original; ?>"
                    min="0">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Giá vốn (Cost Price) (*)</label>
            <input type="number" name="cost_price" class="form-control"
                value="<?php echo isset($old_cost) ? $old_cost : ''; ?>" required min="0" step="0.01">
            <small style="color:#888">Giá vốn thực tế để tính lợi nhuận</small>
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
            <textarea name="description" class="form-control"
                rows="3"><?php echo htmlspecialchars($old_desc); ?></textarea>
        </div>

        <button type="submit" name="add_product" class="btn-submit">Thêm sản phẩm</button>
        <a href="product_list.php" style="margin-left: 10px; text-decoration: none; color: #666;">Hủy bỏ</a>

    </form>
</div>

<?php
echo '</div></div>';
?>