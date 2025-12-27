<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 
echo '<div class="main-with-sidebar">';

// 2. LẤY ID HÓA ĐƠN
$order_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo "<div class='admin-wrapper'><h1>ID hóa đơn không hợp lệ.</h1></div>";
    require '../includes/footer.php';
    disconnect_db();
    exit();
}

// 3. LẤY THÔNG TIN CHUNG
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

// 4. LẤY CHI TIẾT + GIÁ GỐC (SỬA LẠI THÀNH original_price)
$sql_details = "SELECT products.name, products.image, products.original_price, 
                       order_details.quantity, order_details.price
                FROM order_details
                JOIN products ON order_details.product_id = products.id
                WHERE order_details.order_id = ?";
$stmt_details = mysqli_prepare($conn, $sql_details);
mysqli_stmt_bind_param($stmt_details, "i", $order_id);
mysqli_stmt_execute($stmt_details);
$result_details = mysqli_stmt_get_result($stmt_details);

// --- TÍNH TOÁN TRƯỚC KHI HIỂN THỊ ---
$items = [];
$total_revenue = 0;
$total_cost = 0;

if ($result_details) {
    while ($row = mysqli_fetch_assoc($result_details)) {
        // Tính toán từng dòng (SỬA LẠI key mảng ở đây)
        $row['line_revenue'] = $row['price'] * $row['quantity']; // Tiền bán
        $row['line_cost']    = $row['original_price'] * $row['quantity']; // Tiền vốn (Đã sửa)
        $row['line_profit']  = $row['line_revenue'] - $row['line_cost']; // Lợi nhuận
        
        // Cộng dồn tổng
        $total_revenue += $row['line_revenue'];
        $total_cost    += $row['line_cost'];
        
        $items[] = $row;
    }
}
$total_profit = $total_revenue - $total_cost;
?>

<style>
    .admin-wrapper { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #17a2b8; padding-left: 15px; }
    .btn-back { display: inline-block; background-color: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 20px; font-size: 14px; }
    .btn-back:hover { background-color: #5a6268; }

    /* DASHBOARD MINI CHO ĐƠN HÀNG */
    .order-stats { display: flex; gap: 15px; margin-bottom: 25px; }
    .stat-box { flex: 1; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-top: 4px solid #ccc; }
    .stat-box h4 { margin: 0 0 5px; font-size: 12px; text-transform: uppercase; color: #777; }
    .stat-box .num { font-size: 20px; font-weight: bold; }
    
    .box-rev { border-top-color: #28a745; }
    .box-rev .num { color: #28a745; }
    
    .box-cost { border-top-color: #ffc107; }
    .box-cost .num { color: #d39e00; }
    
    .box-profit { border-top-color: #6f42c1; }
    .box-profit .num { color: #6f42c1; }

    /* TABLE STYLES */
    .order-info-line { background: #e9ecef; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; color: #555; }
    table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border-radius: 10px; overflow: hidden; }
    th, td { border-bottom: 1px solid #eee; padding: 12px 15px; text-align: left; vertical-align: middle; }
    th { background-color: #f8f9fa; font-weight: 700; color: #555; text-transform: uppercase; font-size: 12px; }
    img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
    
    .text-right { text-align: right; }
    .fw-bold { font-weight: bold; }
    .text-green { color: #28a745; }
    .text-purple { color: #6f42c1; }
    .text-muted { color: #999; font-size: 0.9em; }

    .total-row td { background-color: #ffffeb; font-weight: bold; font-size: 16px; padding-top: 15px; padding-bottom: 15px; border-top: 2px solid #ddd; }
    /* CSS cho nút Xuất Excel */
    .btn-excel {
        display: inline-block;
        background-color: #217346; /* Màu xanh Excel */
        color: white;
        padding: 8px 15px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        font-size: 14px;
        transition: 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-excel:hover {
        background-color: #1e6b41;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
</style>

<div class="admin-wrapper">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="order_list.php" class="btn-back" style="margin-bottom: 0;">← Quay lại danh sách</a>
        
        <a href="../excel/export_order_excel.php?id=<?php echo $order_id; ?>" class="btn-excel" target="_blank">
            📥 Xuất Hóa Đơn Excel
        </a>
    </div>

    <?php if ($order_info): ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Chi tiết Hóa đơn: #<?php echo $order_info['id']; ?></h2>
            <div style="font-style:italic; color:#666;">
                Ngày tạo: <strong><?php echo date('d/m/Y H:i', strtotime($order_info['order_date'])); ?></strong>
            </div>
        </div>

        <div class="order-info-line">
            Người lập đơn: <strong><?php echo htmlspecialchars($order_info['full_name']); ?></strong>
        </div>

        <div class="order-stats">
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
            <div class="stat-box" style="border-top-color: #17a2b8;">
                <h4>Tỉ suất lợi nhuận</h4>
                <div class="num" style="color: #17a2b8;">
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
                                <img src="../assets/no-image.png" alt="No Img">
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

                        <td class="text-right fw-bold text-green">
                            <?php echo number_format($item['line_revenue']); ?> ₫
                        </td>
                        
                        <td class="text-right fw-bold text-purple">
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
                    <tr><td colspan="7">Không có sản phẩm nào trong đơn hàng này.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <h2>Không tìm thấy hóa đơn này.</h2>
    <?php endif; ?>

</div>

<?php
// DỌN DẸP
if ($result_details) mysqli_free_result($result_details);
mysqli_stmt_close($stmt_details);
disconnect_db();
echo '</div>'; 
?>