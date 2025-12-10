<?php
require '../includes/auth_admin.php'; 
require '../includes/connect.php'; 

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // 1. Lấy trạng thái hiện tại
    $check = mysqli_query($conn, "SELECT is_locked FROM products WHERE id = $id");
    $row = mysqli_fetch_assoc($check);
    
    if ($row) {
        // 2. Đảo ngược trạng thái (Nếu đang 0 thì thành 1, đang 1 thì thành 0)
        $new_status = ($row['is_locked'] == 1) ? 0 : 1;
        
        $sql = "UPDATE products SET is_locked = $new_status WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            // Quay lại trang danh sách
            header("Location: product_list.php");
        } else {
            echo "Lỗi: " . mysqli_error($conn);
        }
    } else {
        header("Location: product_list.php");
    }
}
?>