<?php
require '../includes/auth_admin.php';
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['full_name']);
    $password = $_POST['password']; // Pass thô
    $role = $_POST['role'];
    $security_code = $_POST['security_code'];

    // 1. Kiểm tra username đã tồn tại chưa
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Lỗi: Tên đăng nhập '$username' đã tồn tại!";
    } else {
        // 2. Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Thêm vào CSDL
        $sql = "INSERT INTO users (username, password, full_name, role, security_code) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $fullname, $role, $security_code);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Thêm tài khoản thành công!";
        } else {
            $message = "Lỗi: " . mysqli_error($conn);
        }
    }
}

require '../includes/header.php'; 
?>

<style>
    .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #6f42c1; padding-left: 15px; }
    
    .form-container {
        background: #fff; padding: 30px; border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        max-width: 600px; margin: 0 auto;
    }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; }
    .form-group input, .form-group select {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; box-sizing: border-box;
    }
    .btn-add { background-color: #6f42c1; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; }
    .btn-add:hover { background-color: #59359a; }
    .btn-back { display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none; }
    .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center;}
    .message.ok { background: #d4edda; color: #155724; }
    .message.err { background: #f8d7da; color: #721c24; }
</style>

<div class="admin-wrapper">
    <h2>Thêm Nhân viên mới</h2>
    <a href="user_list.php" class="btn-back">← Quay về danh sách</a>

    <?php if ($message != ""): ?>
        <div class="message <?php echo strpos($message, 'Lỗi') !== false ? 'err' : 'ok'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form action="" method="POST">
            <div class="form-group">
                <label>Tên đăng nhập (viết liền, không dấu):</label>
                <input type="text" name="username" required placeholder="vd: nhanvien2">
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="text" name="password" required placeholder="vd: 123456">
            </div>
            <div class="form-group">
                <label>Họ và tên:</label>
                <input type="text" name="full_name" required placeholder="vd: Nguyễn Văn A">
            </div>
            <div class="form-group">
                <label>Vai trò:</label>
                <select name="role">
                    <option value="staff">Nhân viên bán hàng</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Mã bảo mật (Dùng khi quên mật khẩu):</label>
                <input type="text" name="security_code" required placeholder="vd: 112233">
            </div>
            <button type="submit" class="btn-add">Tạo tài khoản</button>
        </form>
    </div>
</div>

<?php require '../includes/footer.php'; ?>