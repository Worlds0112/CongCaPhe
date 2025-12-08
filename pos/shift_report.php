<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php'; // Để lấy khung giờ ca

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. TÍNH DOANH THU CA HIỆN TẠI
// Logic: Lấy các đơn hàng do User này tạo ra trong ngày hôm nay
$today = date('Y-m-d');
$sql_my_sales = "SELECT SUM(total_amount) as total, COUNT(*) as count 
                 FROM orders 
                 WHERE user_id = $user_id AND DATE(order_date) = '$today'";
$res_sales = mysqli_query($conn, $sql_my_sales);
$my_stats = mysqli_fetch_assoc($res_sales);

// 2. XỬ LÝ GỬI GHI CHÚ BÀN GIAO
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_handover'])) {
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    if (!empty($note)) {
        $sql_insert = "INSERT INTO shift_handovers (user_id, note) VALUES ($user_id, '$note')";
        mysqli_query($conn, $sql_insert);
        $msg = "Đã lưu ghi chú bàn giao!";
    }
}

// 3. LẤY LỊCH SỬ BÀN GIAO (5 tin gần nhất)
$sql_notes = "SELECT h.*, u.full_name, u.shift 
              FROM shift_handovers h 
              JOIN users u ON h.user_id = u.id 
              ORDER BY h.created_at DESC LIMIT 5";
$res_notes = mysqli_query($conn, $sql_notes);
?>

<style>
    .report-wrapper { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
    
    .stats-box {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white; padding: 30px; border-radius: 12px;
        text-align: center; margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .stats-box h2 { margin: 0; font-size: 32px; }
    .stats-box p { font-size: 18px; opacity: 0.9; margin-top: 5px; }

    .handover-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    textarea { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; margin: 10px 0; }
    .btn-submit { background: #5B743A; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    
    .note-list { margin-top: 30px; }
    .note-item { border-bottom: 1px solid #eee; padding: 15px 0; }
    .note-meta { font-size: 12px; color: #888; margin-bottom: 5px; font-weight: bold; }
    .shift-tag { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 5px; }
</style>

<div class="report-wrapper">
    
    <a href="pos.php" style="text-decoration:none; color:#555; font-weight:bold;">← Quay lại bán hàng</a>
    <h2 style="margin-top: 15px; color: #333;">Báo Cáo Ca Cá Nhân</h2>

    <div class="stats-box">
        <p>Doanh số của bạn hôm nay</p>
        <h2><?php echo number_format($my_stats['total'] ?? 0); ?> ₫</h2>
        <p>Tổng <?php echo $my_stats['count']; ?> đơn hàng</p>
    </div>

    <div class="handover-section">
        <h3>📝 Ghi chú bàn giao ca</h3>
        <?php if($msg) echo "<p style='color:green'>$msg</p>"; ?>
        
        <form method="POST">
            <textarea name="note" rows="3" placeholder="Ví dụ: Máy in hết giấy, còn tồn 2 ly chưa giao, khách bàn 3 nợ..."></textarea>
            <button type="submit" name="submit_handover" class="btn-submit">Gửi Báo Cáo</button>
        </form>

        <div class="note-list">
            <h4>Lịch sử bàn giao gần đây:</h4>
            <?php while ($note = mysqli_fetch_assoc($res_notes)): ?>
                <div class="note-item">
                    <div class="note-meta">
                        <?php echo date('H:i d/m', strtotime($note['created_at'])); ?> - 
                        <?php echo htmlspecialchars($note['full_name']); ?>
                        <span class="shift-tag"><?php echo strtoupper($note['shift']); ?></span>
                    </div>
                    <div><?php echo nl2br(htmlspecialchars($note['note'])); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

<?php require '../includes/footer.php'; ?>