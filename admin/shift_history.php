<?php
// 1. BẢO VỆ TRANG (Chỉ Admin mới được vào)
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// 2. XỬ LÝ LỌC NGÀY
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT r.*, u.full_name, u.username 
        FROM shift_reports r 
        JOIN users u ON r.user_id = u.id 
        WHERE 1=1";

if ($filter_date) {
    $sql .= " AND r.report_date = '$filter_date'";
}

$sql .= " ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>



<div class="admin-wrapper">

    <h2>Lịch sử Kết Ca & Bàn Giao</h2>

    <div class="filter-bar">
        <form method="GET" style="display: flex; align-items: center; gap: 10px;">
            <label style="font-weight: bold;">Xem ngày:</label>
            <input type="date" name="date" class="filter-input" value="<?php echo $filter_date; ?>">
            <button type="submit" class="btn-filter">Xem</button>
            
            <?php if($filter_date): ?>
                <a href="shift_history.php" class="btn-reset">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Ca làm việc</th>
                <th>Nhân viên</th>
                <th>Doanh thu (Máy)</th>
                <th>Thực tế (Két)</th>
                <th>Chênh lệch</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <strong><?php echo date('d/m/Y', strtotime($row['report_date'])); ?></strong><br>
                        <small style="color:#777">Lúc <?php echo date('H:i', strtotime($row['created_at'])); ?></small>
                    </td>
                    <td>
                        <?php 
                        if($row['shift_code'] == 'sang') echo '<span style="color:green; font-weight:bold">Ca Sáng</span>';
                        elseif($row['shift_code'] == 'chieu') echo '<span style="color:orange; font-weight:bold">Ca Chiều</span>';
                        else echo '<span style="color:purple; font-weight:bold">Ca Tối</span>';
                        ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['full_name']); ?><br>
                        <small style="color:#999"><?php echo $row['username']; ?></small>
                    </td>
                    <td>
                        <?php echo number_format($row['system_revenue']); ?> ₫
                    </td>
                    <td style="font-weight:bold; color:#333;">
                        <?php echo number_format($row['real_cash']); ?> ₫
                    </td>
                    <td>
                        <?php 
                        if ($row['difference'] == 0) {
                            echo '<span class="diff-ok">✓ Khớp</span>';
                        } elseif ($row['difference'] > 0) {
                            echo '<span class="diff-pos">+' . number_format($row['difference']) . '</span>';
                        } else {
                            echo '<span class="diff-neg">' . number_format($row['difference']) . '</span>';
                        }
                        ?>
                    </td>
                    <td style="color: #666; font-style: italic; max-width: 250px;">
                        <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; padding: 30px; background: white; border-radius: 8px; color: #777;">
            Chưa có dữ liệu báo cáo nào.
        </p>
    <?php endif; ?>

</div>

<?php 
if ($result) mysqli_free_result($result);
disconnect_db();
require '../includes/footer.php'; 
?>