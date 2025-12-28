<?php
// 1. KẾT NỐI VÀ BẢO VỆ
require '../includes/auth_pos.php'; // Kiểm tra quyền POS
require '../includes/header.php';   // Gọi Header & CSS

echo '<link rel="stylesheet" href="../css/pos_style.css">';

// Đảm bảo múi giờ đúng
date_default_timezone_set('Asia/Ho_Chi_Minh');
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- 2. XÁC ĐỊNH THỜI ĐIỂM BẮT ĐẦU CA HIỆN TẠI ---
// Logic: Lấy thời gian chốt ca gần nhất của hệ thống.
// Đơn hàng nào tạo SAU thời điểm chốt ca gần nhất -> Thuộc ca hiện tại.
$sql_last_shift = "SELECT created_at FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last_shift);
$r_last = mysqli_fetch_assoc($q_last);

// Nếu có báo cáo trước đó -> Ca này bắt đầu ngay sau đó
// Nếu không (sáng sớm hoặc hệ thống mới tinh) -> Bắt đầu từ đầu ngày (00:00)
$start_time = ($r_last) ? $r_last['created_at'] : "$today 00:00:00";

// --- 3. LẤY ĐƠN HÀNG CỦA NHÂN VIÊN (TRONG CA NÀY) ---
$sql_orders = "SELECT * FROM orders 
               WHERE user_id = '$user_id' 
               AND order_date > '$start_time' 
               ORDER BY order_date DESC";
$result = mysqli_query($conn, $sql_orders);

// Tính toán sơ bộ
$total_orders = mysqli_num_rows($result);
$total_revenue = 0; // Biến cộng dồn doanh thu để hiển thị lên Header
?>

<div class="content pos-wrapper">
    
    <div class="page-header">
        <div>
            <h2 class="header-title">Đơn hàng Ca này</h2>
            <div class="header-subtitle">
                Từ <?php echo date('H:i d/m', strtotime($start_time)); ?> đến hiện tại
            </div>
        </div>
        <div style="text-align: right;">
            <div class="total-rev" id="total-rev-display">0 ₫</div>
            <div class="total-count">Tổng: <?php echo $total_orders; ?> đơn</div>
        </div>
    </div>

    <div class="order-list">
        <?php if ($total_orders > 0): ?>
            
            <?php 
            // Duyệt qua từng đơn hàng
            while ($row = mysqli_fetch_assoc($result)): 
                // Cộng dồn doanh thu
                $total_revenue += $row['total_amount'];
                
                // Truy vấn lấy chi tiết món ăn trong đơn hàng này
                $oid = $row['id'];
                $q_detail = mysqli_query($conn, "SELECT d.quantity, p.name 
                                                 FROM order_details d 
                                                 JOIN products p ON d.product_id = p.id 
                                                 WHERE d.order_id = $oid");
            ?>
                <div class="order-card">
                    <div class="card-top">
                        <span class="order-id">#<?php echo $row['id']; ?></span>
                        <span class="order-time"><?php echo date('H:i', strtotime($row['order_date'])); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <?php while($d = mysqli_fetch_assoc($q_detail)): ?>
                            <div class="product-line">
                                <span class="product-name"><?php echo htmlspecialchars($d['name']); ?></span>
                                <span class="product-qty">x<?php echo $d['quantity']; ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="card-footer">
                        <?php echo number_format($row['total_amount']); ?> ₫
                    </div>
                </div>
            <?php endwhile; ?>
            
            <script>
                document.getElementById('total-rev-display').innerText = '<?php echo number_format($total_revenue); ?> ₫';
            </script>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                Chưa có đơn hàng nào trong ca làm việc này.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require '../includes/footer.php'; ?>