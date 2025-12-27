<?php
// 1. KẾT NỐI & BẢO VỆ
require '../includes/auth_admin.php'; 

// 2. NHẬN CÁC BỘ LỌC TỪ URL (Giống hệt trang xem lịch sử)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type']) ? $_GET['type'] : 'all'; 
$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : ''; 
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";    
$filter_month = isset($_GET['month']) ? $_GET['month'] : ""; 
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y'); 
if ($filter_year == 'all') $filter_year = '';

// 3. XÂY DỰNG QUERY (Không có LIMIT để xuất hết)
$where_sql = "WHERE 1=1";

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_sql .= " AND p.name LIKE '%$s%'";
}
if ($type == 'in') {
    $where_sql .= " AND h.quantity > 0";
} elseif ($type == 'out') {
    $where_sql .= " AND h.quantity < 0";
}

// Lọc Ca
if (!empty($filter_shift) && $filter_shift != 'all') {
    if ($filter_shift == 'sang')      $where_sql .= " AND HOUR(h.created_at) >= 6 AND HOUR(h.created_at) < 12";
    elseif ($filter_shift == 'chieu') $where_sql .= " AND HOUR(h.created_at) >= 12 AND HOUR(h.created_at) < 18";
    elseif ($filter_shift == 'toi')   $where_sql .= " AND HOUR(h.created_at) >= 18";
}

if (!empty($filter_day))   $where_sql .= " AND DAY(h.created_at) = '$filter_day'";
if (!empty($filter_month)) $where_sql .= " AND MONTH(h.created_at) = '$filter_month'";
if (!empty($filter_year))  $where_sql .= " AND YEAR(h.created_at) = '$filter_year'";

// Query dữ liệu
$sql_data = "SELECT h.*, p.name as product_name, p.id as prod_id 
             FROM inventory_history h 
             JOIN products p ON h.product_id = p.id 
             $where_sql 
             ORDER BY h.created_at DESC";
$result_data = mysqli_query($conn, $sql_data);

// 4. THIẾT LẬP HEADER EXCEL
$filename = "LichSuKho_" . date('Ymd_His') . ".xls";
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
    <col width="50">  <col width="150"> <col width="250"> <col width="100"> <col width="80">  <col width="100"> <col width="120"> <col width="150"> <tr style="height: 40px;">
        <td colspan="8" align="center" style="background-color: #548235; color: #ffffff; font-size: 18pt; font-weight: bold; vertical-align: middle;">
            CONG CA PHE - QUAN LY KHO
        </td>
    </tr>
    <tr><td colspan="8"></td></tr>
    
    <tr>
        <td colspan="8" align="center" style="font-size: 16pt; font-weight: bold;">BAO CAO NHAP - XUAT KHO</td>
    </tr>
    <tr>
        <td colspan="8" align="center">
            Thời gian xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
        </td>
    </tr>
    <tr><td colspan="8"></td></tr>

    <tr style="height: 30px;">
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">STT</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Thoi Gian</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">San Pham</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Loai GD</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">So Luong</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Gia Von</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Tong Tien</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000; font-weight: bold;">Ghi Chu</td>
    </tr>

    <?php 
    $stt = 1;
    $total_in_val = 0;
    $total_out_val = 0;
    
    while ($row = mysqli_fetch_assoc($result_data)): 
        $qty = (int)$row['quantity'];
        $is_import = $qty > 0;
        $price = isset($row['import_price']) ? $row['import_price'] : 0;
        $total_value = abs($qty) * $price;

        if ($is_import) $total_in_val += $total_value;
        else $total_out_val += $total_value;

        // Màu chữ cho loại GD
        $color_style = $is_import ? "color: #006400;" : "color: #8B0000;"; // Xanh đậm / Đỏ đậm
    ?>
    <tr style="height: 25px;">
        <td align="center" style="border: 1px solid #000;"><?php echo $stt++; ?></td>
        <td align="center" style="border: 1px solid #000;"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
        <td align="left"   style="border: 1px solid #000; padding-left: 5px;"><?php echo $row['product_name']; ?></td>
        <td align="center" style="border: 1px solid #000; font-weight: bold; <?php echo $color_style; ?>">
            <?php echo $is_import ? "NHAP" : "XUAT"; ?>
        </td>
        <td align="center" style="border: 1px solid #000; <?php echo $color_style; ?>">
            <?php echo $is_import ? "+".$qty : $qty; ?>
        </td>
        <td align="right" style="border: 1px solid #000; padding-right: 5px;"><?php echo number_format($price); ?></td>
        <td align="right" style="border: 1px solid #000; padding-right: 5px; font-weight: bold;">
            <?php echo number_format($total_value); ?>
        </td>
        <td align="left" style="border: 1px solid #000; padding-left: 5px; font-size: 10pt; color: #555;">
            <?php echo $row['note']; ?>
        </td>
    </tr>
    <?php endwhile; ?>

    <tr><td colspan="8"></td></tr>
    <tr style="height: 30px;">
        <td colspan="6" align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 10px;">
            TONG TIEN NHAP (+):
        </td>
        <td colspan="2" align="right" style="background-color: #548235; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($total_in_val); ?> VND
        </td>
    </tr>
    <tr style="height: 30px;">
        <td colspan="6" align="right" style="background-color: #A52A2A; color: white; border: 1px solid #000; font-weight: bold; padding-right: 10px;">
            TONG GIA TRI XUAT (-):
        </td>
        <td colspan="2" align="right" style="background-color: #A52A2A; color: white; border: 1px solid #000; font-weight: bold; padding-right: 5px;">
            <?php echo number_format($total_out_val); ?> VND
        </td>
    </tr>

</table>
</body>
</html>