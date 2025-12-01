<?php
require '../includes/auth_admin.php'; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Bảo vệ: Không được xóa chính mình
if ($id == $_SESSION['user_id']) {
    die("Bạn không thể xóa chính mình!");
}

$sql = "DELETE FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: user_list.php");
exit();
?>