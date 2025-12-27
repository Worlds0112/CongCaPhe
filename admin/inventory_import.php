<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php';

$message = "";

// --- XỬ LÝ KHI BẤM NÚT "LƯU NHẬP KHO" ---
if (isset($_POST['btn_import'])) {
    $products = $_POST['product_id'];       // Mảng ID món
    $quantities = $_POST['quantity'];       // Mảng số lượng
    $prices = $_POST['import_price'];       // Mảng giá nhập mới
    $note = $_POST['note'];                 

    mysqli_begin_transaction($conn);
    try {
        for ($i = 0; $i < count($products); $i++) {
            $pid = (int)$products[$i];
            $qty = (int)$quantities[$i];
            $price = (float)$prices[$i]; // Giá vốn nhập vào lần này

            if ($qty > 0 && $pid > 0) {
                // 1. CẬP NHẬT KHO & GIÁ VỐN MỚI (original_price)
                // Code này sẽ cập nhật giá vốn mới nhất vào bảng products
                $sql_update = "UPDATE products 
                               SET stock = stock + $qty, 
                                   original_price = $price 
                               WHERE id = $pid";
                
                if (!mysqli_query($conn, $sql_update)) {
                    throw new Exception("Lỗi cập nhật sản phẩm ID: $pid");
                }

                // 2. GHI LỊCH SỬ (Để sau này tính tổng chi tiêu)
                $sql_history = "INSERT INTO inventory_history (product_id, quantity, import_price, note) 
                                VALUES ('$pid', '$qty', '$price', '$note')";
                
                if (!mysqli_query($conn, $sql_history)) {
                    throw new Exception("Lỗi ghi lịch sử.");
                }
            }
        }
        mysqli_commit($conn);
        $message = '<div class="alert success">✅ Nhập kho & Cập nhật giá vốn thành công!</div>';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = '<div class="alert error">❌ Lỗi: ' . $e->getMessage() . '</div>';
    }
}

// Lấy danh sách sản phẩm (Lấy cả original_price cũ để hiển thị gợi ý)
$q_prods = mysqli_query($conn, "SELECT id, name, stock, original_price FROM products ORDER BY name ASC");
$prod_list = [];
while ($row = mysqli_fetch_assoc($q_prods)) {
    $prod_list[] = $row;
}
?>

<script>
    // Tạo biến JS chứa thông tin giá vốn hiện tại của từng món
    const productData = {};
    <?php foreach ($prod_list as $p): ?>
        productData[<?php echo $p['id']; ?>] = <?php echo $p['original_price'] ? $p['original_price'] : 0; ?>;
    <?php endforeach; ?>
</script>

<div class="main-with-sidebar">
    <div class="content-wrapper">
        <h2 class="page-title">📥 Nhập Hàng & Cập Nhật Giá Vốn</h2>
        <?php echo $message; ?>

        <form method="POST" action="" id="importForm">
            <div class="card">
                <div class="form-group">
                    <label>Ghi chú nhập hàng:</label>
                    <input type="text" name="note" class="form-control" placeholder="VD: Nhập hàng ngày 25/12...">
                </div>

                <table class="table" id="importTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Sản phẩm</th>
                            <th style="width: 20%;">Số lượng</th>
                            <th style="width: 30%;">Giá vốn nhập vào (VNĐ)</th>
                            <th style="width: 10%;">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control" onchange="fillPrice(this)" required>
                                    <option value="">-- Chọn món --</option>
                                    <?php foreach ($prod_list as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?> (Kho: <?php echo $p['stock']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control" placeholder="SL" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="import_price[]" class="form-control price-input" placeholder="Giá nhập" min="0" required>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-del" onclick="removeRow(this)">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="button" class="btn-secondary" onclick="addRow()">+ Thêm dòng</button>
                    <button type="submit" name="btn_import" class="btn-primary">💾 Lưu & Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .content-wrapper { max-width: 900px; margin: 0 auto; padding: 20px; }
    .page-title { color: #5B743A; border-bottom: 2px solid #5B743A; padding-bottom: 10px; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th { background: #f4f4f4; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
    .table td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .btn-primary { background: #5B743A; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-secondary { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    .btn-del { background: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
</style>

<script>
    // Hàm tự động điền giá vốn cũ khi chọn món
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

    function addRow() {
        const table = document.getElementById('tableBody');
        const firstRow = table.rows[0];
        const newRow = firstRow.cloneNode(true);
        
        // Reset giá trị input
        const inputs = newRow.getElementsByTagName('input');
        for(let i=0; i<inputs.length; i++) { inputs[i].value = ''; }
        
        // Reset select
        newRow.getElementsByTagName('select')[0].value = '';

        table.appendChild(newRow);
    }

    function removeRow(btn) {
        const table = document.getElementById('tableBody');
        if (table.rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert("Phải nhập ít nhất 1 món!");
        }
    }
</script>
