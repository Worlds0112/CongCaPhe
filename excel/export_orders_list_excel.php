<?php
// 1. KẾT NỐI & BẢO VỆ
require '../includes/auth_admin.php'; 

// 2. NHẬN BỘ LỌC TỪ URL (Copy y nguyên logic từ order_list.php)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : ''; 
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";    
$filter_month = isset($_GET['month']) ? $_GET['month'] : ""; 
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y'); 
if ($filter_year == 'all') $filter_year = '';

// 3. XÂY DỰNG QUERY (Không có LIMIT)
$where_clause = " WHERE 1=1";

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_clause .= " AND (orders.id LIKE '%$s%' OR users.full_name LIKE '%$s%')";
}
if (!empty($filter_shift) && $filter_shift != 'all') {
    $where_clause .= " AND users.shift = '$filter_shift'";
}
if (!empty($filter_day))   $where_clause .= " AND DAY(orders.order_date) = '$filter_day'";
if (!empty($filter_month)) $where_clause .= " AND MONTH(orders.order_date) = '$filter_month'";
if (!empty($filter_year))  $where_clause .= " AND YEAR(orders.order_date) = '$filter_year'";

// Query lấy dữ liệu (Kèm tính toán giá vốn/lợi nhuận)
$sql = "SELECT 
            orders.id, 
            orders.order_date, 
            orders.total_amount, 
            users.full_name, 
            users.shift,
            COALESCE(SUM(order_details.quantity * products.original_price), 0) as calculated_cost
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        LEFT JOIN order_details ON orders.id = order_details.order_id
        LEFT JOIN products ON order_details.product_id = products.id
        $where_clause
        GROUP BY orders.id
        ORDER BY orders.order_date DESC";

$result = mysqli_query($conn, $sql);

// 4. THIẾT LẬP HEADER EXCEL
$filename = "DanhSachHoaDon_" . date('Ymd_His') . ".xls";
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
    <col width="50">  <col width="80">  <col width="150"> <col width="200"> <col width="80">  <col width="120"> <col width="120"> <col width="120"> <tr style="height: 40px;">
        <td colspan="8" align="center" style="background-color: #548235; color: #ffffff; font-size: 18pt; font-weight: bold; vertical-align: middle;">
            CONG CA PHE - QUAN LY
        </td>
    </tr>
    <tr><td colspan="8"></td></tr>
    
    <tr>
        <td colspan="8" align="center" style="font-size: 16pt; font-weight: bold;">BAO CAO DANH SACH HOA DON</td>
    </tr>
    <tr>
        <td colspan="8" align="center">
            Thời gian xuất: <?php echo date('d/m/Y H:i'); ?>
        </td>
    </tr>
    <tr><td colspan="8"></td></tr>

    <tr style="height: 30px;">
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">STT</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Ma HD</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Thoi Gian</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Nhan Vien</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Ca</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Doanh Thu</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Gia Von</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Loi Nhuan</td>
    </tr>

    <?php 
    $stt = 1;
    $total_revenue = 0;
    $total_cost = 0;
    $total_profit = 0;
    
    while ($row = mysqli_fetch_assoc($result)): 
        $revenue = $row['total_amount'];
        $cost    = $row['calculated_cost'];
        $profit  = $revenue - $cost;

        // Cộng dồn tổng
        $total_revenue += $revenue;
        $total_cost    += $cost;
        $total_profit  += $profit;

        // Xử lý tên Ca
        $shift_name = $row['shift'];
        if($row['shift'] == 'sang') $shift_name = "Sang";
        elseif($row['shift'] == 'chieu') $shift_name = "Chieu";
        elseif($row['shift'] == 'toi') $shift_name = "Toi";
    ?>
    <tr style="height: 25px;">
        <td align="center" style="border: 1px solid #000;"><?php echo $stt++; ?></td>
        <td align="center" style="border: 1px solid #000;">#<?php echo $row['id']; ?></td>
        <td align="center" style="border: 1px solid #000;"><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td>
        <td align="left"   style="border: 1px solid #000; padding-left: 5px;"><?php echo $row['full_name']; ?></td>
        <td align="center" style="border: 1px solid #000; text-transform: capitalize;"><?php echo $shift_name; ?></td>
        <td align="right" style="border: 1px solid #000; padding-right: 5px; color: #006400;"><?php echo number_format($revenue); ?></td>
        <td align="right" style="border: 1px solid #000; padding-right: 5px; color: #8B4500;"><?php echo number_format($cost); ?></td>
        <td align="right" style="border: 1px solid #000; padding-right: 5px; font-weight: bold; color: #00008B;">
            <?php echo number_format($profit); ?>
        </td>
    </tr>
    <?php endwhile; ?>

    <tr><td colspan="8"></td></tr>
    <tr style="height: 35px;">
        <td colspan="5" align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 10px;">
            TONG CONG (VND):
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($total_revenue); ?>
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($total_cost); ?>
        </td>
        <td align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($total_profit); ?>
        </td>
    </tr>
    
    <tr><td colspan="8" height="20"></td></tr>
    <tr>
        <td colspan="3" align="center"><b>NGUOI LAP BAO CAO</b></td>
        <td colspan="2"></td>
        <td colspan="3" align="center"><b>QUAN LY</b></td>
    </tr>
    <tr>
        <td colspan="3" align="center" style="color:#555; font-size:10pt;">(Ky ten)</td>
        <td colspan="2"></td>
        <td colspan="3" align="center" style="color:#555; font-size:10pt;">(Ky ten)</td>
    </tr>

</table>
</body>
</html>