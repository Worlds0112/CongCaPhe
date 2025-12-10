<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';
require '../includes/auto_shift_check.php';

// Tên nhân viên
$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$role = $_SESSION['role'];
$uid = $_SESSION['user_id'];

// Lấy thông tin ca của nhân viên
$q_user = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$my_shift = mysqli_fetch_assoc($q_user)['shift'];

date_default_timezone_set('Asia/Ho_Chi_Minh');
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$current_hour = (int)date('H');

// --- 1. LOGIC XÁC ĐỊNH CA THÔNG MINH (Xử lý cả đêm muộn) ---
$current_shift_code = ''; 
$shift_label = '';

if ($current_hour >= 6 && $current_hour < 12) {
    $current_shift_code = 'sang';
    $shift_label = 'CA SÁNG (06:00 - 12:00)';
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $current_shift_code = 'chieu';
    $shift_label = 'CA CHIỀU (12:00 - 18:00)';
} else {
    // CA TỐI (Từ 18h trở đi)
    $current_shift_code = 'toi';
    
    // Mặc định là Ca Tối
    $shift_label = 'CA TỐI (18:00 - 23:00)';

    // Nếu quá 23h đêm (23:00 - 23:59) -> Thêm chữ "Ngoài giờ" hoặc đổi nhãn
    if ($current_hour >= 23) {
        $shift_label = 'CA TỐI (Ngoài giờ)';
    }

    // Nếu là 0h - 6h sáng -> Tính là ca tối hôm qua
    if ($current_hour < 6) {
        $today = date('Y-m-d', strtotime('-1 day')); 
        $shift_label = 'CA TỐI (Làm đêm)';
    }
}

// --- 2. XÁC ĐỊNH THỜI GIAN BẮT ĐẦU CA (Dựa trên lần chốt trước) ---
$sql_last = "SELECT created_at FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last);
$r_last = mysqli_fetch_assoc($q_last);

$start_time = "$today 00:00:00"; // Mặc định
if ($r_last) {
    // Nếu lần chốt trước cùng ngày (hoặc là ca trước của ngày hôm nay)
    // Thì ca này bắt đầu nối tiếp ngay sau đó
    // Ví dụ: Ca Chiều chốt lúc 18:05 -> Ca Tối tính từ 18:05:01
    $start_time = $r_last['created_at'];
}
$end_time = $now;


// --- 3. KIỂM TRA QUYỀN TRUY CẬP (LUẬT 23H-6H) ---
$is_view_only = false;
$lock_message = "";

// Nếu trong khung giờ giới nghiêm (23h - 6h)
if ($current_hour >= 23 || $current_hour < 6) {
    // Nếu KHÔNG phải Admin và KHÔNG phải Fulltime
    if ($role != 'admin' && $my_shift != 'full') {
        // Kiểm tra xem có phải đang chốt ca của chính mình không?
        // Nếu ca hiện tại là 'toi' thì cho phép chốt.
        // Nhưng nếu vào giờ này mà hệ thống lại báo là 'sang' (lỗi) thì chặn.
        if ($current_shift_code != 'toi') {
            $is_view_only = true;
            $lock_message = "Ngoài giờ làm việc. Bạn chỉ có thể xem.";
        }
    }
}


// --- 4. TÍNH TOÁN DOANH THU ---
$sql_rev = "SELECT SUM(total_amount) as total FROM orders 
            WHERE order_date > '$start_time' AND order_date <= '$end_time'";
$r_rev = mysqli_fetch_assoc(mysqli_query($conn, $sql_rev));
$system_revenue = $r_rev['total'] ? $r_rev['total'] : 0;

$sql_items = "SELECT p.name, SUM(d.quantity) as qty
              FROM order_details d
              JOIN orders o ON d.order_id = o.id
              JOIN products p ON d.product_id = p.id
              WHERE o.order_date > '$start_time' AND o.order_date <= '$end_time'
              GROUP BY p.id";
$q_items = mysqli_query($conn, $sql_items);
$sold_items = [];
if ($q_items) { while($row = mysqli_fetch_assoc($q_items)) { $sold_items[] = $row; } }


// --- 5. XỬ LÝ SUBMIT ---
$message = "";
$success_redirect = false;

if (isset($_POST['submit_report']) && !$is_view_only) {
    $real_cash = floatval($_POST['real_cash']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $inv_notes = mysqli_real_escape_string($conn, $_POST['inventory_notes']);
    $diff = $real_cash - $system_revenue;

    $sql_insert = "INSERT INTO shift_reports (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes, inventory_notes) 
                   VALUES ('$uid', '$current_shift_code', '$today', '$system_revenue', '$real_cash', '$diff', '$notes', '$inv_notes')";
    
    if (mysqli_query($conn, $sql_insert)) {
        $success_redirect = true;
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
    .btn-submit { width: 100%; padding: 15px; background: #d32f2f; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-submit:hover { background: #b71c1c; }
    .btn-disabled { background: #ccc; cursor: not-allowed; }
    
    .inventory-list { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .inventory-list th, .inventory-list td { border-bottom: 1px solid #eee; padding: 8px; text-align: left; }
    .inventory-list th { color: #5B743A; border-bottom: 2px solid #5B743A; }
    .qty-badge { background: #eee; padding: 2px 8px; border-radius: 10px; font-weight: bold; }

    /* Modal Styles */
    .custom-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
    .modal-content { background: white; padding: 30px; border-radius: 12px; text-align: center; width: 400px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: fadeIn 0.3s; }
    .modal-icon { font-size: 50px; margin-bottom: 15px; display: block; }
    .modal-title { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 10px; }
    .btn-modal { padding: 10px 25px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; font-size: 14px; margin: 0 5px; }
    .btn-cancel-modal { background: #eee; color: #333; }
    .btn-confirm-modal { background: #d32f2f; color: white; }
    @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
</style>

<div class="content report-container">
    
    <div class="report-card">
        <div class="card-header" style="background: #343a40;">
            <h3 style="margin:0">📦 Đối soát Kho</h3>
            <div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">
                Từ: <?php echo date('H:i d/m', strtotime($start_time)); ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($sold_items)): ?>
                <table class="inventory-list">
                    <thead><tr><th>Tên món</th><th style="text-align: right;">Đã dùng</th></tr></thead>
                    <tbody>
                        <?php foreach ($sold_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="text-align: right;"><span class="qty-badge"><?php echo $item['qty']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999;">Chưa bán món nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-card">
        <div class="card-header">
            <h2 style="margin:0">Báo cáo Tài chính</h2>
            <div style="background: #ffc107; color: #333; padding: 4px 12px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px;">
                <?php echo $shift_label; ?>
            </div>
            <div style="margin-top: 5px;">NV: <?php echo htmlspecialchars($staff_name); ?></div>
        </div>

        <div class="card-body">
            
            <?php if($is_view_only): ?>
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba;">
                    ⛔ <strong>Chế độ Xem:</strong> <?php echo $lock_message; ?>
                </div>
            <?php endif; ?>

            <form id="shiftForm" method="POST">
                <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #eee; padding-bottom: 15px;">
                    <div style="color: #777; font-size: 12px; font-weight: bold;">DOANH THU CA NÀY</div>
                    <div style="font-size: 32px; font-weight: bold; color: #5B743A;"><?php echo number_format($system_revenue); ?> đ</div>
                </div>

                <label style="font-weight: bold;">💵 Tiền thực tế:</label>
                <input type="number" name="real_cash" class="form-control" placeholder="Nhập tiền đếm được..." <?php if($is_view_only) echo 'disabled'; ?> required>

                <label style="font-weight: bold;">📦 Ghi chú Kho:</label>
                <textarea name="inventory_notes" class="form-control" rows="2" <?php if($is_view_only) echo 'disabled'; ?>></textarea>

                <label style="font-weight: bold;">📝 Ghi chú chung:</label>
                <textarea name="notes" class="form-control" rows="2" <?php if($is_view_only) echo 'disabled'; ?>></textarea>

                <?php if ($message): ?><p style="color:red; text-align:center;"><?php echo $message; ?></p><?php endif; ?>

                <?php if(!$is_view_only): ?>
                    <button type="button" onclick="showConfirmModal()" class="btn-submit">🔒 KẾT CA NGAY</button>
                    <input type="hidden" name="submit_report" value="1">
                <?php else: ?>
                    <button type="button" class="btn-submit btn-disabled" disabled>🔒 KHÔNG ĐƯỢC PHÉP</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div id="confirmModal" class="custom-modal">
    <div class="modal-content">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">Xác nhận Kết Ca?</div>
        <p style="color:#666;">Hành động này sẽ chốt doanh thu và đăng xuất.</p>
        <div style="margin-top:20px;">
            <button class="btn-modal btn-cancel-modal" onclick="closeModal('confirmModal')">Hủy</button>
            <button class="btn-modal btn-confirm-modal" onclick="submitForm()">Đồng ý</button>
        </div>
    </div>
</div>

<div id="successModal" class="custom-modal">
    <div class="modal-content">
        <div class="modal-icon">✅</div>
        <div class="modal-title" style="color: #28a745;">Kết ca thành công!</div>
        <p>Hệ thống đã ghi nhận. Đang đăng xuất...</p>
    </div>
</div>

<script>
    function showConfirmModal() {
        const cash = document.querySelector('input[name="real_cash"]').value;
        if(cash === "") { alert("Vui lòng nhập tiền thực tế!"); return; }
        document.getElementById('confirmModal').style.display = 'flex';
    }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    function submitForm() { document.getElementById('shiftForm').submit(); }

    <?php if ($success_redirect): ?>
        document.getElementById('successModal').style.display = 'flex';
        setTimeout(() => { window.location.href = '../logout.php'; }, 2000);
    <?php endif; ?>
</script>

<?php disconnect_db(); require '../includes/footer.php'; ?>