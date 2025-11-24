<?php
// 1. Bắt đầu session
session_start();

// 2. Nạp file kết nối
require 'includes/connect.php'; 
require 'includes/disconnect.php';

// 3. Kiểm tra xem có dữ liệu POST lên không
if (isset($_POST['username']) && isset($_POST['password'])) {
    
    connect_db(); // Kết nối CSDL
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 4. Tìm user trong CSDL
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        if ($user = mysqli_fetch_assoc($result)) {
            
            // 5. Nếu tìm thấy user, kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                // Mật khẩu KHỚP!
                
                // 6. Lưu thông tin vào Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; 
                
                // 7. ⭐️ THAY ĐỔI Ở ĐÂY ⭐️
                // Thay vì phân quyền, CHUYỂN THẲNG VỀ TRANG CHỦ
                header('Location: index.php');
                exit();
                // ⭐️ HẾT THAY ĐỔI ⭐️
                
            } else {
                // Sai mật khẩu
                header('Location: login.php?error=1');
                exit();
            }
        } else {
            // Không tìm thấy username
            header('Location: login.php?error=1');
            exit();
        }
        
        mysqli_stmt_close($stmt);

    } else {
        // Lỗi ngay từ khi mysqli_prepare()
        echo "Lỗi hệ thống: " . mysqli_error($conn);
    }
    
    disconnect_db();
    
} else {
    // Nếu vào thẳng file này, đá về login
    header('Location: login.php'); 
    exit();
}
?>