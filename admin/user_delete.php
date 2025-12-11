<?php
require '../includes/auth_admin.php'; 
require '../includes/connect.php'; 

// 1. Lấy ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $current_user_id = $_SESSION['user_id'];

    // 🛡️ LUẬT 1: KHÔNG ĐƯỢC TỰ XÓA CHÍNH MÌNH
    if ($id == $current_user_id) {
        echo "<script>alert('❌ Bạn không thể tự xóa tài khoản của mình!'); window.location.href='user_list.php';</script>";
        exit();
    }

    // 🛡️ LUẬT 2: KHÔNG BAO GIỜ ĐƯỢC XÓA CHỦ HỆ THỐNG (ID = 1)
    if ($id == 1) {
        echo "<script>alert('⛔ CẤM: Không thể xóa tài khoản Chủ hệ thống!'); window.location.href='user_list.php';</script>";
        exit();
    }

    // Nếu vượt qua 2 luật trên -> Tiến hành xóa
    // Xóa ảnh đại diện cũ cho sạch server
    $res = mysqli_query($conn, "SELECT avatar FROM users WHERE id=$id");
    $row = mysqli_fetch_assoc($res);
    if ($row && $row['avatar'] != 'default_user.png') {
        $path = "uploads/" . $row['avatar'];
        if (file_exists($path)) unlink($path);
    }

    // Xóa trong Database
    $sql = "DELETE FROM users WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Xóa nhân viên thành công!'); window.location.href='user_list.php';</script>";
    } else {
        echo "<script>alert('Lỗi SQL: " . mysqli_error($conn) . "'); window.location.href='user_list.php';</script>";
    }

} else {
    header("Location: user_list.php");
}
?>