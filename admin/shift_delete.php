<?php
// FILE: shift_delete.php
// =================================================================
// XỬ LÝ XÓA BÁO CÁO KẾT CA
// =================================================================

require '../includes/auth_admin.php'; // Bắt buộc là Admin mới được xóa

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Thực hiện xóa
    $sql = "DELETE FROM shift_reports WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
            alert('✅ Đã xóa báo cáo thành công!');
            window.location.href = 'shift_history.php';
        </script>";
    } else {
        echo "<script>
            alert('❌ Lỗi SQL: " . mysqli_error($conn) . "');
            window.location.href = 'shift_history.php';
        </script>";
    }
} else {
    // Nếu không có ID thì quay về
    header("Location: shift_history.php");
}

disconnect_db();
?>