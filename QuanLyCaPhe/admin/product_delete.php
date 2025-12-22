<?php
require '../includes/auth_admin.php';
$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID sản phẩm không hợp lệ.");
}

connect_db(); 

$sql_select_image = "SELECT image FROM products WHERE id = ?";
$stmt_select = mysqli_prepare($conn, $sql_select_image);
$image_to_delete = "uploads/default.jpg"; 

if ($stmt_select) {
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $result_image = mysqli_stmt_get_result($stmt_select);
    if ($row = mysqli_fetch_assoc($result_image)) {
        $image_to_delete = $row['image'];
    }
    mysqli_stmt_close($stmt_select);
}

if ($image_to_delete != "uploads/default.jpg" && file_exists("uploads/" . $image_to_delete)) {
    unlink("uploads/" . $image_to_delete);
}

$sql = "DELETE FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
    } else {
        echo "Lỗi khi xóa sản phẩm: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);

} else {
    echo "Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn);
}
disconnect_db();
header("Location: product_list.php?status=deleted");
exit(); 
?>