<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

date_default_timezone_set('Asia/Ho_Chi_Minh');
$today = date('Y-m-d');
$this_month = date('Y-m');
$current_hour = date('H');

// --- 1. XÁC ĐỊNH CA LÀM VIỆC ---
$shift_name = "";
if ($current_hour >= 6 && $current_hour < 12) {
    $shift_start = "$today 06:00:00"; $shift_end = "$today 12:00:00"; $shift_name = "CA SÁNG";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $shift_start = "$today 12:00:00"; $shift_end = "$today 18:00:00"; $shift_name = "CA CHIỀU";
} else {
    $shift_start = "$today 18:00:00"; $shift_end = "$today 23:59:59"; $shift_name = "CA TỐI";
}

// --- 2. SỐ LIỆU THEO CA (Shift Stats) ---
$sql_rev_shift = "SELECT SUM(total_amount) as total FROM orders WHERE order_date >= '$shift_start' AND order_date <= '$shift_end'";
$rev_shift = mysqli_fetch_assoc(mysqli_query($conn, $sql_rev_shift))['total'] ?? 0;

$sql_stock_shift = "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.order_date >= '$shift_start' AND o.order_date <= '$shift_end'";
$stock_shift = mysqli_fetch_assoc(mysqli_query($conn, $sql_stock_shift))['qty'] ?? 0;

// --- 3. SỐ LIỆU CẢ NGÀY (Daily Stats) ---
// Doanh thu
$rev_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$today'"))['t'] ?? 0;
// Lợi nhuận
$prof_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['p'] ?? 0;

// Kho: Bán ra (Xuất)
$sold_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['qty'] ?? 0;
// Kho: Nhập vào
$import_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE(created_at) = '$today'"))['qty'] ?? 0;
// Kho: Tổng tồn
$current_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as s FROM products"))['s'] ?? 0;
// Cảnh báo
$low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE stock <= 5"))['c'];


// --- 4. DỮ LIỆU BIỂU ĐỒ 7 NGÀY ---
$chart_labels = []; 
$chart_rev = [];        // Tiền doanh thu
$chart_sold_qty = [];   // Số lượng bán
$chart_import_qty = []; // Số lượng nhập

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d));
    
    // Doanh thu
    $q_rev = mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$d'");
    $chart_rev[] = mysqli_fetch_assoc($q_rev)['t'] ?? 0;

    // Số lượng Bán (Xuất)
    $q_out = mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'");
    $chart_sold_qty[] = mysqli_fetch_assoc($q_out)['qty'] ?? 0;

    // Số lượng Nhập
    $q_in = mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE(created_at) = '$d'");
    $chart_import_qty[] = mysqli_fetch_assoc($q_in)['qty'] ?? 0;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    
    /* Grid Layout */
    .stats-section { margin-bottom: 30px; }
    .section-title { font-size: 15px; color: #555; margin-bottom: 15px; font-weight: bold; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
    .section-title span { width: 4px; height: 18px; background: #333; display: inline-block; border-radius: 2px; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    
    .stat-card {
        background: white; padding: 20px; border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;
        border-bottom: 4px solid #eee; transition: 0.3s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    
    .stat-icon { font-size: 24px; margin-bottom: 5px; display: block; }
    .stat-label { font-size: 13px; color: #777; text-transform: uppercase; font-weight: bold; }
    .stat-number { font-size: 24px; font-weight: bold; color: #333; margin: 5px 0; }
    .stat-desc { font-size: 12px; color: #999; }

    /* Màu sắc */
    .c-shift { border-bottom-color: #007bff; color: #007bff; background: #f0f7ff; } /* Xanh dương ca */
    .c-money { border-bottom-color: #28a745; color: #28a745; } /* Xanh lá tiền */
    .c-stock { border-bottom-color: #fd7e14; color: #fd7e14; } /* Cam hàng */
    .c-import { border-bottom-color: #6f42c1; color: #6f42c1; } /* Tím nhập */
    .c-alert { border-bottom-color: #dc3545; color: #dc3545; } /* Đỏ báo động */

    /* Chart Layout */
    .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 30px; }
    .chart-box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .chart-header { font-weight: bold; color: #333; margin-bottom: 15px; border-left: 4px solid #5B743A; padding-left: 10px; }

    @media (max-width: 900px) { .charts-row { grid-template-columns: 1fr; } }
</style>

<div class="admin-wrapper">
    <h2 style="border-left: 5px solid #28a745; padding-left: 15px; margin-bottom: 30px;">Tổng Quan Hoạt Động</h2>

    <div class="stats-section">
        <div class="section-title"><span style="background: #007bff;"></span> ĐANG HOẠT ĐỘNG: <?php echo $shift_name; ?></div>
        <div class="stats-grid">
            <div class="stat-card c-shift">
                <span class="stat-icon">⚡</span>
                <div class="stat-label">Doanh thu Ca</div>
                <div class="stat-number"><?php echo number_format($rev_shift); ?> ₫</div>
            </div>
            <div class="stat-card c-shift">
                <span class="stat-icon">☕</span>
                <div class="stat-label">Đã Bán Ca này</div>
                <div class="stat-number"><?php echo number_format($stock_shift); ?> <small>món</small></div>
            </div>
        </div>
    </div>

    <div class="stats-section">
        <div class="section-title"><span style="background: #28a745;"></span> TỔNG KẾT HÔM NAY (<?php echo date('d/m'); ?>)</div>
        <div class="stats-grid">
            <div class="stat-card c-money">
                <div class="stat-label">Tổng Doanh thu</div>
                <div class="stat-number"><?php echo number_format($rev_today); ?> ₫</div>
                <div class="stat-desc">Lãi: <?php echo number_format($prof_today); ?> ₫</div>
            </div>
            
            <div class="stat-card c-stock">
                <div class="stat-label">Tổng Bán Ra</div>
                <div class="stat-number"><?php echo number_format($sold_today); ?></div>
                <div class="stat-desc">Đơn vị: Món/Ly</div>
            </div>

            <div class="stat-card c-import">
                <div class="stat-label">Đã Nhập Kho</div>
                <div class="stat-number"><?php echo number_format($import_today); ?></div>
                <div class="stat-desc">Hàng mới về</div>
            </div>

            <div class="stat-card c-alert">
                <div class="stat-label">Sắp Hết Hàng</div>
                <div class="stat-number"><?php echo $low_stock; ?></div>
                <div class="stat-desc">Món còn &le; 5</div>
            </div>
        </div>
    </div>

    <div class="charts-row">
        
        <div class="chart-box">
            <div class="chart-header">📊 Tương quan Doanh thu & Số lượng bán</div>
            


            <canvas id="comboChart"></canvas>
            <p style="text-align: center; font-size: 13px; color: #777; margin-top: 10px;">
                Cột xanh: Doanh thu (Trục trái) - Đường cam: Số lượng bán (Trục phải)
            </p>
        </div>

        <div class="chart-box">
            <div class="chart-header">📦 Nhập - Xuất Kho</div>
            <canvas id="stockFluxChart"></canvas>
            <p style="text-align: center; font-size: 13px; color: #777; margin-top: 10px;">
                Theo dõi luân chuyển hàng hóa
            </p>
        </div>

    </div>
</div>

<script>
    // --- 1. BIỂU ĐỒ KẾT HỢP (COMBO CHART) ---
    // Đây là biểu đồ "tích hợp 2 bảng" mà bạn thích
    const ctxCombo = document.getElementById('comboChart').getContext('2d');
    new Chart(ctxCombo, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode($chart_rev); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.6)', // Cột xanh lá
                    borderColor: '#28a745', borderWidth: 1,
                    order: 2,
                    yAxisID: 'y_money' // Gắn vào trục trái
                },
                {
                    label: 'Số lượng bán (Món)',
                    data: <?php echo json_encode($chart_sold_qty); ?>,
                    type: 'line', // Đường dây
                    borderColor: '#fd7e14', // Màu cam
                    backgroundColor: 'rgba(253, 126, 20, 0.2)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#fd7e14',
                    pointRadius: 5,
                    tension: 0.3,
                    order: 1,
                    yAxisID: 'y_qty' // Gắn vào trục phải
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y_money: {
                    type: 'linear', display: true, position: 'left', beginAtZero: true,
                    title: { display: true, text: 'Doanh thu (VNĐ)' }
                },
                y_qty: {
                    type: 'linear', display: true, position: 'right', beginAtZero: true,
                    grid: { drawOnChartArea: false }, // Ẩn lưới trục phải cho đỡ rối
                    title: { display: true, text: 'Số lượng (Món)' }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.dataset.yAxisID === 'y_money') {
                                label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                            } else {
                                label += context.raw;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    // --- 2. BIỂU ĐỒ NHẬP XUẤT (STACKED BAR) ---
    const ctxStock = document.getElementById('stockFluxChart').getContext('2d');
    new Chart(ctxStock, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Nhập kho',
                    data: <?php echo json_encode($chart_import_qty); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.7)', // Xanh dương
                },
                {
                    label: 'Xuất kho (Bán)',
                    data: <?php echo json_encode($chart_sold_qty); ?>,
                    backgroundColor: 'rgba(253, 126, 20, 0.7)', // Cam
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true }, // Cột chồng lên nhau
                y: { stacked: true, beginAtZero: true }
            }
        }
    });
</script>

<?php require '../includes/footer.php'; ?>