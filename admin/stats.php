<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// 1. TÍNH TOÁN CÁC CON SỐ TỔNG QUAN
$today = date('Y-m-d');
$this_month = date('Y-m');

// Doanh thu hôm nay
$q1 = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '$today'");
$rev_today = mysqli_fetch_assoc($q1)['total'] ?? 0;

// Doanh thu tháng này
$q2 = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$this_month'");
$rev_month = mysqli_fetch_assoc($q2)['total'] ?? 0;

// Tổng đơn hàng hôm nay
$q3 = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = '$today'");
$orders_today = mysqli_fetch_assoc($q3)['count'] ?? 0;

// 2. DỮ LIỆU BIỂU ĐỒ (7 NGÀY GẦN NHẤT)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $q = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '$d'");
    $row = mysqli_fetch_assoc($q);
    
    $chart_labels[] = date('d/m', strtotime($d)); // Nhãn ngày (vd: 05/12)
    $chart_data[] = $row['total'] ?? 0; // Doanh thu
}

// 3. THỐNG KÊ THEO SẢN PHẨM (Top bán chạy)
$sql_prod = "SELECT p.name, SUM(od.quantity) as sold_qty, SUM(od.price * od.quantity) as revenue
             FROM order_details od
             JOIN products p ON od.product_id = p.id
             GROUP BY od.product_id
             ORDER BY sold_qty DESC LIMIT 5";
$res_prod = mysqli_query($conn, $sql_prod);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    
    /* Grid 4 thẻ thống kê */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card {
        background: white; padding: 20px; border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;
    }
    .stat-title { font-size: 14px; color: #777; text-transform: uppercase; font-weight: bold; }
    .stat-value { font-size: 24px; font-weight: bold; color: #333; margin-top: 10px; }
    .text-green { color: #28a745; }
    .text-blue { color: #007bff; }

    /* Layout Biểu đồ và Bảng */
    .charts-row { display: flex; gap: 20px; flex-wrap: wrap; }
    .chart-box { flex: 2; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .top-products { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; font-size: 14px; }
    th { color: #555; }
</style>

<div class="admin-wrapper">
    <h2 style="border-left: 5px solid #28a745; padding-left: 15px; margin-bottom: 20px;">Báo Cáo Thống Kê</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">Doanh thu hôm nay</div>
            <div class="stat-value text-green"><?php echo number_format($rev_today); ?> ₫</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Đơn hàng hôm nay</div>
            <div class="stat-value"><?php echo $orders_today; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Doanh thu tháng này</div>
            <div class="stat-value text-blue"><?php echo number_format($rev_month); ?> ₫</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Tồn kho tổng</div>
            <?php 
            $q_stock = mysqli_query($conn, "SELECT SUM(stock) as s FROM products");
            $stock = mysqli_fetch_assoc($q_stock)['s'];
            ?>
            <div class="stat-value"><?php echo number_format($stock); ?></div>
        </div>
    </div>

    <div class="charts-row">
        
        <div class="chart-box">
            <h3 style="margin-bottom: 15px;">Biểu đồ doanh thu 7 ngày</h3>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="top-products">
            <h3 style="margin-bottom: 15px;">Top 5 Món Bán Chạy</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tên món</th>
                        <th>SL</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prod = mysqli_fetch_assoc($res_prod)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td><?php echo $prod['sold_qty']; ?></td>
                        <td><?php echo number_format($prod['revenue']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Cấu hình Biểu đồ Chart.js
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar', // Loại biểu đồ: Cột
        data: {
            labels: <?php echo json_encode($chart_labels); ?>, // Mảng ngày
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?php echo json_encode($chart_data); ?>, // Mảng tiền
                backgroundColor: 'rgba(91, 116, 58, 0.7)', // Màu xanh Cộng
                borderColor: 'rgba(91, 116, 58, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php require '../includes/footer.php'; ?>