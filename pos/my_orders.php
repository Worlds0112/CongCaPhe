<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 

date_default_timezone_set('Asia/Ho_Chi_Minh');
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- 1. XÁC ĐỊNH THỜI ĐIỂM BẮT ĐẦU CA HIỆN TẠI ---
// Logic: Lấy thời gian của lần báo cáo kết ca gần nhất của nhân viên này hoặc của hệ thống
// Tuy nhiên, yêu cầu là "trong ca của mình". Ca bắt đầu từ lúc nhân viên nhận ca.
// Cách đơn giản nhất: Lấy thời điểm chốt ca gần nhất của BẤT KỲ AI. 
// Mọi đơn hàng sau thời điểm đó được coi là thuộc ca hiện tại.

$sql_last_shift = "SELECT created_at FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last_shift);
$r_last = mysqli_fetch_assoc($q_last);

// Nếu có báo cáo trước đó -> Ca này bắt đầu ngay sau đó
// Nếu không (sáng sớm hoặc hệ thống mới) -> Bắt đầu từ đầu ngày
$start_time = ($r_last) ? $r_last['created_at'] : "$today 00:00:00";

// --- 2. LẤY ĐƠN HÀNG CỦA NHÂN VIÊN TRONG KHOẢNG THỜI GIAN ĐÓ ---
$sql_orders = "SELECT * FROM orders 
               WHERE user_id = '$user_id' 
               AND order_date > '$start_time' 
               ORDER BY order_date DESC";
$result = mysqli_query($conn, $sql_orders);

// Tính tổng nhanh
$total_orders = mysqli_num_rows($result);
$total_revenue = 0;
?>

<style>
    .pos-wrapper { max-width: 1000px; margin: 30px auto; padding: 20px; }
    
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        background: white; padding: 20px; border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px;
    }
    
    .order-list {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;
    }
    
    .order-card {
        background: white; border-radius: 10px; overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #5B743A;
        transition: transform 0.2s;
    }
    .order-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    
    .card-top { padding: 15px; border-bottom: 1px dashed #eee; display: flex; justify-content: space-between; }
    .order-id { font-weight: bold; color: #333; }
    .order-time { font-size: 13px; color: #777; }
    
    .card-body { padding: 15px; }
    .product-line { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 5px; }
    .product-name { color: #555; }
    .product-qty { font-weight: bold; color: #333; }
    
    .card-footer { padding: 15px; background: #f9f9f9; text-align: right; font-weight: bold; color: #d32f2f; }
    
    .empty-state { text-align: center; padding: 50px; color: #999; width: 100%; grid-column: 1 / -1; }
</style>

<div class="content pos-wrapper">
    
    <div class="page-header">
        <div>
            <h2 style="margin:0; color: #5B743A;">Đơn hàng Ca này</h2>
            <div style="font-size: 13px; color: #777; margin-top: 5px;">
                Từ <?php echo date('H:i d/m', strtotime($start_time)); ?> đến hiện tại
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 24px; font-weight: bold; color: #28a745;" id="total-rev-display">0 ₫</div>
            <div style="font-size: 13px; font-weight: bold; color: #555;">Tổng: <?php echo $total_orders; ?> đơn</div>
        </div>
    </div>

    <div class="order-list">
        <?php if ($total_orders > 0): ?>
            <?php 
            while ($row = mysqli_fetch_assoc($result)): 
                $total_revenue += $row['total_amount'];
                
                // Lấy chi tiết món ăn của đơn này
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
                <div style="font-size: 40px; margin-bottom: 10px;">📭</div>
                Chưa có đơn hàng nào trong ca làm việc này.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require '../includes/footer.php'; ?>