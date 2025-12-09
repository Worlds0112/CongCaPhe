<?php
require '../includes/auth_admin.php';
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['full_name']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $shift = $_POST['shift']; 
    $security_code = $_POST['security_code'];
    
    // Dữ liệu mới thêm
    $gender = $_POST['gender'];
    $birth_year = (int)$_POST['birth_year'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Kiểm tra trùng username
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Lỗi: Tên đăng nhập '$username' đã tồn tại!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Câu lệnh INSERT dài hơn
        $sql = "INSERT INTO users (username, password, full_name, role, security_code, shift, gender, birth_year, phone, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssiss", $username, $hashed_password, $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Thêm hồ sơ nhân viên thành công!";
        } else {
            $message = "Lỗi: " . mysqli_error($conn);
        }
    }
}

require '../includes/header.php'; 
?>

<style>
    .admin-wrapper { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #6f42c1; padding-left: 15px; }
    .form-container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
    .form-row { display: flex; gap: 20px; margin-bottom: 15px; } /* Chia 2 cột */
    .form-group { flex: 1; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;
    }
    .btn-add { background-color: #6f42c1; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;}
    .btn-add:hover { background-color: #59359a; }
    .btn-back { display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none; }
    .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    .message.ok { background: #d4edda; color: #155724; } .message.err { background: #f8d7da; color: #721c24; }
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
            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập (*):</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu (*):</label>
                    <input type="text" name="password" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên (*):</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Mã bảo mật (để reset pass):</label>
                    <input type="text" name="security_code" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Năm sinh:</label>
                    <input type="number" name="birth_year" value="2000">
                </div>
                <div class="form-group">
                    <label>Giới tính:</label>
                    <select name="gender">
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Phân ca làm việc:</label>
                    <select name="shift">
                        <option value="sang">Ca Sáng (06:00 - 12:00)</option>
                        <option value="chieu">Ca Chiều (12:00 - 18:00)</option>
                        <option value="toi">Ca Tối (18:00 - 23:00)</option>
                        <option value="full">Toàn thời gian (Full)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Vai trò:</label>
                    <select name="role">
                        <option value="staff">Nhân viên bán hàng</option>
                        <option value="admin">Quản trị viên (Admin)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Địa chỉ liên hệ:</label>
                <textarea name="address" rows="2"></textarea>
            </div>

            <button type="submit" class="btn-add">Lưu hồ sơ</button>
        </form>
    </div>
</div>

<?php require '../includes/footer.php'; ?>