<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// 2. LẤY DỮ LIỆU ORDERS (NỐI BẢNG VỚI USERS)
// Chúng ta nối (JOIN) với bảng 'users' để lấy tên nhân viên bán hàng
$sql = "SELECT orders.id, orders.order_date, orders.total_amount, users.full_name
        FROM orders
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.order_date DESC"; // Sắp xếp hóa đơn mới nhất lên đầu

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Lỗi truy vấn: " . mysqli_error($conn);
}
?>

<style>
    h2 { color: #333; margin-bottom: 1rem; }
    table { 
        width: 100%; border-collapse: collapse; background-color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border-radius: 8px; overflow: hidden;
    }
    th, td { 
        border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: middle;
    }
    th { background-color: #f2f2f2; font-weight: 600; color: #333; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background-color: #f9f9f9; }
    .btn-view {
        text-decoration: none; padding: 2px 5px; border-radius: 4px;
        color: white; font-size: 14px; font-weight: 500;
        background-color: #17a2b8;
    }
    .btn-view:hover { background-color: #138496; }
    .btn-delete {
        text-decoration: none; padding: 2px 5px; border-radius: 4px;
        color: white; font-size: 14px; font-weight: 500;
        background-color: #dc3545;
    }
    .btn-delete:hover { background-color: #c82333; }
</style>

<h2>Quản lý Hóa đơn</h2>

<?php if ($result && mysqli_num_rows($result) > 0): ?>
<table>
    <thead>
        <tr>
            <th>Mã HĐ</th>
            <th>Ngày tạo</th>
            <th>Nhân viên</th>
            <th>Tổng tiền</th>
            <th style="width: 120px; text-align: center;">Hành động</th> </tr>
    </thead>
    <tbody>
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo number_format($row['total_amount']); ?> VNĐ</td>
                <td style="text-align: center;">
                    <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn-view">Xem</a>
                    
                    <a href="order_delete.php?id=<?php echo $row['id']; ?>" 
                       class="btn-delete" 
                       style="margin-left: 5px;"
                       onclick="return confirm('Bạn có chắc chắn muốn XÓA HÓA ĐƠN này không? \n(Hành động này không thể hoàn tác!)');">Xóa</a>
                </td>
            </tr>
        <?php
        } // Kết thúc vòng lặp
        ?>
    </tbody>
</table>
<?php else: ?>
    <p>Chưa có hóa đơn nào được tạo.</p>
<?php endif; ?>

<?php
// DỌN DẸP VÀ GỌI FOOTER
if ($result) {
    mysqli_free_result($result);
}
disconnect_db();

require '../includes/footer.php'; 
?>