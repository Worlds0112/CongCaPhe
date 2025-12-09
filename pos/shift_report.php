<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';

// --- SỬA LỖI TÊN NHÂN VIÊN ---
// Kiểm tra nếu có full_name thì dùng, không thì dùng username
$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
// -----------------------------

// 1. XÁC ĐỊNH CA
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_hour = date('H');
$current_date = date('Y-m-d');
$current_shift = '';
$shift_label = '';
$start_time = ''; $end_time = '';

if ($current_hour >= 6 && $current_hour < 12) {
    $current_shift = 'sang';
    $shift_label = 'CA SÁNG (06:00 - 12:00)';
    $start_time = "$current_date 06:00:00"; $end_time = "$current_date 12:00:00";
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $current_shift = 'chieu';
    $shift_label = 'CA CHIỀU (12:00 - 18:00)';
    $start_time = "$current_date 12:00:00"; $end_time = "$current_date 18:00:00";
} else {
    $current_shift = 'toi';
    $shift_label = 'CA TỐI (18:00 - 23:00)';
    $start_time = "$current_date 18:00:00"; $end_time = "$current_date 23:59:59";
}

// 2. TÍNH DOANH THU
$sql_rev = "SELECT SUM(total_amount) as total FROM orders WHERE order_date >= '$start_time' AND order_date <= '$end_time'";
$q_rev = mysqli_query($conn, $sql_rev);
$r_rev = mysqli_fetch_assoc($q_rev);
$system_revenue = $r_rev['total'] ? $r_rev['total'] : 0;

// 3. XỬ LÝ SUBMIT
$message = "";
if (isset($_POST['submit_report'])) {
    $user_id = $_SESSION['user_id'];
    $real_cash = floatval($_POST['real_cash']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $diff = $real_cash - $system_revenue;

    $sql_insert = "INSERT INTO shift_reports (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes) 
                   VALUES ('$user_id', '$current_shift', '$current_date', '$system_revenue', '$real_cash', '$diff', '$notes')";
    
    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>alert('Kết ca thành công! Đang đăng xuất...'); window.location.href = '../logout.php';</script>";
        exit();
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}
?>

<style>
    .report-wrapper { max-width: 600px; margin: 80px auto; }
    .report-card { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; }
    .card-header { background: #5B743A; color: white; padding: 20px; text-align: center; }
    .card-body { padding: 30px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; }
    .btn-submit { width: 100%; padding: 15px; background: #d32f2f; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
</style>

<div class="report-wrapper">
    <div class="report-card">
        <div class="card-header">
            <h2 style="margin:0">Báo cáo kết ca</h2>
            <div style="background: #ffc107; color: #333; padding: 5px 15px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px;">
                <?php echo $shift_label; ?>
            </div>
            <div style="margin-top: 5px; opacity: 0.9;">Nhân viên: <?php echo htmlspecialchars($staff_name); ?></div>
        </div>

        <div class="card-body">
            <form method="POST" onsubmit="return confirm('Xác nhận kết ca và đăng xuất?');">
                <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #eee; padding-bottom: 15px;">
                    <div style="color: #777;">DOANH THU HỆ THỐNG</div>
                    <div style="font-size: 32px; font-weight: bold; color: #5B743A;"><?php echo number_format($system_revenue); ?> đ</div>
                </div>

                <label>💵 Tiền thực tế trong két:</label>
                <input type="number" name="real_cash" class="form-control" placeholder="Nhập số tiền..." required>

                <label>📝 Ghi chú:</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>

                <?php if ($message): ?><p style="color:red; text-align:center;"><?php echo $message; ?></p><?php endif; ?>

                <button type="submit" name="submit_report" class="btn-submit">🔒 KẾT CA & ĐĂNG XUẤT</button>
            </form>
        </div>
    </div>
</div>

<?php disconnect_db(); require '../includes/footer.php'; ?>