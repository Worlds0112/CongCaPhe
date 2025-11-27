<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// 2. LẤY DỮ LIỆU
$sql = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
        FROM orders
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.order_date DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* 🟢 CSS MỚI: Căn lề */
    .admin-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    h2 { 
        color: #333; margin-bottom: 1.5rem; font-size: 24px;
        border-left: 5px solid #17a2b8; /* Điểm nhấn màu xanh dương nhạt cho Hóa đơn */
        padding-left: 15px;
    }
    
    table { 
        width: 100%; border-collapse: collapse; background-color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border-radius: 10px; overflow: hidden;
    }
    th, td { 
        border-bottom: 1px solid #eee; padding: 15px 20px; text-align: left; vertical-align: middle;
    }
    th { background-color: #f8f9fa; font-weight: 700; color: #555; text-transform: uppercase; font-size: 13px; }
    tr:hover { background-color: #f1f3f5; }
    
    .btn-view {
        text-decoration: none; padding: 6px 12px; border-radius: 4px;
        color: white; font-size: 13px; font-weight: 500;
        background-color: #17a2b8; margin-right: 5px;
    }
    .btn-view:hover { background-color: #138496; }
    
    .btn-delete {
        text-decoration: none; padding: 6px 12px; border-radius: 4px;
        color: white; font-size: 13px; font-weight: 500;
        background-color: #dc3545;
    }
    .btn-delete:hover { background-color: #c82333; }
</style>

<div class="admin-wrapper">

    <h2>Quản lý Hóa đơn</h2>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Mã HĐ</th>
                <th>Ngày tạo</th>
                <th>Nhân viên</th>
                <th>Tổng tiền</th>
                <th style="text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><strong>#<?php echo $row['id']; ?></strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td> <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td style="color: #28a745; font-weight: bold;"><?php echo number_format($row['total_amount']); ?> ₫</td>
                <td style="text-align: center;">
                    <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn-view">Chi tiết</a>
                    <a href="order_delete.php?id=<?php echo $row['id']; ?>" 
                       class="btn-delete" 
                       onclick="return confirm('Xóa hóa đơn #<?php echo $row['id']; ?>?');">Xóa</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#999; margin-top: 30px;">Chưa có hóa đơn nào.</p>
    <?php endif; ?>

</div> <?php
if ($result) mysqli_free_result($result);
disconnect_db();
require '../includes/footer.php'; 
?>