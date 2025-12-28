<?php
// FILE: inventory_delete.php
require '../includes/auth_admin.php'; // Chỉ Admin mới được xóa

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 1. Lấy thông tin dòng lịch sử sắp xóa
    $sql_get = "SELECT product_id, quantity FROM inventory_history WHERE id = $id";
    $result = mysqli_query($conn, $sql_get);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $product_id = $row['product_id'];
        $qty_history = (int)$row['quantity']; // Số lượng đã ghi (có thể âm hoặc dương)

        // Bắt đầu giao dịch để đảm bảo an toàn dữ liệu
        mysqli_begin_transaction($conn);

        try {
            // 2. Cập nhật lại kho (Đảo ngược số lượng)
            // Công thức: Stock mới = Stock cũ - (Số lượng lịch sử)
            // VD: Xóa nhập (+10) => Stock - 10
            // VD: Xóa xuất (-5)  => Stock - (-5) = Stock + 5
            $sql_update_stock = "UPDATE products SET stock = stock - ($qty_history) WHERE id = $product_id";
            if (!mysqli_query($conn, $sql_update_stock)) {
                throw new Exception("Lỗi cập nhật lại kho.");
            }

            // 3. Xóa dòng lịch sử
            $sql_delete = "DELETE FROM inventory_history WHERE id = $id";
            if (!mysqli_query($conn, $sql_delete)) {
                throw new Exception("Lỗi xóa dữ liệu lịch sử.");
            }

            // Hoàn tất
            mysqli_commit($conn);
            echo "<script>
                alert('✅ Đã xóa thành công và cập nhật lại kho!');
                window.location.href = 'inventory_history.php';
            </script>";

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>
                alert('❌ Lỗi: " . $e->getMessage() . "');
                window.location.href = 'inventory_history.php';
            </script>";
        }
    } else {
        echo "<script>alert('Không tìm thấy dữ liệu!'); window.location.href = 'inventory_history.php';</script>";
    }
} else {
    header("Location: inventory_history.php");
}
disconnect_db();
?>