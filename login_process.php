<?php
// 1. Bắt đầu session
session_start();

// 2. Nạp file kết nối và ngắt kết nối
// Đảm bảo file connect.php chứa function connect_db()
// Đảm bảo file disconnect.php chứa function disconnect_db($conn)
require 'includes/connect.php'; 
require 'includes/disconnect.php';

// 3. XỬ LÝ DỮ LIỆU POST
if (isset($_POST['username']) && isset($_POST['password'])) {
    
    // 🔥 3.1. KẾT NỐI CSDL 🔥
    $conn = connect_db(); 
    
    // Kiểm tra kết nối
    if (!$conn) {
        header('Location: login.php?error=Lỗi kết nối CSDL.');
        exit();
    }
    
    // Lấy dữ liệu
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 4. Tìm user trong CSDL (Dùng Prepared Statements để bảo mật)
    $sql = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        
        // 4.1. Bind và thực thi
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        if ($user = mysqli_fetch_assoc($result)) {
            
            // 5. Kiểm tra mật khẩu (Sử dụng password_verify)
            if (password_verify($password, $user['password'])) {
                
                // Mật khẩu KHỚP! Lưu thông tin vào Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role']; 
                
                // Chuyển hướng
                header('Location: index.php');
                exit();
                
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
        // Lỗi prepare SQL
        header('Location: login.php?error=Lỗi hệ thống: SQL Prepare Failed');
        exit();
    }
    
    // 🔥 ĐÓNG KẾT NỐI 🔥
    disconnect_db();
    
} else {
    // Nếu vào thẳng file này, đá về login
    header('Location: login.php'); 
    exit();
}
?>