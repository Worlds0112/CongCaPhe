<?php
session_start(); // Bắt đầu session
session_unset(); // Xóa tất cả các biến session
session_destroy(); // Hủy session hiện tại

// Chuyển người dùng về trang đăng nhập
header('Location: index.php');
exit();
?>