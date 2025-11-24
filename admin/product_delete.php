<?php
// Bước 1: Nạp 2 file thư viện kết nối
require '../includes/auth_admin.php';
// Bước 2: Lấy ID sản phẩm từ URL
$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID sản phẩm không hợp lệ.");
}

// Bước 3: Gọi hàm connect_db() để có biến $conn
connect_db(); // Biến $conn (global) giờ đã sẵn sàng

// --- (Phần nâng cao: Xóa file ảnh trước khi xóa CSDL) ---
// 3.1. Lấy tên file ảnh cũ
$sql_select_image = "SELECT image FROM products WHERE id = ?";
$stmt_select = mysqli_prepare($conn, $sql_select_image);
$image_to_delete = "default.jpg"; // Mặc định

if ($stmt_select) {
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $result_image = mysqli_stmt_get_result($stmt_select);
    if ($row = mysqli_fetch_assoc($result_image)) {
        $image_to_delete = $row['image'];
    }
    mysqli_stmt_close($stmt_select);
}

// 3.2. Xóa file ảnh (nếu không phải là ảnh mặc định)
if ($image_to_delete != "default.jpg" && file_exists("uploads/" . $image_to_delete)) {
    unlink("uploads/" . $image_to_delete);
}
// --------------------------------------------------------


// Bước 4: Viết câu lệnh SQL DELETE
$sql = "DELETE FROM products WHERE id = ?";

// Bước 5: Chuẩn bị câu lệnh (sử dụng $conn)
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // 6. Gán giá trị ID vào dấu ?
    // "i" nghĩa là: Integer
    mysqli_stmt_bind_param($stmt, "i", $id);

    // 7. Thực thi câu lệnh
    if (mysqli_stmt_execute($stmt)) {
        // Xóa thành công!
    } else {
        echo "Lỗi khi xóa sản phẩm: " . mysqli_stmt_error($stmt);
    }

    // Đóng statement
    mysqli_stmt_close($stmt);

} else {
    echo "Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn);
}

// Bước 8: Gọi hàm disconnect_db() để đóng kết nối
disconnect_db();

// Bước 9: Chuyển hướng người dùng về trang danh sách
// (Dù thành công hay thất bại cũng nên quay về)
header("Location: product_list.php?status=deleted");
exit(); // Dừng chạy script ngay lập tức

?>