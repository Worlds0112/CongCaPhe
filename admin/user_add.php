<?php
// 1. KẾT NỐI VÀ BẢO VỆ TRANG (CHỈ ADMIN ĐƯỢC VÀO)
require '../includes/auth_admin.php'; // Kiểm tra đăng nhập và quyền Admin
require '../includes/header.php';     // Gọi phần đầu trang (HTML head, CSS)
require '../includes/admin_sidebar.php'; // Gọi thanh menu bên trái

echo '<div class="main-with-sidebar">'; // Mở khung nội dung chính
echo '<div class="admin-wrapper" style="margin: 0; max-width: none;">';

// Khởi tạo các biến thông báo
$error_msg = ""; 
$success_msg = "";

// Biến lưu giữ giá trị cũ (để điền lại form nếu người dùng nhập sai)
$old_user = ""; $old_name = ""; $old_role = "staff"; $old_shift = "full"; 
$old_code = ""; $old_gender = "Nam"; $old_year = "2000"; $old_phone = ""; $old_addr = "";

// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT "LƯU HỒ SƠ" (METHOD POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- LẤY DỮ LIỆU TỪ FORM ---
    $username = trim($_POST['username']);       // Tên đăng nhập
    $fullname = trim($_POST['full_name']);      // Họ tên đầy đủ
    $password = $_POST['password'];             // Mật khẩu
    $role = $_POST['role'];                     // Vai trò (admin/staff)
    $shift = $_POST['shift'];                   // Ca làm việc
    $security_code = trim($_POST['security_code']); // Mã bảo mật
    
    $gender = $_POST['gender'];                 // Giới tính
    $birth_year = (int)$_POST['birth_year'];    // Năm sinh (ép kiểu số nguyên)
    $phone = trim($_POST['phone']);             // Số điện thoại
    $address = trim($_POST['address']);         // Địa chỉ

    // Lưu lại giá trị cũ vào biến để hiển thị lại
    $old_user = $username; $old_name = $fullname; $old_role = $role; $old_shift = $shift;
    $old_code = $security_code; $old_gender = $gender; $old_year = $birth_year; 
    $old_phone = $phone; $old_addr = $address;

    // 3. VALIDATION (KIỂM TRA DỮ LIỆU ĐẦU VÀO)
    // Kiểm tra các trường bắt buộc
    if (empty($username) || empty($password) || empty($fullname) || empty($security_code)) {
        $error_msg = "Vui lòng điền đầy đủ các trường bắt buộc (*).";
    } 
    // Kiểm tra độ dài và định dạng Username (Từ 4 ký tự, không dấu)
    elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error_msg = "Tên đăng nhập phải từ 4 ký tự, không chứa dấu hoặc ký tự đặc biệt.";
    } 
    // Kiểm tra độ dài Mật khẩu (Từ 6 ký tự)
    elseif (strlen($password) < 6) {
        $error_msg = "Mật khẩu phải có ít nhất 6 ký tự.";
    } 
    // [MỚI] Kiểm tra độ dài Mã bảo mật (Từ 6 ký tự)
    elseif (strlen($security_code) < 6) {
        $error_msg = "Mã bảo mật phải có ít nhất 6 ký tự.";
    }
    // Kiểm tra độ tuổi (Phải từ 16 tuổi trở lên)
    elseif ($birth_year < 1960 || $birth_year > date('Y') - 16) {
        $error_msg = "Năm sinh không hợp lệ (Nhân viên phải từ 16 tuổi).";
    } 
    // Kiểm tra số điện thoại (Phải là số nếu có nhập)
    elseif (!empty($phone) && !is_numeric($phone)) {
        $error_msg = "Số điện thoại phải là số.";
    } else {
        
        // 4. KIỂM TRA TRÙNG LẶP TRONG CƠ SỞ DỮ LIỆU
        // Clean dữ liệu trước khi query để tránh SQL Injection
        $username = mysqli_real_escape_string($conn, $username);
        
        // Query kiểm tra xem username đã tồn tại chưa
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        
        if (mysqli_num_rows($check) > 0) {
            $error_msg = "Tên đăng nhập '$username' đã tồn tại! Vui lòng chọn tên khác.";
        } else {
            // 5. THỰC HIỆN THÊM MỚI (INSERT) NẾU KHÔNG CÓ LỖI
            
            // Mã hóa mật khẩu (Quan trọng để bảo mật)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Clean các dữ liệu text khác
            $fullname = mysqli_real_escape_string($conn, $fullname);
            $security_code = mysqli_real_escape_string($conn, $security_code);
            $phone = mysqli_real_escape_string($conn, $phone);
            $address = mysqli_real_escape_string($conn, $address);

            // Câu lệnh SQL thêm mới
            $sql = "INSERT INTO users (username, password, full_name, role, security_code, shift, gender, birth_year, phone, address) 
                    VALUES ('$username', '$hashed_password', '$fullname', '$role', '$security_code', '$shift', '$gender', '$birth_year', '$phone', '$address')";
            
            // Thực thi câu lệnh
            if (mysqli_query($conn, $sql)) {
                $success_msg = "Thêm nhân viên <b>$fullname</b> thành công!";
                // Reset form về rỗng sau khi thêm thành công
                $old_user = ""; $old_name = ""; $old_code = ""; $old_phone = ""; $old_addr = "";
            } else {
                $error_msg = "Lỗi SQL: " . mysqli_error($conn); // Báo lỗi nếu insert thất bại
            }
        }
    }
}
?>

<div class="header-row">
        <h2 class="title-user" style="margin: 0;">Thêm Nhân viên mới</h2>
        <a href="user_list.php" class="btn-back">← Quay về danh sách</a>
    </div>

    <?php if($error_msg): ?>
        <div class="alert error">⚠️ <?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <?php if($success_msg): ?>
        <div class="alert success">✅ <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form action="" method="POST">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập (*)</label>
                    <input type="text" name="username" class="form-control user-input" value="<?php echo htmlspecialchars($old_user); ?>" placeholder="VD: nv_an (viết liền không dấu)" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu (*)</label>
                    <input type="text" name="password" class="form-control user-input" placeholder="Tối thiểu 6 ký tự" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên (*)</label>
                    <input type="text" name="full_name" class="form-control user-input" value="<?php echo htmlspecialchars($old_name); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mã bảo mật (để reset pass) (*)</label>
                    <input type="text" name="security_code" class="form-control user-input" value="<?php echo htmlspecialchars($old_code); ?>" placeholder="VD: 123456 (Tối thiểu 6 ký tự)" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Năm sinh</label>
                    <input type="number" name="birth_year" class="form-control user-input" value="<?php echo $old_year; ?>">
                </div>
                <div class="form-group">
                    <label>Giới tính</label>
                    <select name="gender" class="form-control user-input">
                        <option value="Nam" <?php if($old_gender=='Nam') echo 'selected'; ?>>Nam</option>
                        <option value="Nữ" <?php if($old_gender=='Nữ') echo 'selected'; ?>>Nữ</option>
                        <option value="Khác" <?php if($old_gender=='Khác') echo 'selected'; ?>>Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control user-input" value="<?php echo htmlspecialchars($old_phone); ?>">
                </div>
                <div class="form-group">
                    <label>Phân ca làm việc</label>
                    <select name="shift" class="form-control user-input">
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
                    <select name="role" class="form-control user-input">
                        <option value="staff" <?php if($old_role=='staff') echo 'selected'; ?>>Nhân viên bán hàng</option>
                        <option value="admin" <?php if($old_role=='admin') echo 'selected'; ?>>Quản trị viên (Admin)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Địa chỉ liên hệ</label>
                <textarea name="address" class="form-control user-input" rows="2"><?php echo htmlspecialchars($old_addr); ?></textarea>
            </div>

            <button type="submit" class="btn-add-user" style="width: 100%; margin-top: 15px;">
                💾 Lưu hồ sơ
            </button>
        </form>
    </div>

<?php 
// Đóng các thẻ div wrapper
echo '</div>'; echo '</div>';
?>