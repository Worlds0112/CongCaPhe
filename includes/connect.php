<?php
// File: includes/connect.php

if (!function_exists('connect_db')) {
    function connect_db() {
        // Khai báo biến $conn ở phạm vi toàn cục
        global $conn; 
        
        // Tạo kết nối
        $conn = mysqli_connect("localhost", "root", "", "db_quanlycafe");
        
        if (!$conn) {
            die("Kết nối thất bại: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8");
        
        // KHÔNG cần trả về $conn
        return $conn;
    }
}
?>