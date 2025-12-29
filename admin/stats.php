<?php
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

// --- XỬ LÝ FORM NẠP TIỀN VÀO QUỸ (KHI NGƯỜI DÙNG BẤM NÚT) ---
$toast_msg = ""; 
if (isset($_POST['btn_add_fund'])) {
    $amount = (float)$_POST['fund_amount'];
    $note = $_POST['fund_note'];
    
    // Chỉ nạp nếu số tiền khác 0
    if ($amount != 0) {
        $sql_fund = "INSERT INTO funds (amount, note) VALUES ('$amount', '$note')";
        mysqli_query($conn, $sql_fund);
        $toast_msg = "✅ Đã nạp thành công " . number_format($amount) . "đ vào quỹ!";
    }
}

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// Thiết lập múi giờ và các biến thời gian cơ bản
date_default_timezone_set('Asia/Ho_Chi_Minh');
$today = date('Y-m-d');
$this_month = date('Y-m'); 
$current_year = date('Y');
$current_hour = date('H');

// --- HÀM HỖ TRỢ: LẤY GIÁ TRỊ ĐƠN TỪ SQL (GIÚP CODE GỌN HƠN) ---
function get_val($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($result);
    return $row[0] ?? 0; // Trả về 0 nếu null
}

// --- 2. XÁC ĐỊNH CA LÀM VIỆC HIỆN TẠI (DỰA VÀO GIỜ HỆ THỐNG) ---
$shift_name = "";
$current_shift_code = ""; 

if ($current_hour >= 6 && $current_hour < 12) {
    $shift_start = "$today 06:00:00"; $shift_end = "$today 12:00:00"; 
    $shift_name = "CA SÁNG"; $current_shift_code = "sang";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $shift_start = "$today 12:00:00"; $shift_end = "$today 18:00:00"; 
    $shift_name = "CA CHIỀU"; $current_shift_code = "chieu";
} else {
    $shift_start = "$today 18:00:00"; $shift_end = "$today 23:59:59"; 
    $shift_name = "CA TỐI"; $current_shift_code = "toi";
}

// --- 3. TÍNH TOÁN CÁC CHỈ SỐ TÀI CHÍNH ---

// A. TỔNG QUỸ (TÍCH LŨY TỪ TRƯỚC ĐẾN NAY)
// Công thức: (Tổng nạp quỹ + Tổng bán hàng) - (Tổng tiền nhập hàng)
$total_fund_in = get_val($conn, "SELECT SUM(amount) FROM funds");
$total_revenue_all = get_val($conn, "SELECT SUM(total_amount) FROM orders");
$total_import_cost_all = get_val($conn, "
    SELECT SUM(h.quantity * IF(h.import_price > 0, h.import_price, p.original_price)) 
    FROM inventory_history h 
    JOIN products p ON h.product_id = p.id 
    WHERE h.quantity > 0
");
// Lợi nhuận gộp toàn thời gian (Giá bán - Giá vốn * Số lượng bán)
$total_profit_all = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id");

$current_balance = ($total_fund_in + $total_revenue_all) - $total_import_cost_all;

// B. SỐ LIỆU CA HIỆN TẠI (THEO KHUNG GIỜ ĐÃ XÁC ĐỊNH Ở TRÊN)
$rev_shift = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE order_date >= '$shift_start' AND order_date <= '$shift_end'");
$cost_import_shift = get_val($conn, "
    SELECT SUM(h.quantity * IF(h.import_price > 0, h.import_price, p.original_price)) 
    FROM inventory_history h 
    JOIN products p ON h.product_id = p.id 
    WHERE h.created_at >= '$shift_start' AND h.created_at <= '$shift_end' AND h.quantity > 0
");
$prof_shift = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE o.order_date >= '$shift_start' AND o.order_date <= '$shift_end'");

// C. SỐ LIỆU HÔM NAY (TOÀN BỘ 24H)
$rev_today = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = '$today'");
$prof_today = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'");
$sold_today = get_val($conn, "SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'");
// Chỉ tính những dòng có quantity > 0 (tức là nhập kho)
$import_today = get_val($conn, "SELECT SUM(quantity) FROM inventory_history WHERE DATE(created_at) = '$today' AND quantity > 0");
$cost_import_today = get_val($conn, "
    SELECT SUM(h.quantity * IF(h.import_price > 0, h.import_price, p.original_price)) 
    FROM inventory_history h 
    JOIN products p ON h.product_id = p.id 
    WHERE DATE(h.created_at) = '$today' AND h.quantity > 0
");

// D. SỐ LIỆU THÁNG NÀY
$rev_month = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$this_month'");
$prof_month = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'");
$sold_month = get_val($conn, "SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'");
// Chỉ tính những dòng có quantity > 0
$import_month = get_val($conn, "SELECT SUM(quantity) FROM inventory_history WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month' AND quantity > 0");
$cost_import_month = get_val($conn, "
    SELECT SUM(h.quantity * IF(h.import_price > 0, h.import_price, p.original_price)) 
    FROM inventory_history h 
    JOIN products p ON h.product_id = p.id 
    WHERE DATE_FORMAT(h.created_at, '%Y-%m') = '$this_month' AND h.quantity > 0
");

// E. CẢNH BÁO KHO (SẢN PHẨM SẮP HẾT)
$low_stock = get_val($conn, "SELECT COUNT(*) FROM products WHERE stock <= 5");

// --- 4. CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ (CHARTS DATA) ---

// A. BIỂU ĐỒ CỘT & ĐƯỜNG (7 NGÀY GẦN NHẤT)
$chart_labels = []; $chart_rev = []; $chart_import_cost = []; $chart_profit = []; $chart_import_qty = []; $chart_sold_qty = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d)); // Nhãn ngày (VD: 25/12)

    // Lấy dữ liệu từng ngày
    $chart_rev[] = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = '$d'");
    $chart_import_cost[] = get_val($conn, "SELECT SUM(quantity * import_price) FROM inventory_history WHERE DATE(created_at) = '$d' AND quantity > 0");
    $chart_profit[] = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'");
    $chart_import_qty[] = get_val($conn, "SELECT SUM(quantity) FROM inventory_history WHERE DATE(created_at) = '$d' AND quantity > 0");
    $chart_sold_qty[] = get_val($conn, "SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'");
}

// B. BIỂU ĐỒ TRÒN (TỶ TRỌNG DANH MỤC)
$pie_labels = []; $pie_data = [];
$q_pie = mysqli_query($conn, "SELECT c.name, SUM(od.price * od.quantity) as total FROM order_details od JOIN products p ON od.product_id = p.id JOIN categories c ON p.category_id = c.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month' GROUP BY c.id");
while($row = mysqli_fetch_assoc($q_pie)) { 
    $pie_labels[] = $row['name']; 
    $pie_data[] = $row['total']; 
}

// C. BIỂU ĐỒ LINE (DIỄN BIẾN THÁNG)
$month_days_labels = []; $month_days_rev = [];
$num_days = date('t'); // Số ngày trong tháng hiện tại
for ($i = 1; $i <= $num_days; $i++) {
    $month_days_labels[] = $i;
    $d_check = date('Y-m-') . sprintf("%02d", $i);
    $month_days_rev[] = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = '$d_check'");
}

// D. BIỂU ĐỒ CỘT (TỔNG KẾT NĂM)
$year_labels = []; $year_rev = []; $year_prof = [];
for ($m = 1; $m <= 12; $m++) {
    $ym = $current_year . '-' . sprintf("%02d", $m);
    $year_labels[] = "Thg $m";
    $year_rev[] = get_val($conn, "SELECT SUM(total_amount) FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$ym'");
    $year_prof[] = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$ym'");
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <h2 class="title-product" style="margin: 0;">Trung Tâm Thống Kê</h2>
    
    <form action="../excel/export_stats_excel.php" method="GET" target="_blank" style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
        <select name="shift" class="form-control" style="width: auto; min-width: 80px;">
            <option value="">-- Ca --</option>
            <option value="sang">Sáng</option>
            <option value="chieu">Chiều</option>
            <option value="toi">Tối</option>
        </select>

        <select name="day" class="form-control" style="width: auto; min-width: 90px;">
            <option value="">Cả tháng</option>
            <?php for($d=1; $d<=31; $d++): ?>
                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
            <?php endfor; ?>
        </select>

        <select name="month" class="form-control" style="width: auto; min-width: 80px;">
            <?php $curMonth = date('m');
            for($m=1; $m<=12; $m++){ 
                $sel = ($m == $curMonth) ? 'selected' : ''; 
                echo "<option value='$m' $sel>T.$m</option>"; 
            } ?>
        </select>

        <select name="year" class="form-control" style="width: auto; min-width: 80px;">
            <?php $curYear = date('Y'); 
            for($y=$curYear; $y>=$curYear-2; $y--){ 
                echo "<option value='$y'>$y</option>"; 
            } ?>
        </select>

        <button type="submit" class="btn-excel">📥 Xuất Excel</button>
    </form>
</div>

<div class="fund-card">
    <div class="fund-info" style="flex: 1;">
        <h3>💰 Tổng Quỹ Tiền Mặt Hiện Có</h3>
        <div class="fund-number"><?php echo number_format($current_balance); ?> ₫</div>
        
        <div class="fund-stats-row">
            <div class="fund-stat-item">
                <small class="text-white-50">TỔNG BÁN</small><br>
                <strong style="font-size: 15px;">+<?php echo number_format($total_revenue_all); ?></strong>
            </div>
            <div class="fund-stat-item">
                <small class="text-white-50">TỔNG NHẬP</small><br>
                <strong class="text-light-red">-<?php echo number_format($total_import_cost_all); ?></strong>
            </div>
            <div class="fund-stat-item">
                <small class="text-white-50">TỔNG LÃI</small><br>
                <strong class="text-light-green">+<?php echo number_format($total_profit_all); ?></strong>
            </div>
        </div>
    </div>
    
    <div>
        <div style="font-size: 12px; margin-bottom: 5px; color: #ddd;">➕ Nạp thêm tiền vốn / quỹ:</div>
        <form method="POST" class="fund-form">
            <input type="number" name="fund_amount" placeholder="Nhập số tiền..." required>
            <input type="text" name="fund_note" placeholder="Ghi chú (VD: Vốn đầu tư)">
            <button type="submit" name="btn_add_fund">Nạp</button>
        </form>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #007bff;"></span> ĐANG HOẠT ĐỘNG: <?php echo $shift_name; ?></div>
    
    <div class="stats-grid">
        <a href="order_list.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&shift=<?php echo $current_shift_code; ?>" class="stat-card c-blue c-blue-bg">
        <span class="stat-icon">⚡</span> 
        <div class="stat-label">Doanh thu Ca</div>
        <div class="stat-number"><?php echo number_format($rev_shift); ?> ₫</div>
        <div class="stat-desc">Đang bán hiện tại</div>
    </a>

        <a href="inventory_history.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&shift=<?php echo $current_shift_code; ?>&type=in" class="stat-card c-red">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập (Ca)</div>
            <div class="stat-number"><?php echo number_format($cost_import_shift); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra trong ca</div>
        </a>

        <div class="stat-card c-cyan">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Ca)</div>
            <div class="stat-number"><?php echo number_format($rev_shift - $cost_import_shift); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <div class="stat-card c-blue"> <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp (Ca)</div>
            <div class="stat-number"><?php echo number_format($prof_shift); ?> ₫</div>
            <div class="stat-desc">Lãi trên đơn ca này</div>
        </div>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #28a745;"></span> HÔM NAY (<?php echo date('d/m'); ?>)</div>
    
    <div class="stats-grid">
        <a href="order_list.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-green">
            <span class="stat-icon">⚡</span>
            <div class="stat-label">Doanh thu Ngày</div> 
            <div class="stat-number"><?php echo number_format($rev_today); ?> ₫</div> 
            <div class="stat-desc">Tổng doanh số bán</div>
        </a>

        <a href="inventory_history.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&type=in" class="stat-card c-red">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($cost_import_today); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra hôm nay</div>
        </a>

        <div class="stat-card c-blue">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($rev_today - $cost_import_today); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <div class="stat-card c-purple"> <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($prof_today); ?> ₫</div>
            <div class="stat-desc">Lãi trên đơn hàng</div>
        </div>

        <a href="order_list.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-orange">
            <span class="stat-icon">☕</span>
            <div class="stat-label">Bán Ra (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($sold_today); ?></div>
            <div class="stat-desc">Đơn vị: Món/Ly</div>
        </a>

        <a href="inventory_history.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&type=in" class="stat-card c-cyan">
            <span class="stat-icon">🚚</span>
            <div class="stat-label">Nhập Kho (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($import_today); ?></div>
            <div class="stat-desc">Hàng mới về</div>
        </a>

        <a href="product_list.php?view=low" class="stat-card c-red">
            <span class="stat-icon">⚠️</span>
            <div class="stat-label">Sắp Hết Hàng</div> 
            <div class="stat-number"><?php echo $low_stock; ?></div>
            <div class="stat-desc">Mức báo động &le; 5</div>
        </a>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #6f42c1;"></span> THÁNG <?php echo date('m/Y'); ?></div>

    <div class="stats-grid">
        <a href="order_list.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-purple">
            <span class="stat-icon">⚡</span>
            <div class="stat-label">Doanh thu Tháng</div>
            <div class="stat-number"><?php echo number_format($rev_month); ?> ₫</div>
            <div class="stat-desc">Tổng thu</div>
        </a>

        <a href="inventory_history.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&type=in" class="stat-card c-red">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập Tháng</div>
            <div class="stat-number"><?php echo number_format($cost_import_month); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra</div>
        </a>

        <div class="stat-card c-blue">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Tháng)</div>
            <div class="stat-number"><?php echo number_format($rev_month - $cost_import_month); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <div class="stat-card c-purple"> <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp</div>
            <div class="stat-number"><?php echo number_format($prof_month); ?> ₫</div>
            <div class="stat-desc">Lãi ròng (trên đơn)</div>
        </div>

        <a href="order_list.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-orange">
            <span class="stat-icon">☕</span>
            <div class="stat-label">Tổng Bán Tháng</div>
            <div class="stat-number"><?php echo number_format($sold_month); ?></div>
            <div class="stat-desc">Ly/Món</div>
        </a>

        <a href="inventory_history.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&type=in" class="stat-card c-cyan">
            <span class="stat-icon">🚚</span>
            <div class="stat-label">Tổng Nhập Tháng</div>
            <div class="stat-number"><?php echo number_format($import_month); ?></div>
            <div class="stat-desc">Nguyên liệu</div>
        </a>
    </div>
    
    <div id="toast"><?php echo $toast_msg; ?></div>
</div>

<div class="charts-row-top">
    <div class="chart-box">
        <div class="chart-header">📊 Tài chính 7 ngày: Thu - Chi - Lãi</div>
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
    // 1. Chart Combo: Thu (Cột), Chi (Cột), Lãi (Đường dây)
    new Chart(document.getElementById('comboChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                { 
                    label: 'Doanh thu (Thu)', 
                    data: <?php echo json_encode($chart_rev); ?>, 
                    backgroundColor: 'rgba(40, 167, 69, 0.7)', // Xanh lá
                    borderColor: '#28a745', borderWidth: 1, order: 2
                },
                { 
                    label: 'Vốn Nhập (Chi)', 
                    data: <?php echo json_encode($chart_import_cost); ?>, 
                    backgroundColor: 'rgba(220, 53, 69, 0.7)', // Đỏ
                    borderColor: '#dc3545', borderWidth: 1, order: 3
                },
                { 
                    label: 'Lợi Nhuận (Lãi)', 
                    data: <?php echo json_encode($chart_profit); ?>, 
                    type: 'line', borderColor: '#6f42c1', backgroundColor: 'rgba(111, 66, 193, 0.1)', // Tím
                    borderWidth: 3, tension: 0.3, pointRadius: 4, order: 1 
                }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, 
            plugins: { tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); } } } } 
        }
    });

    // 2. Chart Tròn: Tỷ trọng danh mục
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{ data: <?php echo json_encode($pie_data); ?>, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Chart Line: Doanh thu theo ngày trong tháng
    new Chart(document.getElementById('monthChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($month_days_labels); ?>,
            datasets: [{ label: 'Doanh thu ngày', data: <?php echo json_encode($month_days_rev); ?>, borderColor: '#6f42c1', backgroundColor: 'rgba(111, 66, 193, 0.1)', borderWidth: 2, pointRadius: 3, fill: true, tension: 0.2 }]
        },
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Ngày' } }, y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(c) { return new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); } } } } }
    });

    // 4. Chart Bar: Tổng kết năm
    new Chart(document.getElementById('yearChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($year_labels); ?>,
            datasets: [
                { label: 'Doanh thu', data: <?php echo json_encode($year_rev); ?>, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: '#36a2eb', borderWidth: 1, order: 2 },
                { label: 'Lợi nhuận', data: <?php echo json_encode($year_prof); ?>, type: 'line', borderColor: '#ff6384', backgroundColor: 'rgba(255, 99, 132, 0.2)', borderWidth: 3, tension: 0.3, order: 1 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); } } } } }
    });

    // 5. Chart Stacked Bar: Nhập/Xuất Kho
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

    // --- SCRIPT HIỂN THỊ TOAST (NẾU CÓ THÔNG BÁO) ---
    <?php if(!empty($toast_msg)): ?>
        var x = document.getElementById("toast");
        x.className = "show";
        // Ẩn sau 3 giây
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
        // Xóa param query trên URL để tránh refresh hiện lại
        if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }
    <?php endif; ?>
</script>

<?php 
echo '</div>'; echo '</div>'; // Đóng các div wrapper
?>