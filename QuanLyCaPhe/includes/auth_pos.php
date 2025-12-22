<?php
// 1. Luôn bắt đầu session
session_start(); 

// 2. Chỉ cần kiểm tra xem đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    // 3. Nếu chưa, đá về trang login
    header('Location: ../login.php');
    exit();
}

// 4. Nếu đã đăng nhập, tự động nạp kết nối CSDL
require 'connect.php';
require 'disconnect.php';

// 5. Và tự động chạy hàm kết nối
connect_db();
?>