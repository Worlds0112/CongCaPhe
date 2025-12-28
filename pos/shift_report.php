<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG (DÀNH CHO NHÂN VIÊN POS)
// =================================================================
require '../includes/auth_pos.php'; // Kiểm tra quyền POS
require '../includes/header.php';   // Gọi Header & CSS
require '../includes/time_check.php'; // Kiểm tra giờ làm việc (nếu có)
require '../includes/auto_shift_check.php'; // Tự động kiểm tra ca

// NHÚNG STYLE RIÊNG CỦA POS
echo '<link rel="stylesheet" href="../css/pos_style.css">';

// =================================================================
// 2. KHỞI TẠO BIẾN CƠ BẢN
// =================================================================
$staff_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$role = $_SESSION['role'];
$uid = $_SESSION['user_id'];

// Lấy thông tin ca làm việc của nhân viên từ DB
$q_user = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$my_shift = mysqli_fetch_assoc($q_user)['shift'];

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$current_hour = (int)date('H');

// =================================================================
// 3. LOGIC XÁC ĐỊNH CA LÀM VIỆC HIỆN TẠI
// =================================================================
$current_shift_code = ''; 
$shift_label = '';

if ($current_hour >= 6 && $current_hour < 12) {
    $current_shift_code = 'sang';
    $shift_label = 'CA SÁNG (06:00 - 12:00)';
} elseif ($current_hour >= 12 && $current_hour < 18) {
    $current_shift_code = 'chieu';
    $shift_label = 'CA CHIỀU (12:00 - 18:00)';
} else {
    $current_shift_code = 'toi';
    $shift_label = 'CA TỐI (18:00 - 23:00)';
    // Xử lý các trường hợp đặc biệt ngoài giờ hoặc qua đêm
    if ($current_hour >= 23) $shift_label = 'CA TỐI (Ngoài giờ)';
    if ($current_hour < 6) { 
        // Nếu làm qua đêm (sau 0h), tính vào ngày hôm trước
        $today = date('Y-m-d', strtotime('-1 day')); 
        $shift_label = 'CA TỐI (Làm đêm)'; 
    }
}

// =================================================================
// 4. XÁC ĐỊNH THỜI GIAN BẮT ĐẦU CA (START TIME)
// =================================================================
// Lấy thời điểm kết ca gần nhất trong hệ thống
$sql_last = "SELECT created_at FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last);
$r_last = mysqli_fetch_assoc($q_last);

// Ca hiện tại bắt đầu ngay sau khi ca trước kết thúc
// Nếu chưa có báo cáo nào -> Bắt đầu từ 00:00 của ngày hôm nay
$start_time = $r_last ? $r_last['created_at'] : "$today 00:00:00";
$end_time = $now; // Kết thúc tại thời điểm hiện tại

// =================================================================
// 5. KIỂM TRA QUYỀN TRUY CẬP (NGOÀI GIỜ)
// =================================================================
$is_view_only = false;
$lock_message = "";

// Nếu ngoài khung giờ làm việc chính thức (23h - 6h sáng)
if ($current_hour >= 23 || $current_hour < 6) {
    // Chỉ Admin hoặc NV Fulltime hoặc Ca Tối mới được thao tác
    if ($role != 'admin' && $my_shift != 'full' && $current_shift_code != 'toi') {
        $is_view_only = true;
        $lock_message = "Ngoài giờ làm việc. Bạn chỉ có thể xem.";
    }
}

// =================================================================
// 6. TÍNH TOÁN DOANH THU CA HIỆN TẠI
// =================================================================
// A. Tổng tiền bán hàng (System Revenue)
$sql_rev = "SELECT SUM(total_amount) as total FROM orders WHERE order_date > '$start_time' AND order_date <= '$end_time'";
$r_rev = mysqli_fetch_assoc(mysqli_query($conn, $sql_rev));
$system_revenue = $r_rev['total'] ? $r_rev['total'] : 0;

// B. Danh sách món đã bán (Để đối soát kho)
$sql_items = "SELECT p.name, SUM(d.quantity) as qty 
              FROM order_details d 
              JOIN orders o ON d.order_id = o.id 
              JOIN products p ON d.product_id = p.id 
              WHERE o.order_date > '$start_time' AND o.order_date <= '$end_time' 
              GROUP BY p.id";
$q_items = mysqli_query($conn, $sql_items);
$sold_items = [];
if ($q_items) { while($row = mysqli_fetch_assoc($q_items)) { $sold_items[] = $row; } }

// =================================================================
// 7. XỬ LÝ SUBMIT KẾT CA (KHI BẤM NÚT)
// =================================================================
$message = "";
$success_redirect = false; // Cờ kiểm tra thành công để JS xử lý chuyển trang

if (isset($_POST['submit_report']) && !$is_view_only) {
    // Lấy dữ liệu từ form
    $real_cash = floatval($_POST['real_cash']); // Tiền thực tế đếm được
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $inv_notes = mysqli_real_escape_string($conn, $_POST['inventory_notes']);
    
    // Tính chênh lệch (Thực tế - Hệ thống)
    $diff = $real_cash - $system_revenue;

    // Lưu vào bảng shift_reports
    $sql_insert = "INSERT INTO shift_reports (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes, inventory_notes) 
                   VALUES ('$uid', '$current_shift_code', '$today', '$system_revenue', '$real_cash', '$diff', '$notes', '$inv_notes')";
    
    if (mysqli_query($conn, $sql_insert)) {
        $success_redirect = true; // Bật cờ thành công
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content report-container">
    
    <div class="report-card">
        <div class="card-header header-dark">
            <h3>📦 Đối soát Kho</h3>
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
            <h2>Báo cáo Tài chính</h2>
            <div class="shift-info-badge">
                <?php echo $shift_label; ?>
            </div>
            <div style="margin-top: 5px;">NV: <?php echo htmlspecialchars($staff_name); ?></div>
        </div>

        <div class="card-body">
            
            <?php if($is_view_only): ?>
                <div class="alert-warning-box">
                    ⛔ <strong>Chế độ Xem:</strong> <?php echo $lock_message; ?>
                </div>
            <?php endif; ?>

            <form id="shiftForm" method="POST">
                
                <div class="total-revenue-box">
                    <div class="revenue-label">DOANH THU CA NÀY</div>
                    <div class="revenue-amount"><?php echo number_format($system_revenue); ?> đ</div>
                </div>

                <div class="form-group-report">
                    <label class="label-bold">💵 Tiền thực tế (Đếm được):</label>
                    <input type="number" name="real_cash" class="form-control" placeholder="Nhập số tiền..." <?php if($is_view_only) echo 'disabled'; ?> required>
                </div>

                <div class="form-group-report">
                    <label class="label-bold">📦 Ghi chú Kho (Nếu có sai lệch):</label>
                    <textarea name="inventory_notes" class="form-control" rows="2" <?php if($is_view_only) echo 'disabled'; ?>></textarea>
                </div>

                <div class="form-group-report">
                    <label class="label-bold">📝 Ghi chú chung:</label>
                    <textarea name="notes" class="form-control" rows="2" <?php if($is_view_only) echo 'disabled'; ?>></textarea>
                </div>

                <?php if ($message): ?>
                    <script>Swal.fire('Lỗi', '<?php echo $message; ?>', 'error');</script>
                <?php endif; ?>

                <?php if(!$is_view_only): ?>
                    <button type="button" onclick="confirmSubmit()" class="btn-submit-report">🔒 KẾT CA NGAY</button>
                    <input type="hidden" name="submit_report" value="1">
                <?php else: ?>
                    <button type="button" class="btn-submit-report btn-disabled" disabled>🔒 KHÔNG ĐƯỢC PHÉP</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
    // Hàm xác nhận trước khi gửi form
    function confirmSubmit() {
        const cashInput = document.querySelector('input[name="real_cash"]');
        const cashValue = cashInput.value;

        // Kiểm tra xem đã nhập tiền chưa
        if(cashValue === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa nhập tiền!',
                text: 'Vui lòng nhập số tiền thực tế bạn đếm được.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Hiện popup xác nhận
        Swal.fire({
            title: 'Xác nhận Kết Ca?',
            text: "Hành động này sẽ chốt doanh thu và đăng xuất tài khoản.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#5B743A',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý, Kết ca!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('shiftForm').submit();
            }
        });
    }

    // Xử lý sau khi PHP submit thành công -> Hiện thông báo và Đăng xuất
    <?php if ($success_redirect): ?>
        Swal.fire({
            title: 'Kết ca thành công!',
            text: 'Hệ thống đã ghi nhận báo cáo. Đang đăng xuất...',
            icon: 'success',
            showConfirmButton: false,
            timer: 2000 // Tự động chuyển sau 2 giây
        }).then(() => {
            window.location.href = '../logout.php';
        });
    <?php endif; ?>
</script>

<?php disconnect_db(); require '../includes/footer.php'; ?>