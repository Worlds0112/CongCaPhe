<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';
require '../includes/auto_shift_check.php';

// Tên nhân viên
$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];

// 1. XÁC ĐỊNH CA (Giữ nguyên logic cũ)
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_hour = date('H');
$current_date = date('Y-m-d');
$current_shift = ''; $shift_label = ''; $start_time = ''; $end_time = '';

if ($current_hour >= 6 && $current_hour < 12) {
    $current_shift = 'sang'; $shift_label = 'CA SÁNG (06:00 - 12:00)';
    $start_time = "$current_date 06:00:00"; $end_time = "$current_date 12:00:00";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $current_shift = 'chieu'; $shift_label = 'CA CHIỀU (12:00 - 18:00)';
    $start_time = "$current_date 12:00:00"; $end_time = "$current_date 18:00:00";
} else {
    $current_shift = 'toi'; $shift_label = 'CA TỐI (18:00 - 23:00)';
    $start_time = "$current_date 18:00:00"; $end_time = "$current_date 23:59:59";
}

// 2. TÍNH DOANH THU TIỀN
$sql_rev = "SELECT SUM(total_amount) as total FROM orders WHERE order_date >= '$start_time' AND order_date <= '$end_time'";
$r_rev = mysqli_fetch_assoc(mysqli_query($conn, $sql_rev));
$system_revenue = $r_rev['total'] ? $r_rev['total'] : 0;

// 3. [MỚI] TÍNH TỔNG SỐ LƯỢNG MÓN ĐÃ BÁN (ĐỂ KIỂM KHO)
// Giả sử bảng chi tiết đơn hàng tên là 'order_details' (order_id, product_id, quantity)
$sql_items = "SELECT p.name, SUM(d.quantity) as qty
              FROM order_details d
              JOIN orders o ON d.order_id = o.id
              JOIN products p ON d.product_id = p.id
              WHERE o.order_date >= '$start_time' AND o.order_date <= '$end_time'
              GROUP BY p.id";
$q_items = mysqli_query($conn, $sql_items);
$sold_items = [];
if ($q_items) {
    while($row = mysqli_fetch_assoc($q_items)) {
        $sold_items[] = $row;
    }
}

// 4. XỬ LÝ SUBMIT
$message = "";
if (isset($_POST['submit_report'])) {
    $user_id = $_SESSION['user_id'];
    $real_cash = floatval($_POST['real_cash']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $inv_notes = mysqli_real_escape_string($conn, $_POST['inventory_notes']); // Ghi chú kho
    $diff = $real_cash - $system_revenue;

    $sql_insert = "INSERT INTO shift_reports (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes, inventory_notes) 
                   VALUES ('$user_id', '$current_shift', '$current_date', '$system_revenue', '$real_cash', '$diff', '$notes', '$inv_notes')";
    
    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>alert('Kết ca thành công! Đang đăng xuất...'); window.location.href = '../logout.php';</script>";
        exit();
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}
?>

<style>
    .report-container { display: flex; max-width: 1000px; margin: 40px auto; gap: 30px; align-items: flex-start; }
    .report-card { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; flex: 1; }
    .card-header { background: #5B743A; color: white; padding: 20px; text-align: center; }
    .card-body { padding: 30px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; }
    .btn-submit { width: 100%; padding: 15px; background: #d32f2f; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    
    /* Bảng kiểm kê */
    .inventory-list { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .inventory-list th, .inventory-list td { border-bottom: 1px solid #eee; padding: 8px; text-align: left; }
    .inventory-list th { color: #5B743A; border-bottom: 2px solid #5B743A; }
    .qty-badge { background: #eee; padding: 2px 8px; border-radius: 10px; font-weight: bold; }
</style>

<div class="content report-container">
    
    <div class="report-card">
        <div class="card-header" style="background: #343a40;">
            <h3 style="margin:0">📦 Đối soát Kho</h3>
            <div style="font-size: 13px; opacity: 0.8; margin-top: 5px;">Danh sách món đã dùng trong ca</div>
        </div>
        <div class="card-body">
            <?php if (!empty($sold_items)): ?>
                <table class="inventory-list">
                    <thead>
                        <tr>
                            <th>Tên món</th>
                            <th style="text-align: right;">Đã dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sold_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="text-align: right;">
                                <span class="qty-badge"><?php echo $item['qty']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="font-size: 12px; color: #777; margin-top: 15px; font-style: italic;">
                    * Hãy kiểm tra số lượng ly/cốc hoặc nguyên liệu tương ứng. Nếu thấy sai lệch, hãy ghi chú vào form bên cạnh.
                </p>
            <?php else: ?>
                <p style="text-align: center; color: #999;">Chưa bán được món nào trong ca này.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-card">
        <div class="card-header">
            <h2 style="margin:0">Báo cáo Tài chính</h2>
            <div style="background: #ffc107; color: #333; padding: 4px 12px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px; font-size: 13px;">
                <?php echo $shift_label; ?>
            </div>
            <div style="margin-top: 5px; opacity: 0.9; font-size: 14px;">NV: <?php echo htmlspecialchars($staff_name); ?></div>
        </div>

        <div class="card-body">
            <form method="POST" onsubmit="return confirm('Xác nhận kết ca và đăng xuất?');">
                <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #eee; padding-bottom: 15px;">
                    <div style="color: #777; font-size: 12px; font-weight: bold;">DOANH THU HỆ THỐNG</div>
                    <div style="font-size: 32px; font-weight: bold; color: #5B743A;"><?php echo number_format($system_revenue); ?> đ</div>
                </div>

                <label style="font-weight: bold; font-size: 14px;">💵 Tiền thực tế trong két:</label>
                <input type="number" name="real_cash" class="form-control" placeholder="Nhập tiền đếm được..." required>

                <label style="font-weight: bold; font-size: 14px;">📦 Ghi chú Kho (Đối soát):</label>
                <textarea name="inventory_notes" class="form-control" rows="2" placeholder="Ví dụ: Thiếu 1 ly nhựa, vỡ 1 cốc..."></textarea>

                <label style="font-weight: bold; font-size: 14px;">📝 Ghi chú chung:</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú bàn giao ca..."></textarea>

                <?php if ($message): ?><p style="color:red; text-align:center;"><?php echo $message; ?></p><?php endif; ?>

                <button type="submit" name="submit_report" class="btn-submit">🔒 KẾT CA NGAY</button>
            </form>
        </div>
    </div>
</div>

<?php disconnect_db(); require '../includes/footer.php'; ?>