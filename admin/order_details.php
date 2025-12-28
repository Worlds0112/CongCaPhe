<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập và quyền hạn
require '../includes/header.php';     // Gọi phần đầu trang (HTML head, CSS)
require '../includes/admin_sidebar.php'; // Gọi thanh Menu bên trái

echo '<div class="main-with-sidebar">'; // Mở khung nội dung chính

// =================================================================
// 2. LẤY ID HÓA ĐƠN TỪ URL
// =================================================================
$order_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

// Nếu ID không hợp lệ thì báo lỗi và dừng lại
if ($order_id <= 0) {
    echo "<div class='admin-wrapper'><div class='alert error'>ID hóa đơn không hợp lệ.</div></div>";
    require '../includes/footer.php';
    disconnect_db();
    exit();
}

// =================================================================
// 3. LẤY THÔNG TIN CHUNG CỦA HÓA ĐƠN (QUERY 1)
// =================================================================
$sql_order = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
              FROM orders
              JOIN users ON orders.user_id = users.id
              WHERE orders.id = ?";
// Sử dụng Prepared Statement để tránh SQL Injection
$stmt_order = mysqli_prepare($conn, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);
$order_info = mysqli_fetch_assoc($result_order);
mysqli_stmt_close($stmt_order);

// =================================================================
// 4. LẤY CHI TIẾT SẢN PHẨM TRONG HÓA ĐƠN (QUERY 2)
// =================================================================
$sql_details = "SELECT products.name, products.image, products.original_price, 
                       order_details.quantity, order_details.price
                FROM order_details
                JOIN products ON order_details.product_id = products.id
                WHERE order_details.order_id = ?";
$stmt_details = mysqli_prepare($conn, $sql_details);
mysqli_stmt_bind_param($stmt_details, "i", $order_id);
mysqli_stmt_execute($stmt_details);
$result_details = mysqli_stmt_get_result($stmt_details);

// =================================================================
// 5. TÍNH TOÁN SỐ LIỆU (DOANH THU, VỐN, LÃI)
// =================================================================
$items = [];
$total_revenue = 0; // Tổng tiền khách trả
$total_cost = 0;    // Tổng tiền vốn

if ($result_details) {
    while ($row = mysqli_fetch_assoc($result_details)) {
        // Tính toán cho từng dòng sản phẩm
        $row['line_revenue'] = $row['price'] * $row['quantity']; // Giá bán x Số lượng
        $row['line_cost']    = $row['original_price'] * $row['quantity']; // Giá vốn x Số lượng
        $row['line_profit']  = $row['line_revenue'] - $row['line_cost']; // Lợi nhuận
        
        // Cộng dồn vào tổng chung
        $total_revenue += $row['line_revenue'];
        $total_cost    += $row['line_cost'];
        
        $items[] = $row; // Lưu vào mảng để hiển thị sau
    }
}
$total_profit = $total_revenue - $total_cost; // Lợi nhuận tổng của đơn hàng
?>

<div class="admin-wrapper">

    <div class="header-row">
        <a href="order_list.php" class="btn-back">← Quay lại danh sách</a>
        
        <a href="../excel/export_order_excel.php?id=<?php echo $order_id; ?>" class="btn-excel" target="_blank">
            📥 Xuất Hóa Đơn Excel
        </a>
    </div>

    <?php if ($order_info): ?>
        <div class="header-row">
            <h2 class="title-order" style="margin-bottom: 0;">Chi tiết Hóa đơn: #<?php echo $order_info['id']; ?></h2>
            <div class="text-muted" style="font-style:italic;">
                Ngày tạo: <strong><?php echo date('d/m/Y H:i', strtotime($order_info['order_date'])); ?></strong>
            </div>
        </div>

        <div class="order-info-line">
            Người lập đơn: <strong><?php echo htmlspecialchars($order_info['full_name']); ?></strong>
        </div>

        <div class="order-stats-mini">
            <div class="stat-box box-rev">
                <h4>Tổng tiền khách trả</h4>
                <div class="num"><?php echo number_format($total_revenue); ?> ₫</div>
            </div>
            <div class="stat-box box-cost">
                <h4>Tổng giá vốn (Gốc)</h4>
                <div class="num"><?php echo number_format($total_cost); ?> ₫</div>
            </div>
            <div class="stat-box box-profit">
                <h4>Lợi nhuận đơn này</h4>
                <div class="num"><?php echo number_format($total_profit); ?> ₫</div>
            </div>
            <div class="stat-box box-rate">
                <h4>Tỉ suất lợi nhuận</h4>
                <div class="num">
                    <?php echo ($total_revenue > 0) ? round(($total_profit / $total_revenue) * 100, 1) : 0; ?>%
                </div>
            </div>
        </div>

        <h3>Chi tiết sản phẩm</h3>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th class="text-center">SL</th>
                    <th class="text-right">Giá bán</th>
                    <th class="text-right">Giá vốn</th> 
                    <th class="text-right">Thành tiền</th>
                    <th class="text-right">Lợi nhuận</th> 
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if($item['image']): ?>
                                <img src="./uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Img">
                            <?php else: ?>
                                <span class="img-placeholder">No img</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        </td>
                        <td class="text-center">x<?php echo $item['quantity']; ?></td>
                        
                        <td class="text-right">
                            <?php echo number_format($item['price']); ?> ₫
                        </td>
                        
                        <td class="text-right text-muted">
                            <?php echo number_format($item['original_price']); ?> ₫
                        </td>

                        <td class="text-right font-bold text-green">
                            <?php echo number_format($item['line_revenue']); ?> ₫
                        </td>
                        
                        <td class="text-right font-bold text-purple">
                            <?php echo number_format($item['line_profit']); ?> ₫
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <tr class="total-row">
                        <td colspan="5" class="text-right" style="color: #333;">TỔNG CỘNG HÓA ĐƠN:</td>
                        <td class="text-right text-green"><?php echo number_format($total_revenue); ?> ₫</td>
                        <td class="text-right text-purple"><?php echo number_format($total_profit); ?> ₫</td>
                    </tr>

                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Không có sản phẩm nào trong đơn hàng này.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div class="alert error">Không tìm thấy hóa đơn này.</div>
    <?php endif; ?>

</div>

<?php
// Giải phóng bộ nhớ và đóng kết nối
if ($result_details) mysqli_free_result($result_details);
mysqli_stmt_close($stmt_details);
disconnect_db();
echo '</div>'; // Đóng admin-wrapper
?>