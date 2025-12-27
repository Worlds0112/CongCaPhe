<?php
// 1. KẾT NỐI & BẢO VỆ
require '../includes/auth_admin.php'; 

// 2. LẤY ID HÓA ĐƠN
$order_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) die("ID hóa đơn không hợp lệ.");

// 3. LẤY THÔNG TIN ĐƠN HÀNG
$sql_order = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
              FROM orders
              JOIN users ON orders.user_id = users.id
              WHERE orders.id = ?";
$stmt = mysqli_prepare($conn, $sql_order);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) die("Không tìm thấy hóa đơn này.");

// 4. LẤY CHI TIẾT SẢN PHẨM
$sql_details = "SELECT p.name, od.quantity, od.price, (od.quantity * od.price) as line_total
                FROM order_details od
                JOIN products p ON od.product_id = p.id
                WHERE od.order_id = ?";
$stmt_details = mysqli_prepare($conn, $sql_details);
mysqli_stmt_bind_param($stmt_details, "i", $order_id);
mysqli_stmt_execute($stmt_details);
$result_details = mysqli_stmt_get_result($stmt_details);

// 5. THIẾT LẬP HEADER EXCEL
$filename = "HoaDon_#" . $order_id . "_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF"; // BOM fix lỗi font tiếng Việt
?>

<html>
<head>
<meta charset="UTF-8">
</head>
<body style="font-family: 'Times New Roman', serif; font-size: 12pt;">

<table border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <col width="50">
    <col width="300">
    <col width="80">
    <col width="100">
    <col width="120">

    <tr style="height: 40px;">
        <td colspan="5" align="center" style="background-color: #548235; color: #ffffff; font-size: 18pt; font-weight: bold; vertical-align: middle;">
            CONG CA PHE
        </td>
    </tr>
    <tr style="height: 25px;">
        <td colspan="5" align="center" style="background-color: #70AD47; color: #ffffff; font-size: 11pt; font-style: italic; vertical-align: middle;">
            He thong Quan ly - Phieu Thanh Toan
        </td>
    </tr>
    
    <tr><td colspan="5"></td></tr>
    
    <tr>
        <td colspan="5" align="center" style="font-size: 16pt; font-weight: bold; padding: 10px 0;">HOA DON BAN HANG</td>
    </tr>
    <tr>
        <td colspan="5" align="center">Mã hóa đơn: #<?php echo $order['id']; ?></td>
    </tr>
    <tr>
        <td colspan="5" align="center">Ngày lập: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
    </tr>
    <tr>
        <td colspan="5" align="center">Nhân viên: <?php echo $order['full_name']; ?></td>
    </tr>

    <tr><td colspan="5"></td></tr>

    <tr style="height: 30px;">
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">STT</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">Ten San Pham</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">So<br>Luong</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">Don Gia</td>
        <td align="center" style="background-color: #E2EFDA; border: 1px solid #000000; font-weight: bold; vertical-align: middle;">Thanh Tien</td>
    </tr>

    <?php 
    $stt = 1;
    $total_check = 0;
    while ($row = mysqli_fetch_assoc($result_details)): 
        $total_check += $row['line_total'];
    ?>
    <tr style="height: 25px;">
        <td align="center" style="border: 1px solid #000000; vertical-align: middle;"><?php echo $stt++; ?></td>
        <td align="left"   style="border: 1px solid #000000; vertical-align: middle; padding-left: 5px;"><?php echo $row['name']; ?></td>
        <td align="center" style="border: 1px solid #000000; vertical-align: middle;"><?php echo $row['quantity']; ?></td>
        <td align="right"  style="border: 1px solid #000000; vertical-align: middle; padding-right: 5px;"><?php echo number_format($row['price']); ?></td>
        <td align="right"  style="border: 1px solid #000000; vertical-align: middle; padding-right: 5px;"><?php echo number_format($row['line_total']); ?></td>
    </tr>
    <?php endwhile; ?>

    <?php for($i=0; $i<3; $i++): ?>
    <tr style="height: 25px;">
        <td style="border: 1px solid #000000;"></td>
        <td style="border: 1px solid #000000;"></td>
        <td style="border: 1px solid #000000;"></td>
        <td style="border: 1px solid #000000;"></td>
        <td style="border: 1px solid #000000;"></td>
    </tr>
    <?php endfor; ?>

    <tr style="height: 30px;">
        <td colspan="4" align="right" style="background-color: #548235; color: #ffffff; border: 1px solid #000000; font-weight: bold; vertical-align: middle; padding-right: 10px;">
            TONG THANH TOAN (VND):
        </td>
        <td align="right" style="background-color: #548235; color: #ffffff; border: 1px solid #000000; font-weight: bold; vertical-align: middle; padding-right: 5px;">
            <?php echo number_format($total_check); ?>
        </td>
    </tr>
    
    <tr><td colspan="5" height="20"></td></tr>
    <tr>
        <td colspan="2" align="center" style="font-weight: bold;">KHACH HANG</td>
        <td></td>
        <td colspan="2" align="center" style="font-weight: bold;">NHAN VIEN THU NGAN</td>
    </tr>
    <tr>
        <td colspan="2" align="center" style="font-size: 10pt; color: #555555;">(Ky ten)</td>
        <td></td>
        <td colspan="2" align="center" style="font-size: 10pt; color: #555555;">(Ky ten)</td>
    </tr>
    <tr><td colspan="5" height="50"></td></tr>
    <tr>
        <td colspan="5" align="center" style="font-size: 10pt; color: #888888; font-style: italic;">
            Cam on quy khach va hen gap lai!<br>
            Hotline: 1900 xxxx - Website: congcaphe.com
        </td>
    </tr>

</table>
</body>
</html>