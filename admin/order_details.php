<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 
echo '<div class="main-with-sidebar">';

// 2. LẤY ID HÓA ĐƠN TỪ URL
$order_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo "<div class='admin-wrapper'><h1>ID hóa đơn không hợp lệ.</h1></div>";
    require '../includes/footer.php';
    disconnect_db();
    exit();
}

// 3. LẤY THÔNG TIN CHUNG CỦA HÓA ĐƠN
$sql_order = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
              FROM orders
              JOIN users ON orders.user_id = users.id
              WHERE orders.id = ?";
$stmt_order = mysqli_prepare($conn, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);
$order_info = mysqli_fetch_assoc($result_order);
mysqli_stmt_close($stmt_order);

// 4. LẤY CÁC MÓN HÀNG TRONG HÓA ĐƠN
$sql_details = "SELECT products.name, products.image, order_details.quantity, order_details.price
                FROM order_details
                JOIN products ON order_details.product_id = products.id
                WHERE order_details.order_id = ?";
$stmt_details = mysqli_prepare($conn, $sql_details);
mysqli_stmt_bind_param($stmt_details, "i", $order_id);
mysqli_stmt_execute($stmt_details);
$result_details = mysqli_stmt_get_result($stmt_details);

?>

<style>
    /* Wrapper căn giữa giống các trang khác */
    .admin-wrapper {
        max-width: 1000px; /* Nhỏ hơn chút cho gọn */
        margin: 0 auto;
        padding: 30px 20px;
    }

    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #17a2b8; padding-left: 15px; }
    
    /* Nút quay lại */
    .btn-back {
        display: inline-block;
        background-color: #6c757d;
        color: white;
        padding: 8px 15px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        margin-bottom: 20px;
        font-size: 14px;
    }
    .btn-back:hover { background-color: #5a6268; }

    .order-summary {
        background: #fff; padding: 25px; border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); margin-bottom: 30px;
        border: 1px solid #eee;
    }
    .order-summary p { font-size: 16px; line-height: 1.8; margin: 0; color: #555; }
    .order-summary strong { color: #333; min-width: 100px; display: inline-block; }
    
    h3 { margin-bottom: 15px; color: #444; }

    table { 
        width: 100%; border-collapse: collapse; background-color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border-radius: 10px; overflow: hidden;
    }
    th, td { 
        border-bottom: 1px solid #eee; padding: 15px; text-align: left; vertical-align: middle;
    }
    th { background-color: #f8f9fa; font-weight: 700; color: #555; text-transform: uppercase; font-size: 13px; }
    tr:last-child td { border-bottom: none; }
    
    img { 
        width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;
    }
    
    .total-row td {
        background-color: #f9f9f9;
        font-weight: bold;
        font-size: 18px;
        color: #d32f2f;
        padding-top: 20px;
        padding-bottom: 20px;
    }
</style>

<div class="admin-wrapper">

    <a href="order_list.php" class="btn-back">← Quay lại danh sách hóa đơn</a>

    <?php if ($order_info): ?>
        <h2>Chi tiết Hóa đơn: #<?php echo $order_info['id']; ?></h2>

        <div class="order-summary">
            <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($order_info['order_date'])); ?></p>
            <p><strong>Nhân viên:</strong> <?php echo htmlspecialchars($order_info['full_name']); ?></p>
        </div>

        <h3>Danh sách món đã mua</h3>
        <table>
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_details && mysqli_num_rows($result_details) > 0) {
                    while ($item = mysqli_fetch_assoc($result_details)) {
                ?>
                    <tr>
                        <td>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Img">
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>x<?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price']); ?> ₫</td>
                        <td><?php echo number_format($item['price'] * $item['quantity']); ?> ₫</td>
                    </tr>
                <?php
                    } // Kết thúc vòng lặp
                }
                ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right; color: #333;">TỔNG CỘNG:</td>
                    <td><?php echo number_format($order_info['total_amount']); ?> ₫</td>
                </tr>
            </tbody>
        </table>

    <?php else: ?>
        <h2>Không tìm thấy hóa đơn này.</h2>
    <?php endif; ?>

</div> <?php
// DỌN DẸP VÀ GỌI FOOTER
if ($result_details) {
    mysqli_free_result($result_details);
}
mysqli_stmt_close($stmt_details);
disconnect_db();
echo '</div>';
?>