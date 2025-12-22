<?php
// 1. BẢO VỆ TRANG (File này tự động session_start, check admin, và connect_db)
require '../includes/auth_admin.php'; 

// 2. LẤY ID HÓA ĐƠN TỪ URL
$order_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    die("ID hóa đơn không hợp lệ.");
}

// 3. CHUẨN BỊ CÂU LỆNH DELETE
// Do đã có 'ON DELETE CASCADE' trong CSDL,
// chúng ta chỉ cần xóa khỏi bảng 'orders'.
// Các 'order_details' liên quan sẽ tự động bị xóa.
$sql = "DELETE FROM orders WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    // 4. THỰC THI LỆNH XÓA
    if (mysqli_stmt_execute($stmt)) {
        // Xóa thành công, không cần thông báo gì,
        // chỉ cần chuyển hướng về trang danh sách
    } else {
        // Nếu có lỗi, bạn có thể ghi lại
        // error_log("Lỗi khi xóa hóa đơn: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
}

// 5. NGẮT KẾT NỐI VÀ CHUYỂN HƯỚNG
disconnect_db();

// Luôn chuyển hướng người dùng về trang danh sách hóa đơn
header("Location: order_list.php?status=deleted");
exit();
?>