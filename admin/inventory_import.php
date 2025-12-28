<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập và quyền hạn
require '../includes/header.php';     // Gọi phần đầu trang (HTML head, CSS)
require '../includes/admin_sidebar.php'; // Gọi thanh Menu bên trái

$message = ""; // Biến chứa thông báo (Thành công/Lỗi)

// =================================================================
// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM "LƯU & CẬP NHẬT" (POST)
// =================================================================
if (isset($_POST['btn_import'])) {
    
    // Lấy dữ liệu mảng từ Form (Vì nhập nhiều dòng cùng lúc)
    $products   = $_POST['product_id'];   // Mảng ID sản phẩm
    $quantities = $_POST['quantity'];     // Mảng số lượng
    $prices     = $_POST['import_price']; // Mảng giá nhập
    $note       = $_POST['note'];         // Ghi chú chung cho phiếu nhập

    // Bắt đầu Transaction (Giao dịch) để đảm bảo tính toàn vẹn dữ liệu
    // Nếu có 1 lỗi xảy ra -> Rollback (Hủy toàn bộ)
    mysqli_begin_transaction($conn);
    
    try {
        $has_item = false; // Biến cờ kiểm tra xem có dòng nào hợp lệ không

        // Duyệt qua từng dòng sản phẩm được nhập
        for ($i = 0; $i < count($products); $i++) {
            $pid   = (int)$products[$i];
            $qty   = (int)$quantities[$i];
            $price = (float)$prices[$i];

            // Chỉ xử lý nếu số lượng và ID sản phẩm hợp lệ
            if ($qty > 0 && $pid > 0) {
                $has_item = true;
                
                // 1. Cập nhật Tồn kho (Cộng thêm) & Giá vốn (Cập nhật mới) trong bảng Products
                $sql_update = "UPDATE products SET stock = stock + $qty, original_price = $price WHERE id = $pid";
                if (!mysqli_query($conn, $sql_update)) throw new Exception("Lỗi cập nhật SP ID: $pid");

                // 2. Ghi lịch sử nhập kho vào bảng Inventory History
                $sql_history = "INSERT INTO inventory_history (product_id, quantity, import_price, note) VALUES ('$pid', '$qty', '$price', '$note')";
                if (!mysqli_query($conn, $sql_history)) throw new Exception("Lỗi ghi lịch sử.");
            }
        }
        
        // Nếu có ít nhất 1 sản phẩm được nhập -> Commit (Lưu chính thức)
        if($has_item) {
            mysqli_commit($conn);
            $message = '<div class="alert success">✅ Nhập kho & Cập nhật giá vốn thành công!</div>';
        } else {
            throw new Exception("Vui lòng chọn ít nhất 1 sản phẩm.");
        }
        
    } catch (Exception $e) {
        // Nếu có lỗi -> Rollback (Hoàn tác mọi thay đổi)
        mysqli_rollback($conn);
        $message = '<div class="alert error">❌ Lỗi: ' . $e->getMessage() . '</div>';
    }
}

// =================================================================
// 3. LẤY DANH SÁCH SẢN PHẨM (ĐỂ ĐỔ VÀO SELECT BOX)
// =================================================================
$q_prods = mysqli_query($conn, "SELECT id, name, stock, original_price FROM products ORDER BY name ASC");
$prod_list = [];
while ($row = mysqli_fetch_assoc($q_prods)) {
    $prod_list[] = $row;
}
?>

<script>
    const productData = {};
    <?php foreach ($prod_list as $p): ?>
        productData[<?php echo $p['id']; ?>] = <?php echo $p['original_price'] ? $p['original_price'] : 0; ?>;
    <?php endforeach; ?>
</script>

<div class="main-with-sidebar">
    <div class="admin-wrapper"> 
        
        <div class="header-row">
            <h2 style="margin: 0; border-left-color: #28a745;">📥 Nhập Hàng & Cập Nhật Giá Vốn</h2>
            <a href="inventory_history.php" class="btn-reset" style="width: auto; padding: 0 15px; font-size: 14px;">
                Xem Lịch sử
            </a>
        </div>

        <?php echo $message; ?>

        <form method="POST" action="" id="importForm">
            <div class="card"> 
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ghi chú nhập hàng:</label>
                    <input type="text" name="note" class="form-control" style="width: 100%;" placeholder="VD: Nhập hàng ngày <?php echo date('d/m'); ?>...">
                </div>

                <table id="importTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Sản phẩm </th>
                            <th style="width: 15%;">Số lượng</th>
                            <th style="width: 35%;">Giá vốn nhập (VNĐ)</th>
                            <th style="width: 10%; text-align: center;">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td>
                                <select name="product_id[]" class="table-input" onchange="fillPrice(this)" required>
                                    <option value="">-- Chọn món --</option>
                                    <?php foreach ($prod_list as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?> (Kho: <?php echo $p['stock']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="table-input" placeholder="SL" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="import_price[]" class="table-input price-input" placeholder="Giá nhập" min="0" required>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-remove-row" onclick="removeRow(this)">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="addRow()">+ Thêm dòng</button>
                    <button type="submit" name="btn_import" class="btn-primary">💾 Lưu & Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Hàm tự động điền giá vốn khi chọn sản phẩm
    function fillPrice(selectElement) {
        const productId = selectElement.value;
        const row = selectElement.closest('tr');
        const priceInput = row.querySelector('.price-input');

        if (productData[productId]) {
            priceInput.value = productData[productId];
        } else {
            priceInput.value = 0;
        }
    }

    // Hàm thêm dòng mới vào bảng
    function addRow() {
        const table = document.getElementById('tableBody');
        const firstRow = table.rows[0];
        const newRow = firstRow.cloneNode(true); // Clone dòng đầu tiên
        
        // Reset giá trị input trong dòng mới
        const inputs = newRow.getElementsByTagName('input');
        for(let i=0; i<inputs.length; i++) { inputs[i].value = ''; }
        
        // Reset select về mặc định
        newRow.getElementsByTagName('select')[0].value = '';

        table.appendChild(newRow);
    }

    // Hàm xóa dòng
    function removeRow(btn) {
        const table = document.getElementById('tableBody');
        // Chỉ cho xóa nếu còn nhiều hơn 1 dòng
        if (table.rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            // Thông báo lỗi nếu cố xóa dòng cuối cùng
            if(typeof Swal !== 'undefined') {
                Swal.fire('Lỗi', 'Phải nhập ít nhất 1 món!', 'error');
            } else {
                alert("Phải nhập ít nhất 1 món!");
            }
        }
    }
</script>