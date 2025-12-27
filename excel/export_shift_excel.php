<?php
require '../includes/auth_admin.php'; 

// NHẬN BỘ LỌC (Giống trang history)
$filter_shift = isset($_GET['shift']) ? $_GET['shift'] : ''; 
$filter_day   = isset($_GET['day']) ? $_GET['day'] : "";    
$filter_month = isset($_GET['month']) ? $_GET['month'] : ""; 
$filter_year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
if ($filter_year == 'all') $filter_year = '';

$where_sql = "WHERE 1=1";
if (!empty($filter_shift) && $filter_shift != 'all') $where_sql .= " AND r.shift_code = '$filter_shift'";
if (!empty($filter_day))   $where_sql .= " AND DAY(r.report_date) = '$filter_day'";
if (!empty($filter_month)) $where_sql .= " AND MONTH(r.report_date) = '$filter_month'";
if (!empty($filter_year))  $where_sql .= " AND YEAR(r.report_date) = '$filter_year'";

// QUERY
$sql = "SELECT r.*, u.full_name 
        FROM shift_reports r 
        JOIN users u ON r.user_id = u.id 
        $where_sql 
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);

// HEADER EXCEL
$filename = "BaoCaoCa_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF"; 
?>

<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: 'Times New Roman', serif; font-size: 12pt;">
<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
    <tr style="height: 40px; background-color: #ffc107;">
        <td colspan="8" align="center" style="font-size: 16pt; font-weight: bold;">LICH SU KET CA & BAN GIAO</td>
    </tr>
    <tr style="background-color: #eee; font-weight: bold;">
        <td align="center">STT</td>
        <td align="center">Ngay Bao Cao</td>
        <td align="center">Ca</td>
        <td align="center">Nhan Vien</td>
        <td align="center">Doanh Thu May</td>
        <td align="center">Tien Mat Thuc Te</td>
        <td align="center">Chenh Lech</td>
        <td align="center">Ghi Chu</td>
    </tr>
    <?php 
    $stt = 1;
    while ($row = mysqli_fetch_assoc($result)): 
        $shift = $row['shift_code'] == 'sang' ? 'Sang' : ($row['shift_code'] == 'chieu' ? 'Chieu' : 'Toi');
        $color = ($row['difference'] < 0) ? 'red' : 'black';
    ?>
    <tr>
        <td align="center"><?php echo $stt++; ?></td>
        <td align="center"><?php echo date('d/m/Y', strtotime($row['report_date'])); ?></td>
        <td align="center"><?php echo $shift; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td align="right"><?php echo number_format($row['system_revenue']); ?></td>
        <td align="right"><?php echo number_format($row['real_cash']); ?></td>
        <td align="right" style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo number_format($row['difference']); ?></td>
        <td>
            <?php echo "Kho: " . $row['inventory_notes'] . " | Chung: " . $row['notes']; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>