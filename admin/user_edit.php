<?php
// 1. KHỞI TẠO PHIÊN LÀM VIỆC & KIỂM TRA ĐĂNG NHẬP
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. KẾT NỐI CƠ SỞ DỮ LIỆU & GỌI GIAO DIỆN CHUNG
require '../includes/connect.php';
connect_db();
require '../includes/header.php'; 

// 3. XỬ LÝ THANH MENU (SIDEBAR) - CHỈ HIỆN VỚI ADMIN
$is_admin = ($_SESSION['role'] == 'admin');
if ($is_admin) {
    require '../includes/admin_sidebar.php'; 
    echo '<div class="main-with-sidebar">'; 
}

// Mở khung bao quanh nội dung (nếu là admin thì bỏ max-width để full màn hình)
echo '<div class="admin-wrapper" ' . ($is_admin ? 'style="margin: 0; max-width: none;"' : '') . '>';

// 4. LẤY ID NHÂN VIÊN CẦN SỬA
// Ưu tiên lấy từ URL (GET), nếu không có thì lấy từ form (POST) khi submit
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);

// --- BẢO MẬT: CHẶN NHÂN VIÊN SỬA HỒ SƠ NGƯỜI KHÁC ---
if (!$is_admin && $_SESSION['user_id'] != $id) {
    die("<div class='alert error'>Bạn không có quyền sửa thông tin người này!</div>");
}

// LẤY DỮ LIỆU CŨ TỪ DATABASE ĐỂ HIỂN THỊ LÊN FORM
$sql_old = "SELECT * FROM users WHERE id = $id";
$res_old = mysqli_query($conn, $sql_old);
$user_old = mysqli_fetch_assoc($res_old);

if (!$user_old) die("<div class='alert error'>Không tìm thấy nhân viên.</div>");

$message = "";
$msg_type = ""; 

// 5. XỬ KHI NGƯỜI DÙNG BẤM NÚT "LƯU THAY ĐỔI"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    
    // --- A. LẤY DỮ LIỆU TỪ FORM ---
    $fullname = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $birth_year = (int)$_POST['birth_year'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password_new = $_POST['password']; // Mật khẩu mới (nếu có)
    $avatar_name = $_POST['old_avatar']; // Giữ ảnh cũ mặc định

    // --- B. XỬ LÝ QUYỀN HẠN & MÃ BẢO MẬT (CHỈ ADMIN MỚI ĐƯỢC SỬA) ---
    if ($is_admin) {
        $role_new = $_POST['role'];
        $shift = $_POST['shift'];
        $security_code = trim($_POST['security_code']);

        // 🛡️ LUẬT 1: Không được tự hạ quyền Admin của chính mình xuống nhân viên
        if ($id == $_SESSION['user_id'] && $role_new != 'admin') {
            $message = "⚠️ Bạn không thể tự hạ quyền Admin của chính mình!";
            $msg_type = "error";
        } 
        // 🛡️ LUẬT 2: Không được sửa quyền của Super Admin (ID=1)
        elseif ($id == 1 && $role_new != 'admin') {
            $message = "⛔ Không thể thay đổi quyền của Super Admin!";
            $msg_type = "error";
        } else {
            $role = $role_new; 
        }
    } else {
        // Nếu là nhân viên thường -> Giữ nguyên dữ liệu cũ
        $role = $user_old['role'];
        $shift = $user_old['shift'];
        $security_code = $user_old['security_code'];
    }

    // --- C. VALIDATION (KIỂM TRA DỮ LIỆU HỢP LỆ) ---
    if (empty($fullname)) {
        $message = "Họ tên không được để trống."; $msg_type = "error";
    } 
    // Kiểm tra Mã bảo mật (Chỉ check nếu là Admin đang sửa)
    elseif ($is_admin && empty($security_code)) {
        $message = "Mã bảo mật không được để trống."; $msg_type = "error";
    } 
    elseif ($is_admin && strlen($security_code) < 6) { // [MỚI THÊM] Check độ dài
        $message = "Mã bảo mật phải có ít nhất 6 ký tự."; $msg_type = "error";
    }
    // Kiểm tra Năm sinh
    elseif ($birth_year < 1960 || $birth_year > date('Y') - 16) {
        $message = "Năm sinh không hợp lệ (Phải từ 16 tuổi)."; $msg_type = "error";
    } 
    // Kiểm tra Số điện thoại
    elseif (!empty($phone) && !is_numeric($phone)) {
        $message = "Số điện thoại không hợp lệ."; $msg_type = "error";
    } 
    // Kiểm tra Mật khẩu mới (nếu có nhập)
    elseif (!empty($password_new) && strlen($password_new) < 6) {
        $message = "Mật khẩu mới phải có ít nhất 6 ký tự."; $msg_type = "error";
    } else {
        
        // --- D. XỬ LÝ UPLOAD ẢNH ĐẠI DIỆN ---
        if (isset($_FILES['user_avatar']) && $_FILES['user_avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['user_avatar']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $_FILES['user_avatar']['size'] <= 5000000) { // Max 5MB
                $target_dir = "uploads/";
                $new_avatar_name = "user_" . time() . "_" . uniqid() . "." . $ext;
                
                if (move_uploaded_file($_FILES["user_avatar"]["tmp_name"], $target_dir . $new_avatar_name)) {
                    $avatar_name = $new_avatar_name;
                    // Xóa ảnh cũ (trừ ảnh mặc định) để tiết kiệm dung lượng
                    if ($user_old['avatar'] != 'default_user.png' && file_exists($target_dir . $user_old['avatar'])) {
                        unlink($target_dir . $user_old['avatar']);
                    }
                }
            } else {
                $message = "Lỗi ảnh: Chỉ chấp nhận JPG, PNG... dưới 5MB."; $msg_type = "error";
            }
        }

        // --- E. CẬP NHẬT VÀO DATABASE (NẾU KHÔNG CÓ LỖI) ---
        if (empty($message)) {
            // Làm sạch dữ liệu để tránh SQL Injection
            $fullname = mysqli_real_escape_string($conn, $fullname);
            $phone = mysqli_real_escape_string($conn, $phone);
            $address = mysqli_real_escape_string($conn, $address);
            $security_code = mysqli_real_escape_string($conn, $security_code);

            // Kiểm tra xem có đổi mật khẩu không
            if (!empty($password_new)) {
                $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=?, password=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssissssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $hashed_password, $id);
            } else {
                // Không đổi mật khẩu -> Giữ nguyên
                $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssisssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $message = "Cập nhật hồ sơ thành công!"; $msg_type = "success";
                // Lấy lại dữ liệu mới để hiển thị ngay lập tức
                $res_old = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
                $user_old = mysqli_fetch_assoc($res_old);
            } else {
                $message = "Lỗi SQL: " . mysqli_error($conn); $msg_type = "error";
            }
        }
    }
}
?>

<?php if ($is_admin): ?>
        <a href="user_list.php" class="btn-back">← Quay về danh sách</a>
    <?php else: ?>
        <a href="user_view.php?id=<?php echo $id; ?>" class="btn-back">← Quay lại xem hồ sơ</a>
    <?php endif; ?>

    <div class="form-container">
        <h2 class="title-user">Sửa hồ sơ: <?php echo htmlspecialchars($user_old['full_name']); ?></h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $msg_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $user_old['id']; ?>">
            <input type="hidden" name="old_avatar" value="<?php echo $user_old['avatar']; ?>">

            <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                <label>Ảnh đại diện:</label>
                <img src="uploads/<?php echo !empty($user_old['avatar']) ? $user_old['avatar'] : 'default_user.png'; ?>" class="current-avatar">
                <br>
                <input type="file" name="user_avatar" accept="image/*" class="form-control" style="width: auto; margin-top: 10px; display: inline-block;">
                <div class="text-hint">(Để trống nếu không muốn đổi ảnh)</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập (Không thể sửa):</label>
                    <input type="text" name="username" value="<?php echo $user_old['username']; ?>" readonly class="form-control">
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới:</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập để đổi (Để trống giữ nguyên)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên (*):</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_old['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mã bảo mật <?php echo !$is_admin ? '(Chỉ Admin sửa)' : '(*)'; ?>:</label>
                    <input type="text" name="security_code" class="form-control" value="<?php echo htmlspecialchars($user_old['security_code']); ?>" 
                           <?php echo $is_admin ? 'required' : 'readonly'; ?> placeholder="Ít nhất 6 ký tự">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Năm sinh:</label>
                    <input type="number" name="birth_year" class="form-control" value="<?php echo $user_old['birth_year']; ?>">
                </div>
                <div class="form-group">
                    <label>Giới tính:</label>
                    <select name="gender" class="form-control">
                        <option value="Nam" <?php if($user_old['gender']=='Nam') echo 'selected'; ?>>Nam</option>
                        <option value="Nữ" <?php if($user_old['gender']=='Nữ') echo 'selected'; ?>>Nữ</option>
                        <option value="Khác" <?php if($user_old['gender']=='Khác') echo 'selected'; ?>>Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_old['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Phân ca làm việc <?php echo !$is_admin ? '(Chỉ Admin sửa)' : ''; ?>:</label>
                    <select name="shift" class="form-control" <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        <option value="sang" <?php if($user_old['shift']=='sang') echo 'selected'; ?>>Ca Sáng (06:00 - 12:00)</option>
                        <option value="chieu" <?php if($user_old['shift']=='chieu') echo 'selected'; ?>>Ca Chiều (12:00 - 18:00)</option>
                        <option value="toi" <?php if($user_old['shift']=='toi') echo 'selected'; ?>>Ca Tối (18:00 - 23:00)</option>
                        <option value="full" <?php if($user_old['shift']=='full') echo 'selected'; ?>>Toàn thời gian (Full)</option>
                    </select>
                    <?php if (!$is_admin): ?>
                        <input type="hidden" name="shift" value="<?php echo $user_old['shift']; ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Vai trò <?php echo !$is_admin ? '(Chỉ Admin sửa)' : ''; ?>:</label>
                    <select name="role" class="form-control" <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        <option value="staff" <?php if($user_old['role']=='staff') echo 'selected'; ?>>Nhân viên</option>
                        <option value="admin" <?php if($user_old['role']=='admin') echo 'selected'; ?>>Quản trị viên</option>
                    </select>
                    <?php if (!$is_admin): ?>
                        <input type="hidden" name="role" value="<?php echo $user_old['role']; ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user_old['address']); ?></textarea>
            </div>

            <button type="submit" name="save_user" class="btn-save">Lưu thay đổi</button>
        </form>
    </div>

<?php 
// Đóng thẻ div wrapper
echo '</div>'; 
if ($is_admin) echo '</div>'; 
?>