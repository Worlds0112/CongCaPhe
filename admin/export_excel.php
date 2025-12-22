<?php
require '../includes/auth_admin.php';

// =================================================================
// 1. XỬ LÝ XUẤT BÁO CÁO THÁNG (Code cũ, giữ nguyên logic)
// =================================================================
if (isset($_POST['btn_export_month'])) {
    $month = sprintf("%02d", $_POST['month']);
    $year = $_POST['year'];
    $filename = "Bao_Cao_Thang_{$month}_{$year}.xls";

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
    ?>
    <table border="1">
        <thead>
            <tr>
                <th colspan="5"
                    style="background-color:#5B743A; color:white; font-size:18px; height:40px; text-align:center;">BÁO CÁO
                    DOANH THU THÁNG <?php echo "$month/$year"; ?></th>
            </tr>
            <tr style="background-color:#f0f0f0; text-align:center;">
                <th>Ngày</th>
                <th>Số đơn</th>
                <th>Số ly bán</th>
                <th>Doanh Thu</th>
                <th>Lợi Nhuận</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $num_days = date('t', strtotime("$year-$month-01"));
            $t_rev = 0;
            $t_prof = 0;
            $t_ord = 0;
            $t_qty = 0;

            for ($i = 1; $i <= $num_days; $i++) {
                $day = "$year-$month-" . sprintf("%02d", $i);
                $sql = "SELECT COUNT(DISTINCT o.id) as c, SUM(o.total_amount) as r, SUM(od.quantity) as q, SUM((od.price - p.cost_price)*od.quantity) as p 
                        FROM orders o LEFT JOIN order_details od ON o.id=od.order_id LEFT JOIN products p ON od.product_id=p.id WHERE DATE(o.order_date)='$day'";
                $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));

                // Chỉ hiện ngày có bán hàng
                if ($row['c'] > 0) {
                    echo "<tr>
                        <td style='text-align:center;'>" . date('d/m/Y', strtotime($day)) . "</td>
                        <td style='text-align:center;'>{$row['c']}</td>
                        <td style='text-align:center;'>{$row['q']}</td>
                        <td style='text-align:right;'>" . ($row['r']) . "</td>
                        <td style='text-align:right;'>" . ($row['p']) . "</td>
                    </tr>";
                    $t_rev += $row['r'];
                    $t_prof += $row['p'];
                    $t_ord += $row['c'];
                    $t_qty += $row['q'];
                }
            }
            ?>
            <tr style="background-color:#ffffcc; font-weight:bold;">
                <td style="text-align:right;">TỔNG:</td>
                <td style="text-align:center;"><?php echo $t_ord; ?></td>
                <td style="text-align:center;"><?php echo $t_qty; ?></td>
                <td style="text-align:right;"><?php echo $t_rev; ?></td>
                <td style="text-align:right;"><?php echo $t_prof; ?></td>
            </tr>
        </tbody>
    </table>
    <?php
    exit();
}

// =================================================================
// 2. XỬ LÝ XUẤT BÁO CÁO NGÀY (MỚI THÊM)
// =================================================================
if (isset($_POST['btn_export_day'])) {
    $date = $_POST['report_date']; // YYYY-MM-DD
    $filename = "Bao_Cao_Ngay_" . date('d_m_Y', strtotime($date)) . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
    ?>

    <table border="1">
        <thead>
            <tr>
                <th colspan="4"
                    style="background-color:#007bff; color:white; font-size:16px; height:40px; text-align:center;">
                    TỔNG HỢP MÓN BÁN RA - NGÀY <?php echo date('d/m/Y', strtotime($date)); ?>
                </th>
            </tr>
            <tr style="background-color:#e9ecef; font-weight:bold; text-align:center;">
                <th>STT</th>
                <th>Tên Món</th>
                <th>Số Lượng Bán</th>
                <th>Thành Tiền (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_prod = "SELECT p.name, SUM(od.quantity) as qty, SUM(od.price * od.quantity) as total
                         FROM order_details od
                         JOIN products p ON od.product_id = p.id
                         JOIN orders o ON od.order_id = o.id
                         WHERE DATE(o.order_date) = '$date'
                         GROUP BY p.id ORDER BY qty DESC";
            $res_prod = mysqli_query($conn, $sql_prod);
            $stt = 1;
            $total_day_rev = 0;

            if (mysqli_num_rows($res_prod) > 0) {
                while ($row = mysqli_fetch_assoc($res_prod)) {
                    echo "<tr>
                        <td style='text-align:center;'>$stt</td>
                        <td>{$row['name']}</td>
                        <td style='text-align:center;'>{$row['qty']}</td>
                        <td style='text-align:right;'>" . ($row['total']) . "</td>
                    </tr>";
                    $stt++;
                    $total_day_rev += $row['total'];
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>Không có dữ liệu bán hàng.</td></tr>";
            }
            ?>
            <tr style="background-color:#ffffcc; font-weight:bold;">
                <td colspan="3" style="text-align:right;">TỔNG DOANH THU NGÀY:</td>
                <td style="text-align:right; color:red;"><?php echo $total_day_rev; ?></td>
            </tr>
        </tbody>
    </table>

    <br><br>

    <table border="1">
        <thead>
            <tr>
                <th colspan="5"
                    style="background-color:#6c757d; color:white; font-size:16px; height:35px; text-align:center;">
                    CHI TIẾT ĐƠN HÀNG
                </th>
            </tr>
            <tr style="background-color:#e9ecef; font-weight:bold; text-align:center;">
                <th>Mã HĐ</th>
                <th>Giờ tạo</th>
                <th>Nhân viên</th>
                <th>Tổng tiền</th>
                <th>Chi tiết món</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_ord = "SELECT o.id, o.order_date, o.total_amount, u.full_name 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE DATE(o.order_date) = '$date' 
                        ORDER BY o.id DESC";
            $res_ord = mysqli_query($conn, $sql_ord);

            while ($order = mysqli_fetch_assoc($res_ord)) {
                // Lấy chi tiết món ăn của đơn này để hiển thị chung 1 dòng
                $oid = $order['id'];
                $sql_detail = "SELECT p.name, od.quantity FROM order_details od JOIN products p ON od.product_id=p.id WHERE od.order_id=$oid";
                $res_detail = mysqli_query($conn, $sql_detail);
                $items_str = [];
                while ($d = mysqli_fetch_assoc($res_detail)) {
                    $items_str[] = $d['name'] . " (x" . $d['quantity'] . ")";
                }
                $items_display = implode(", ", $items_str);

                echo "<tr>
                    <td style='text-align:center;'>#{$order['id']}</td>
                    <td style='text-align:center;'>" . date('H:i:s', strtotime($order['order_date'])) . "</td>
                    <td>{$order['full_name']}</td>
                    <td style='text-align:right;'>" . ($order['total_amount']) . "</td>
                    <td>$items_display</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <?php
    exit();
}
?>