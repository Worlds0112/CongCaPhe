<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php';

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

date_default_timezone_set('Asia/Ho_Chi_Minh');
$today = date('Y-m-d');
$this_month = date('Y-m');
$current_year = date('Y');
$current_hour = date('H');

// --- 1. XÁC ĐỊNH CA ---
$shift_name = "";
if ($current_hour >= 6 && $current_hour < 12) {
    $shift_start = "$today 06:00:00";
    $shift_end = "$today 12:00:00";
    $shift_name = "CA SÁNG";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $shift_start = "$today 12:00:00";
    $shift_end = "$today 18:00:00";
    $shift_name = "CA CHIỀU";
} else {
    $shift_start = "$today 18:00:00";
    $shift_end = "$today 23:59:59";
    $shift_name = "CA TỐI";
}

// --- 2. SỐ LIỆU TỔNG HỢP ---
// Ca hiện tại
$rev_shift = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE order_date >= '$shift_start' AND order_date <= '$shift_end'"))['t'] ?? 0;
$stock_shift = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as q FROM order_details od JOIN orders o ON od.order_id=o.id WHERE o.order_date >= '$shift_start' AND o.order_date <= '$shift_end'"))['q'] ?? 0;

// Hôm nay
$rev_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$today'"))['t'] ?? 0;
// Lãi hôm nay
$prof_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.cost_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['p'] ?? 0;
$sold_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['qty'] ?? 0;
$import_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE(created_at) = '$today'"))['qty'] ?? 0;

// Tháng này
$rev_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$this_month'"))['t'] ?? 0;
// Lãi tháng
$prof_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.cost_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'"))['p'] ?? 0;
$sold_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'"))['qty'] ?? 0;
$import_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month'"))['qty'] ?? 0;

$low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE stock <= 5"))['c'];


// --- 3. DỮ LIỆU BIỂU ĐỒ 7 NGÀY ---
$chart_labels = [];
$chart_rev = [];
$chart_sold_qty = [];
$chart_import_qty = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$d'"))['t'] ?? 0;
    $chart_sold_qty[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'"))['qty'] ?? 0;
    $chart_import_qty[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE(created_at) = '$d'"))['qty'] ?? 0;
}

// --- 4. BIỂU ĐỒ TRÒN ---
$pie_labels = [];
$pie_data = [];
$q_pie = mysqli_query($conn, "SELECT c.name, SUM(od.price * od.quantity) as total FROM order_details od JOIN products p ON od.product_id = p.id JOIN categories c ON p.category_id = c.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month' GROUP BY c.id");
while ($row = mysqli_fetch_assoc($q_pie)) {
    $pie_labels[] = $row['name'];
    $pie_data[] = $row['total'];
}

// --- 5. BIỂU ĐỒ NGÀY TRONG THÁNG ---
$month_days_labels = [];
$month_days_rev = [];
$num_days = date('t');
for ($i = 1; $i <= $num_days; $i++) {
    $month_days_labels[] = $i;
    $d_check = date('Y-m-') . sprintf("%02d", $i);
    $month_days_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$d_check'"))['t'] ?? 0;
}

// --- 6. BIỂU ĐỒ 12 THÁNG ---
$year_labels = [];
$year_rev = [];
$year_prof = [];
for ($m = 1; $m <= 12; $m++) {
    $ym = $current_year . '-' . sprintf("%02d", $m);
    $year_labels[] = "Thg $m";
    $year_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$ym'"))['t'] ?? 0;
    $year_prof[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.cost_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$ym'"))['p'] ?? 0;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .stats-section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 15px;
        color: #555;
        margin-bottom: 15px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
    }

    .section-title span {
        width: 4px;
        height: 18px;
        background: #333;
        display: inline-block;
        border-radius: 2px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        text-align: center;
        border-bottom: 4px solid #eee;
        transition: 0.3s;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-label {
        font-size: 13px;
        color: #777;
        text-transform: uppercase;
        font-weight: bold;
    }

    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin: 5px 0;
    }

    .stat-icon {
        font-size: 24px;
        margin-bottom: 5px;
        display: block;
    }

    .stat-desc {
        font-size: 12px;
        color: #999;
    }

    /* Đây là class chữ nhỏ bạn cần */

    .c-shift {
        border-bottom-color: #007bff;
        color: #007bff;
        background: #f0f7ff;
    }

    .c-day-money {
        border-bottom-color: #28a745;
        color: #28a745;
    }

    .c-day-stock {
        border-bottom-color: #fd7e14;
        color: #fd7e14;
    }

    .c-month {
        border-bottom-color: #6f42c1;
        color: #6f42c1;
    }

    .c-alert {
        border-bottom-color: #dc3545;
        color: #dc3545;
    }

    .charts-row-top {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-top: 40px;
    }

    .chart-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .chart-header {
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
        border-left: 4px solid #5B743A;
        padding-left: 10px;
    }

    .chart-full-width {
        margin-top: 20px;
    }

    .charts-row-bottom {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    @media (max-width: 900px) {

        .charts-row-top,
        .charts-row-bottom {
            grid-template-columns: 1fr;
        }
    }
</style>

<h2 style="border-left: 5px solid #28a745; padding-left: 15px; margin-bottom: 30px;">Trung Tâm Thống Kê</h2>



<div class="stats-section">
    <div class="section-title"><span style="background: #007bff;"></span> ĐANG HOẠT ĐỘNG: <?php echo $shift_name; ?>
    </div>
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-shift">
            <span class="stat-icon">⚡</span>
            <div class="stat-label">Doanh thu Ca</div>
            <div class="stat-number"><?php echo number_format($rev_shift); ?> ₫</div>
            <div class="stat-desc">Đang cập nhật...</div>
        </a>
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-shift">
            <span class="stat-icon">☕</span>
            <div class="stat-label">Đã Bán Ca này</div>
            <div class="stat-number"><?php echo number_format($stock_shift); ?> <small>món</small></div>
            <div class="stat-desc">Số lượng xuất kho</div>
        </a>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #28a745;"></span> HÔM NAY (<?php echo date('d/m'); ?>)</div>
    <div class="stats-grid">
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-day-money">
            <div class="stat-label">Doanh thu Ngày</div>
            <div class="stat-number"><?php echo number_format($rev_today); ?> ₫</div>
            <div class="stat-desc">Lãi: <?php echo number_format($prof_today); ?> ₫</div>
        </a>
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-day-stock">
            <div class="stat-label">Bán Ra (Ngày)</div>
            <div class="stat-number"><?php echo number_format($sold_today); ?></div>
            <div class="stat-desc">Đơn vị: Món/Ly</div>
        </a>
        <a href="inventory_history.php?date=<?php echo $today; ?>" class="stat-card c-day-stock"
            style="border-bottom-color: #17a2b8; color: #17a2b8;">
            <div class="stat-label">Nhập Kho (Ngày)</div>
            <div class="stat-number"><?php echo number_format($import_today); ?></div>
            <div class="stat-desc">Hàng mới về</div>
        </a>
        <a href="product_list.php?view=low" class="stat-card c-alert">
            <div class="stat-label">Sắp Hết Hàng</div>
            <div class="stat-number"><?php echo $low_stock; ?></div>
            <div class="stat-desc">Mức báo động &le; 5</div>
        </a>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #6f42c1;"></span> THÁNG <?php echo date('m/Y'); ?></div>

    <div
        style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">

        <form action="export_excel.php" method="POST" style="display:flex; gap:10px; align-items:center;">
            <strong style="color: #6f42c1;">📊 Báo cáo THÁNG:</strong>
            <select name="month" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $sel = ($m == date('m')) ? 'selected' : '';
                    echo "<option value='$m' $sel>Tháng $m</option>";
                }
                ?>
            </select>
            <select name="year" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <?php
                $cur_year = date('Y');
                for ($y = $cur_year; $y >= $cur_year - 2; $y--) {
                    echo "<option value='$y'>$y</option>";
                }
                ?>
            </select>
            <button type="submit" name="btn_export_month"
                style="background: #217346; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                📥 Tải Excel
            </button>
        </form>

        <div style="width: 1px; height: 30px; background: #ddd;"></div>
        <form action="export_excel.php" method="POST" style="display:flex; gap:10px; align-items:center;">
            <strong style="color: #007bff;">📅 Báo cáo NGÀY:</strong>
            <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>"
                style="padding: 7px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" name="btn_export_day"
                style="background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                📥 Tải Excel
            </button>
        </form>

        <div style="width: 1px; height: 30px; background: #ddd;"></div>
        <form action="export_excel.php" method="POST" style="display:flex; gap:10px; align-items:center;">
            <strong style="color: #dc3545;">📆 Khoảng ngày:</strong>
            <input type="date" name="date_from" value="<?php echo date('Y-m-01'); ?>"
                style="padding: 7px; border: 1px solid #ddd; border-radius: 4px;" title="Từ ngày">
            <span>→</span>
            <input type="date" name="date_to" value="<?php echo date('Y-m-d'); ?>"
                style="padding: 7px; border: 1px solid #ddd; border-radius: 4px;" title="Đến ngày">
            <button type="submit" name="btn_export_range"
                style="background: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                📥 Tải Excel
            </button>
        </form>

    </div>
    <div class="stats-grid">
        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month">
            <div class="stat-label">Doanh thu Tháng</div>
            <div class="stat-number"><?php echo number_format($rev_month); ?> ₫</div>
            <div class="stat-desc">Tổng thu</div>
        </a>
        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month">
            <div class="stat-label">Lợi Nhuận Tháng</div>
            <div class="stat-number"><?php echo number_format($prof_month); ?> ₫</div>
            <div class="stat-desc">Lãi ròng</div>
        </a>
        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month"
            style="border-bottom-color: #fd7e14; color: #fd7e14;">
            <div class="stat-label">Tổng Bán Tháng</div>
            <div class="stat-number"><?php echo number_format($sold_month); ?></div>
            <div class="stat-desc">Ly/Món</div>
        </a>
        <a href="inventory_history.php?month=<?php echo $this_month; ?>" class="stat-card c-month"
            style="border-bottom-color: #17a2b8; color: #17a2b8;">
            <div class="stat-label">Tổng Nhập Tháng</div>
            <div class="stat-number"><?php echo number_format($import_month); ?></div>
            <div class="stat-desc">Nguyên liệu</div>
        </a>
    </div>
</div>

<div class="charts-row-top">
    <div class="chart-box">
        <div class="chart-header">📊 Tương quan Doanh thu & Số lượng (7 ngày)</div>
        <canvas id="comboChart"></canvas>
    </div>
    <div class="chart-box">
        <div class="chart-header">🍰 Tỷ trọng Doanh thu (Tháng)</div>
        <div style="height: 250px; position: relative;">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<div class="chart-box chart-full-width">
    <div class="chart-header">📅 Diễn biến Doanh thu Tháng <?php echo date('m/Y'); ?></div>
    <canvas id="monthChart" style="height: 300px; width: 100%;"></canvas>
</div>

<div class="chart-box chart-full-width">
    <div class="chart-header">🗓️ Tổng kết Doanh thu & Lợi nhuận Năm <?php echo $current_year; ?></div>
    <canvas id="yearChart" style="height: 300px; width: 100%;"></canvas>
</div>

<div class="charts-row-bottom">
    <div class="chart-box">
        <div class="chart-header">📦 Nhập - Xuất Kho (7 ngày)</div>
        <canvas id="stockFluxChart"></canvas>
    </div>
</div>

<script>
    // 1. Chart 7 Ngày
    new Chart(document.getElementById('comboChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                { label: 'Doanh thu', data: <?php echo json_encode($chart_rev); ?>, backgroundColor: 'rgba(40, 167, 69, 0.6)', borderColor: '#28a745', borderWidth: 1, order: 2, yAxisID: 'y_money' },
                { label: 'SL Bán', data: <?php echo json_encode($chart_sold_qty); ?>, type: 'line', borderColor: '#fd7e14', backgroundColor: 'rgba(253, 126, 20, 0.2)', borderWidth: 3, tension: 0.3, order: 1, yAxisID: 'y_qty' }
            ]
        },
        options: { responsive: true, scales: { y_money: { position: 'left', beginAtZero: true }, y_qty: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } } }
    });

    // 2. Chart Tròn
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{ data: <?php echo json_encode($pie_data); ?>, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Chart Tháng
    new Chart(document.getElementById('monthChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($month_days_labels); ?>,
            datasets: [{ label: 'Doanh thu ngày', data: <?php echo json_encode($month_days_rev); ?>, borderColor: '#6f42c1', backgroundColor: 'rgba(111, 66, 193, 0.1)', borderWidth: 2, pointRadius: 3, fill: true, tension: 0.2 }]
        },
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Ngày' } }, y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function (c) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(c.raw); } } } } }
    });

    // 4. Chart Năm
    new Chart(document.getElementById('yearChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($year_labels); ?>,
            datasets: [
                { label: 'Doanh thu', data: <?php echo json_encode($year_rev); ?>, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: '#36a2eb', borderWidth: 1, order: 2 },
                { label: 'Lợi nhuận', data: <?php echo json_encode($year_prof); ?>, type: 'line', borderColor: '#ff6384', backgroundColor: 'rgba(255, 99, 132, 0.2)', borderWidth: 3, tension: 0.3, order: 1 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(c.raw); } } } } }
    });

    // 5. Chart Kho
    new Chart(document.getElementById('stockFluxChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                { label: 'Nhập', data: <?php echo json_encode($chart_import_qty); ?>, backgroundColor: 'rgba(23, 162, 184, 0.7)' },
                { label: 'Xuất', data: <?php echo json_encode($chart_sold_qty); ?>, backgroundColor: 'rgba(253, 126, 20, 0.7)' }
            ]
        },
        options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
    });
</script>

<?php
echo '</div>';
echo '</div>';
?>