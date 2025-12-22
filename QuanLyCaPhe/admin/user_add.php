<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php'; 

echo '<div class="main-with-sidebar">';
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

$error_msg = ""; 
$success_msg = "";

// Biến giữ lại giá trị cũ
$old_user = ""; $old_name = ""; $old_role = "staff"; $old_shift = "full"; 
$old_code = ""; $old_gender = "Nam"; $old_year = "2000"; $old_phone = ""; $old_addr = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. LẤY DỮ LIỆU & CLEAN
    $username = trim($_POST['username']);
    $fullname = trim($_POST['full_name']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $shift = $_POST['shift']; 
    $security_code = trim($_POST['security_code']);
    
    $gender = $_POST['gender'];
    $birth_year = (int)$_POST['birth_year'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Giữ lại giá trị cũ để điền lại form nếu lỗi
    $old_user = $username; $old_name = $fullname; $old_role = $role; $old_shift = $shift;
    $old_code = $security_code; $old_gender = $gender; $old_year = $birth_year; 
    $old_phone = $phone; $old_addr = $address;

    // 2. VALIDATION (BẮT LỖI)
    if (empty($username) || empty($password) || empty($fullname) || empty($security_code)) {
        $error_msg = "Vui lòng điền đầy đủ các trường bắt buộc (*).";
    } elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error_msg = "Tên đăng nhập phải từ 4 ký tự, không chứa dấu hoặc ký tự đặc biệt.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($birth_year < 1960 || $birth_year > date('Y') - 16) {
        $error_msg = "Năm sinh không hợp lệ (Nhân viên phải từ 16 tuổi).";
    } elseif (!empty($phone) && !is_numeric($phone)) {
        $error_msg = "Số điện thoại phải là số.";
    } else {
        // 3. KIỂM TRA TRÙNG USERNAME TRONG DB
        $username = mysqli_real_escape_string($conn, $username);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        
        if (mysqli_num_rows($check) > 0) {
            $error_msg = "Tên đăng nhập '$username' đã tồn tại! Vui lòng chọn tên khác.";
        } else {
            // 4. XỬ LÝ NẾU KHÔNG CÓ LỖI
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $fullname = mysqli_real_escape_string($conn, $fullname);
            $security_code = mysqli_real_escape_string($conn, $security_code);
            $phone = mysqli_real_escape_string($conn, $phone);
            $address = mysqli_real_escape_string($conn, $address);

            $sql = "INSERT INTO users (username, password, full_name, role, security_code, shift, gender, birth_year, phone, address) 
                    VALUES ('$username', '$hashed_password', '$fullname', '$role', '$security_code', '$shift', '$gender', '$birth_year', '$phone', '$address')";
            
            if (mysqli_query($conn, $sql)) {
                $success_msg = "Thêm nhân viên <b>$fullname</b> thành công!";
                // Reset form sau khi thành công
                $old_user = ""; $old_name = ""; $old_code = ""; $old_phone = ""; $old_addr = "";
            } else {
                $error_msg = "Lỗi SQL: " . mysqli_error($conn);
            }
        }
    }
}
?>

<style>
    .form-container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); max-width: 800px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #6f42c1; padding-left: 15px; }
    .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
    .form-group { flex: 1; }
    label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; font-size: 14px; }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .btn-add { background-color: #6f42c1; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.3s; }
    .btn-add:hover { background-color: #59359a; }
    .btn-back { display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none; }
    
    /* Thông báo lỗi/thành công */
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
</style>

<h2>Thêm Nhân viên mới</h2>
<a href="user_list.php" class="btn-back">← Quay về danh sách</a>

<?php if($error_msg): ?>
    <div class="alert alert-danger">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
    <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>
<?php endif; ?>

<div class="form-container">
    <form action="" method="POST">
        
        <div class="form-row">
            <div class="form-group">
                <label>Tên đăng nhập (*)</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($old_user); ?>" placeholder="VD: nv_an (viết liền không dấu)" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu (*)</label>
                <input type="text" name="password" placeholder="Tối thiểu 6 ký tự" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Họ và tên (*)</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($old_name); ?>" required>
            </div>
            <div class="form-group">
                <label>Mã bảo mật (để reset pass) (*)</label>
                <input type="text" name="security_code" value="<?php echo htmlspecialchars($old_code); ?>" placeholder="VD: 123456" maxlength="6" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Năm sinh</label>
                <input type="number" name="birth_year" value="<?php echo $old_year; ?>">
            </div>
            <div class="form-group">
                <label>Giới tính</label>
                <select name="gender">
                    <option value="Nam" <?php if($old_gender=='Nam') echo 'selected'; ?>>Nam</option>
                    <option value="Nữ" <?php if($old_gender=='Nữ') echo 'selected'; ?>>Nữ</option>
                    <option value="Khác" <?php if($old_gender=='Khác') echo 'selected'; ?>>Khác</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($old_phone); ?>">
            </div>
            <div class="form-group">
                <label>Phân ca làm việc</label>
                <select name="shift">
                    <option value="sang" <?php if($old_shift=='sang') echo 'selected'; ?>>Ca Sáng (06:00 - 12:00)</option>
                    <option value="chieu" <?php if($old_shift=='chieu') echo 'selected'; ?>>Ca Chiều (12:00 - 18:00)</option>
                    <option value="toi" <?php if($old_shift=='toi') echo 'selected'; ?>>Ca Tối (18:00 - 23:00)</option>
                    <option value="full" <?php if($old_shift=='full') echo 'selected'; ?>>Toàn thời gian (Full)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Vai trò</label>
                <select name="role">
                    <option value="staff" <?php if($old_role=='staff') echo 'selected'; ?>>Nhân viên bán hàng</option>
                    <option value="admin" <?php if($old_role=='admin') echo 'selected'; ?>>Quản trị viên (Admin)</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Địa chỉ liên hệ</label>
            <textarea name="address" rows="2"><?php echo htmlspecialchars($old_addr); ?></textarea>
        </div>

        <button type="submit" class="btn-add">Lưu hồ sơ</button>
    </form>
</div>

<?php 
echo '</div></div>'; // Đóng div wrapper
?>