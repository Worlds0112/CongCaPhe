<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

// --- 1. XỬ LÝ NẠP TIỀN VÀO QUỸ ---
$toast_msg = ""; // Biến chứa thông báo
if (isset($_POST['btn_add_fund'])) {
    $amount = (float)$_POST['fund_amount'];
    $note = $_POST['fund_note'];
    
    if ($amount != 0) {
        $sql_fund = "INSERT INTO funds (amount, note) VALUES ('$amount', '$note')";
        mysqli_query($conn, $sql_fund);
        // Lưu thông báo để hiển thị ở dưới cùng trang
        $toast_msg = "✅ Đã nạp thành công " . number_format($amount) . "đ vào quỹ!";
    }
}

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

date_default_timezone_set('Asia/Ho_Chi_Minh');
$today = date('Y-m-d');
$this_month = date('Y-m'); 
$current_year = date('Y');
$current_hour = date('H');

// --- HÀM HỖ TRỢ RÚT GỌN ---
function get_val($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($result);
    return $row[0] ?? 0;
}

// --- 2. XÁC ĐỊNH CA ---
$shift_name = "";
$current_shift_code = ""; // <--- QUAN TRỌNG: Khai báo biến này để truyền vào link

if ($current_hour >= 6 && $current_hour < 12) {
    $shift_start = "$today 06:00:00"; 
    $shift_end = "$today 12:00:00"; 
    $shift_name = "CA SÁNG";
    $current_shift_code = "sang"; // Gán mã ca
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $shift_start = "$today 12:00:00"; 
    $shift_end = "$today 18:00:00"; 
    $shift_name = "CA CHIỀU";
    $current_shift_code = "chieu"; // Gán mã ca
} else {
    $shift_start = "$today 18:00:00"; 
    $shift_end = "$today 23:59:59"; 
    $shift_name = "CA TỐI";
    $current_shift_code = "toi"; // Gán mã ca
}

// --- 3. TÍNH TOÁN SỐ LIỆU ---

// A. TỔNG QUỸ (ALL TIME)
$total_fund_in = get_val($conn, "SELECT SUM(amount) FROM funds");
$total_revenue_all = get_val($conn, "SELECT SUM(total_amount) FROM orders");
$total_import_cost_all = get_val($conn, "SELECT SUM(quantity * import_price) FROM inventory_history WHERE quantity > 0");
$total_profit_all = get_val($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id");
// -------------------------------------------------------------
// => Tiền hiện có trong két
$current_balance = ($total_fund_in + $total_revenue_all) - $total_import_cost_all;

// B. CA HIỆN TẠI
$rev_shift = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE order_date >= '$shift_start' AND order_date <= '$shift_end'"))['t'] ?? 0;
$cost_import_shift = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity * import_price) as cost FROM inventory_history WHERE created_at >= '$shift_start' AND created_at <= '$shift_end' AND quantity > 0"))['cost'] ?? 0;
$prof_shift = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE o.order_date >= '$shift_start' AND o.order_date <= '$shift_end'"))['p'] ?? 0;

// C. HÔM NAY
$rev_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$today'"))['t'] ?? 0;
$prof_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['p'] ?? 0;
$sold_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$today'"))['qty'] ?? 0;
$import_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE(created_at) = '$today'"))['qty'] ?? 0;
$cost_import_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity * import_price) as cost FROM inventory_history WHERE DATE(created_at) = '$today' AND quantity > 0"))['cost'] ?? 0;

// D. THÁNG NÀY
$rev_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$this_month'"))['t'] ?? 0;
$prof_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'"))['p'] ?? 0;
$sold_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as qty FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month'"))['qty'] ?? 0;
$import_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as qty FROM inventory_history WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month'"))['qty'] ?? 0;
$cost_import_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity * import_price) as cost FROM inventory_history WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month' AND quantity > 0"))['cost'] ?? 0;

$low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE stock <= 5"))['c'];

// --- 4. DỮ LIỆU BIỂU ĐỒ 7 NGÀY (Cập nhật: Thu - Chi - Lãi) ---
$chart_labels = []; 
$chart_rev = [];          // Doanh thu
$chart_import_cost = [];  // Vốn nhập
$chart_profit = [];       // Lợi nhuận
$chart_import_qty = [];   // Số lượng nhập (MỚI)
$chart_sold_qty = [];     // Số lượng bán (MỚI)

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d));

    // 1. Lấy Doanh thu
    $chart_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$d'"))['t'] ?? 0;

    // 2. Lấy Tiền Nhập Hàng
    $chart_import_cost[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity * import_price) as cost FROM inventory_history WHERE DATE(created_at) = '$d' AND quantity > 0"))['cost'] ?? 0;

    // 3. Lấy Lợi Nhuận
    $chart_profit[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'"))['p'] ?? 0;

    // 4. Lấy SỐ LƯỢNG NHẬP (Code cũ bị thiếu cái này nên biểu đồ kho không hiện)
    $chart_import_qty[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as q FROM inventory_history WHERE DATE(created_at) = '$d' AND quantity > 0"))['q'] ?? 0;

    // 5. Lấy SỐ LƯỢNG BÁN RA (Code cũ bị thiếu cái này)
    $chart_sold_qty[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(od.quantity) as q FROM order_details od JOIN orders o ON od.order_id = o.id WHERE DATE(o.order_date) = '$d'"))['q'] ?? 0;
}

$pie_labels = []; $pie_data = [];
$q_pie = mysqli_query($conn, "SELECT c.name, SUM(od.price * od.quantity) as total FROM order_details od JOIN products p ON od.product_id = p.id JOIN categories c ON p.category_id = c.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$this_month' GROUP BY c.id");
while($row = mysqli_fetch_assoc($q_pie)) { $pie_labels[] = $row['name']; $pie_data[] = $row['total']; }

$month_days_labels = []; $month_days_rev = [];
$num_days = date('t');
for ($i = 1; $i <= $num_days; $i++) {
    $month_days_labels[] = $i;
    $d_check = date('Y-m-') . sprintf("%02d", $i);
    $month_days_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date) = '$d_check'"))['t'] ?? 0;
}

$year_labels = []; $year_rev = []; $year_prof = [];
for ($m = 1; $m <= 12; $m++) {
    $ym = $current_year . '-' . sprintf("%02d", $m);
    $year_labels[] = "Thg $m";
    $year_rev[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$ym'"))['t'] ?? 0;
    $year_prof[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM((od.price - p.original_price) * od.quantity) as p FROM order_details od JOIN products p ON od.product_id = p.id JOIN orders o ON od.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = '$ym'"))['p'] ?? 0;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Toast Notification Style */
    #toast {
        visibility: hidden;
        min-width: 250px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 8px;
        padding: 16px;
        position: fixed;
        z-index: 9999;
        left: 50%;
        bottom: 30px;
        transform: translateX(-50%);
        font-size: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        display: flex; align-items: center; gap: 10px;
    }
    #toast.show {
        visibility: visible;
        -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
        animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    @-webkit-keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
    @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
    @-webkit-keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
    @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
    .stats-section { margin-bottom: 30px; }
    .section-title { font-size: 15px; color: #555; margin-bottom: 15px; font-weight: bold; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
    .section-title span { width: 4px; height: 18px; background: #333; display: inline-block; border-radius: 2px; }
    
    /* Grid Layout Gọn Gàng */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    
    .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #eee; transition: 0.3s; text-decoration: none; color: inherit; display: block; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    
    .stat-label { font-size: 13px; color: #777; text-transform: uppercase; font-weight: bold; }
    .stat-number { font-size: 24px; font-weight: bold; color: #333; margin: 5px 0; }
    .stat-icon { font-size: 24px; margin-bottom: 5px; display: block; }
    .stat-desc { font-size: 12px; color: #999; }

    /* MÀU SẮC GIAO DIỆN CŨ */
    .c-shift { border-bottom-color: #007bff; color: #007bff; background: #f0f7ff; }
    .c-day-money { border-bottom-color: #28a745; color: #28a745; }
    .c-day-stock { border-bottom-color: #fd7e14; color: #fd7e14; }
    .c-month { border-bottom-color: #6f42c1; color: #6f42c1; }
    .c-alert { border-bottom-color: #dc3545; color: #dc3545; }

    /* CSS CHO Ô TỔNG QUỸ ĐẶC BIỆT */
    .fund-card {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(30, 60, 114, 0.3);
        margin-bottom: 30px;
        display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;
    }
    .fund-info h3 { margin: 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; }
    .fund-number { font-size: 42px; font-weight: bold; margin: 5px 0 10px 0; }
    .fund-detail { font-size: 13px; opacity: 0.8; background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 20px; display: inline-block;}
    
    .fund-form { display: flex; gap: 10px; background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; }
    .fund-form input { padding: 8px; border: none; border-radius: 4px; outline: none; }
    .fund-form button { padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .fund-form button:hover { background: #218838; }

    .charts-row-top { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 40px; }
    .chart-box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .chart-header { font-weight: bold; color: #333; margin-bottom: 15px; border-left: 4px solid #5B743A; padding-left: 10px; }
    .chart-full-width { margin-top: 20px; }
    .charts-row-bottom { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
    @media (max-width: 900px) { .charts-row-top, .charts-row-bottom { grid-template-columns: 1fr; } }
</style>

<h2 style="border-left: 5px solid #28a745; padding-left: 15px; margin-bottom: 20px;">Trung Tâm Thống Kê</h2>

<div class="fund-card">
    <div class="fund-info" style="flex: 1;">
        <h3>💰 Tổng Quỹ Tiền Mặt Hiện Có</h3>
        <div class="fund-number"><?php echo number_format($current_balance); ?> ₫</div>
        
        <div style="display: flex; gap: 20px; margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">
            <div>
                <small style="opacity: 0.8; font-size: 11px;">TỔNG BÁN</small><br>
                <strong style="font-size: 15px;">+<?php echo number_format($total_revenue_all); ?></strong>
            </div>
            
            <div style="border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px;">
                <small style="opacity: 0.8; font-size: 11px;">TỔNG NHẬP</small><br>
                <strong style="font-size: 15px; color: #ffbaba;">-<?php echo number_format($total_import_cost_all); ?></strong>
            </div>

            <div style="border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px;">
                <small style="opacity: 0.8; font-size: 11px;">TỔNG LÃI</small><br>
                <strong style="font-size: 15px; color: #a3ffac;">+<?php echo number_format($total_profit_all); ?></strong>
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
    
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-shift">
            <span class="stat-icon">⚡</span> 
            <div class="stat-label">Doanh thu Ca</div>
            <div class="stat-number"><?php echo number_format($rev_shift); ?> ₫</div>
            <div class="stat-desc">Đang bán hiện tại</div>
        </a>

        <a href="inventory_history.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>&shift=<?php echo $current_shift_code; ?>" class="stat-card c-alert">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập (Ca)</div>
            <div class="stat-number"><?php echo number_format($cost_import_shift); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra trong ca</div>
        </a>

        <div class="stat-card c-shift" style="border-bottom-color: #17a2b8; color: #17a2b8;">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Ca)</div>
            <div class="stat-number"><?php echo number_format($rev_shift - $cost_import_shift); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-shift">
            <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp (Ca)</div>
            <div class="stat-number"><?php echo number_format($prof_shift); ?> ₫</div>
            <div class="stat-desc">Lãi trên đơn ca này</div>
        </a>
    </div>
</div>

<div class="stats-section">
    <div class="section-title"><span style="background: #28a745;"></span> HÔM NAY (<?php echo date('d/m'); ?>)</div>
    
    <div class="stats-grid">
        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-day-money">
            <span class="stat-icon">⚡</span>
            <div class="stat-label">Doanh thu Ngày</div> 
            <div class="stat-number"><?php echo number_format($rev_today); ?> ₫</div> 
            <div class="stat-desc">Tổng doanh số bán</div>
        </a>

        <a href="inventory_history.php?day=<?php echo date('d'); ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-alert">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($cost_import_today); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra hôm nay</div>
        </a>

        <div class="stat-card" style="border-bottom: 4px solid #007bff; color: #007bff;">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($rev_today - $cost_import_today); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card" style="border-bottom: 4px solid #6f42c1; color: #6f42c1;">
            <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($prof_today); ?> ₫</div>
            <div class="stat-desc">Lãi trên đơn hàng</div>
        </a>

        <a href="order_list.php?date=<?php echo $today; ?>" class="stat-card c-day-stock">
            <span class="stat-icon">☕</span>
            <div class="stat-label">Bán Ra (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($sold_today); ?></div>
            <div class="stat-desc">Đơn vị: Món/Ly</div>
        </a>

        <a href="inventory_history.php?date=<?php echo $today; ?>" class="stat-card c-day-stock" style="border-bottom-color: #17a2b8; color: #17a2b8;">
            <span class="stat-icon">🚚</span>
            <div class="stat-label">Nhập Kho (Ngày)</div> 
            <div class="stat-number"><?php echo number_format($import_today); ?></div>
            <div class="stat-desc">Hàng mới về</div>
        </a>

        <a href="product_list.php?view=low" class="stat-card c-alert">
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
        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month">
            <span class="stat-icon">⚡</span>
            <div class="stat-label">Doanh thu Tháng</div>
            <div class="stat-number"><?php echo number_format($rev_month); ?> ₫</div>
            <div class="stat-desc">Tổng thu</div>
        </a>

        <a href="inventory_history.php?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="stat-card c-alert">
            <span class="stat-icon">📦</span>
            <div class="stat-label">Vốn Nhập Tháng</div>
            <div class="stat-number"><?php echo number_format($cost_import_month); ?> ₫</div>
            <div class="stat-desc">Tiền chi ra</div>
        </a>

        <div class="stat-card c-month" style="border-bottom-color: #007bff; color: #007bff;">
            <span class="stat-icon">💰</span>
            <div class="stat-label">Dòng Tiền (Tháng)</div>
            <div class="stat-number"><?php echo number_format($rev_month - $cost_import_month); ?> ₫</div>
            <div class="stat-desc">Thực thu - Thực chi</div>
        </div>

        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month">
            <span class="stat-icon">📈</span>
            <div class="stat-label">Lợi Nhuận Gộp</div>
            <div class="stat-number"><?php echo number_format($prof_month); ?> ₫</div>
            <div class="stat-desc">Lãi ròng (trên đơn)</div>
        </a>

        <a href="order_list.php?month=<?php echo $this_month; ?>" class="stat-card c-month" style="border-bottom-color: #fd7e14; color: #fd7e14;">
            <span class="stat-icon">☕</span>
            <div class="stat-label">Tổng Bán Tháng</div>
            <div class="stat-number"><?php echo number_format($sold_month); ?></div>
            <div class="stat-desc">Ly/Món</div>
        </a>

        <a href="inventory_history.php?month=<?php echo $this_month; ?>" class="stat-card c-month" style="border-bottom-color: #17a2b8; color: #17a2b8;">
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
    // 1. Chart Tài Chính 7 Ngày (Đã sửa)
    new Chart(document.getElementById('comboChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                { 
                    label: 'Doanh thu (Thu)', 
                    data: <?php echo json_encode($chart_rev); ?>, 
                    backgroundColor: 'rgba(40, 167, 69, 0.7)', // Màu xanh lá
                    borderColor: '#28a745', 
                    borderWidth: 1,
                    order: 2
                },
                { 
                    label: 'Vốn Nhập (Chi)', 
                    data: <?php echo json_encode($chart_import_cost); ?>, 
                    backgroundColor: 'rgba(220, 53, 69, 0.7)', // Màu đỏ
                    borderColor: '#dc3545', 
                    borderWidth: 1,
                    order: 3
                },
                { 
                    label: 'Lợi Nhuận (Lãi)', 
                    data: <?php echo json_encode($chart_profit); ?>, 
                    type: 'line', // Vẽ đường dây đè lên cột
                    borderColor: '#6f42c1', // Màu tím
                    backgroundColor: 'rgba(111, 66, 193, 0.1)', 
                    borderWidth: 3, 
                    tension: 0.3, 
                    pointRadius: 4,
                    order: 1 
                }
            ]
        },
        options: { 
            responsive: true, 
            scales: { 
                y: { beginAtZero: true } // Chung 1 trục Y tiền tệ
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(c) { 
                            // Định dạng tiền tệ VNĐ trong tooltip
                            return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); 
                        }
                    }
                }
            }
        }
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
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Ngày' } }, y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(c) { return new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); } } } } }
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
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', {style:'currency', currency:'VND'}).format(c.raw); } } } } }
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
    // Kiểm tra nếu có tin nhắn từ PHP thì hiện Toast lên
    <?php if(!empty($toast_msg)): ?>
        var x = document.getElementById("toast");
        x.className = "show";
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
        
        // Xóa query param để tránh refresh lại bị hiện lại (nếu muốn)
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    <?php endif; ?>
</script>

<?php 
echo '</div>'; echo '</div>';
?>