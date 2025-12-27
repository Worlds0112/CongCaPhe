<?php
// 1. KẾT NỐI
require '../includes/auth_admin.php'; 

// 2. NHẬN BỘ LỌC
$day   = isset($_GET['day']) && $_GET['day'] != '' ? (int)$_GET['day'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$shift = isset($_GET['shift']) ? $_GET['shift'] : '';

// 3. XÂY DỰNG ĐIỀU KIỆN LỌC
$where_sql = "WHERE MONTH(o.order_date) = '$month' AND YEAR(o.order_date) = '$year'";

// Lọc theo ngày (Nếu có chọn)
if ($day) {
    $where_sql .= " AND DAY(o.order_date) = '$day'";
}

// Lọc theo Ca (Dựa trên giờ)
$shift_name = ""; // Để hiển thị tiêu đề
if ($shift == 'sang') {
    $where_sql .= " AND HOUR(o.order_date) >= 6 AND HOUR(o.order_date) < 12";
    $shift_name = "(Ca Sáng)";
} elseif ($shift == 'chieu') {
    $where_sql .= " AND HOUR(o.order_date) >= 12 AND HOUR(o.order_date) < 18";
    $shift_name = "(Ca Chiều)";
} elseif ($shift == 'toi') {
    $where_sql .= " AND HOUR(o.order_date) >= 18";
    $shift_name = "(Ca Tối)";
}

// 4. QUERY DỮ LIỆU
$sql = "SELECT 
            DATE(o.order_date) as report_date,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_revenue,
            SUM(
                (SELECT COALESCE(SUM(od.quantity * p.original_price), 0) 
                 FROM order_details od 
                 JOIN products p ON od.product_id = p.id 
                 WHERE od.order_id = o.id)
            ) as total_cost
        FROM orders o
        $where_sql
        GROUP BY DATE(o.order_date)
        ORDER BY report_date ASC";

$result = mysqli_query($conn, $sql);

$data_by_date = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_by_date[$row['report_date']] = $row;
}

// 5. HEADER EXCEL
$filename = "BaoCao_Thang".$month."_".$year. ($day ? "_Ngay$day" : "") . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF"; 
?>

<html>
<head>
<meta charset="UTF-8">
</head>
<body style="font-family: 'Times New Roman', serif; font-size: 12pt;">

<table border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <col width="50">
    <col width="120">
    <col width="100">
    <col width="150">
    <col width="150">
    <col width="150">

    <tr style="height: 40px;">
        <td colspan="6" align="center" style="background-color: #548235; color: #ffffff; font-size: 18pt; font-weight: bold; vertical-align: middle;">
            CONG CA PHE - BAO CAO <?php echo strtoupper($shift_name); ?>
        </td>
    </tr>
    <tr><td colspan="6"></td></tr>
    
    <tr>
        <td colspan="6" align="center" style="font-size: 16pt; font-weight: bold;">
            <?php if($day): ?>
                BAO CAO NGAY <?php echo "$day/$month/$year"; ?>
            <?php else: ?>
                BAO CAO DOANH THU THANG <?php echo "$month/$year"; ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr><td colspan="6"></td></tr>

    <tr style="height: 30px;">
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">STT</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Ngay</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">So Don</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Doanh Thu</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Tien Von</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Loi Nhuan</td>
    </tr>

    <?php 
    // XỬ LÝ VÒNG LẶP THÔNG MINH
    // Nếu chọn ngày cụ thể -> Chỉ chạy 1 lần cho ngày đó
    // Nếu chọn cả tháng -> Chạy từ ngày 1 đến ngày cuối tháng
    
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $start_loop = ($day) ? $day : 1;
    $end_loop   = ($day) ? $day : $days_in_month;

    $stt = 1;
    $sum_orders = 0; $sum_revenue = 0; $sum_cost = 0; $sum_profit = 0;

    for ($d = $start_loop; $d <= $end_loop; $d++) {
        $current_date = sprintf("%04d-%02d-%02d", $year, $month, $d);
        
        $has_data = isset($data_by_date[$current_date]);
        $orders   = $has_data ? $data_by_date[$current_date]['total_orders'] : 0;
        $revenue  = $has_data ? $data_by_date[$current_date]['total_revenue'] : 0;
        $cost     = $has_data ? $data_by_date[$current_date]['total_cost'] : 0;
        $profit   = $revenue - $cost;

        $sum_orders  += $orders;
        $sum_revenue += $revenue;
        $sum_cost    += $cost;
        $sum_profit  += $profit;
        ?>
        <tr style="height: 25px;">
            <td align="center" style="border: 1px solid #000;"><?php echo $stt++; ?></td>
            <td align="center" style="border: 1px solid #000;"><?php echo date('d/m/Y', strtotime($current_date)); ?></td>
            <td align="center" style="border: 1px solid #000;"><?php echo ($orders > 0) ? $orders : '-'; ?></td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px;"><?php echo ($revenue > 0) ? number_format($revenue) : '-'; ?></td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px; color: #8B4500;"><?php echo ($cost > 0) ? number_format($cost) : '-'; ?></td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px; font-weight: bold; color: #006400;">
                <?php echo ($profit != 0) ? number_format($profit) : '-'; ?>
            </td>
        </tr>
    <?php } ?>

    <tr style="height: 35px;">
        <td colspan="2" align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 10px;">
            TONG CONG:
        </td>
        <td align="center" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold;">
            <?php echo number_format($sum_orders); ?>
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($sum_revenue); ?>
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($sum_cost); ?>
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($sum_profit); ?>
        </td>
    </tr>
    
    <tr><td colspan="6" height="20"></td></tr>
    <tr>
        <td colspan="3" align="center"><b>NGUOI LAP BAO CAO</b></td>
        <td colspan="3" align="center"><b>QUAN LY</b></td>
    </tr>
    <tr>
        <td colspan="3" align="center" style="color:#555; font-size:10pt;">(Ky ten)</td>
        <td colspan="3" align="center" style="color:#555; font-size:10pt;">(Ky ten)</td>
    </tr>

</table>
</body>
</html>