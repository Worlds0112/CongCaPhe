<?php
// 1. Luôn bắt đầu session
session_start(); 

// 2. Kiểm tra xem đã đăng nhập chưa VÀ có phải là 'admin' không
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // 3. Nếu không, đá về trang login
    // (Path: đi ra khỏi 'includes/' rồi vào 'login.php')
    header('Location: ../login.php');
    exit();
}

// 4. Nếu là admin, tự động nạp 2 file kết nối
// (Path: 2 file này ở ngay cùng thư mục 'includes/')
require 'connect.php';
require 'disconnect.php';

// 5. Và tự động chạy hàm kết nối
// (Biến $conn sẽ sẵn sàng cho file admin sử dụng)
connect_db();
?>