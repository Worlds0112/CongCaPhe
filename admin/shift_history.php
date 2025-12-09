<?php
// 1. BẢO VỆ TRANG (Chỉ Admin mới được vào)
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php'; 
echo '<div class="main-with-sidebar">';

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

<style>
    /* --- MÀU ĐẶC TRƯNG CHO TRANG LỊCH SỬ (MÀU VÀNG #ffc107) --- */
    h2 {
        border-left-color: #ffc107;
    }

    /* Nếu bạn muốn nút Tìm kiếm cũng đổi màu vàng khi bấm vào (cho đồng bộ) */
    .filter-input:focus,
    .filter-select:focus {
        border-color: #ffc107;
    }

    /* CSS cho trạng thái chênh lệch */
    .diff-ok {
        color: #aaa;
        font-weight: bold;
    }

    .diff-pos {
        color: #28a745;
        font-weight: bold;
    }

    .diff-neg {
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="admin-wrapper">

    <h2>Lịch sử Kết Ca & Bàn Giao</h2>

    <div class="filter-bar">
        <form method="GET" style="display: flex; align-items: center; gap: 10px;">
            <label style="font-weight: bold;">Xem ngày:</label>
            <input type="date" name="date" class="filter-input" value="<?php echo $filter_date; ?>">
            <button type="submit" class="btn-filter">Xem</button>

            <?php if ($filter_date): ?>
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
                    <th>Ghi chú Kho</th>
                    <th>Ghi chú Chung</th>
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
                            if ($row['shift_code'] == 'sang') echo '<span style="color:green; font-weight:bold">Ca Sáng</span>';
                            elseif ($row['shift_code'] == 'chieu') echo '<span style="color:orange; font-weight:bold">Ca Chiều</span>';
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

                        <td style="color: #d63384; font-size: 13px; max-width: 200px;">
                            <?php
                            // Kiểm tra nếu có ghi chú kho thì hiện, không thì gạch ngang
                            echo !empty($row['inventory_notes']) ? nl2br(htmlspecialchars($row['inventory_notes'])) : '<span style="color:#ccc">-</span>';
                            ?>
                        </td>

                        <td style="color: #666; font-style: italic; max-width: 200px;">
                            <?php echo !empty($row['notes']) ? nl2br(htmlspecialchars($row['notes'])) : '<span style="color:#ccc">-</span>'; ?>
                        </td>
                        <td>
                            <?php if ($row['user_id'] == 0): ?>
                                <span style="color:red; font-weight:bold;">🤖 HỆ THỐNG (AUTO)</span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($row['full_name']); ?><br>
                                <small style="color:#999"><?php echo $row['username']; ?></small>
                            <?php endif; ?>
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
echo '</div>';
?>