<?php
// 1. KHỞI TẠO SESSION
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. KẾT NỐI
require '../includes/connect.php';
connect_db();
require '../includes/header.php'; 

// 3. XỬ LÝ SIDEBAR THÔNG MINH
$is_admin = ($_SESSION['role'] == 'admin');
if ($is_admin) {
    require '../includes/admin_sidebar.php'; 
    echo '<div class="main-with-sidebar">'; 
}

echo '<div class="admin-wrapper" ' . ($is_admin ? 'style="margin: 0; max-width: none;"' : '') . '>';

// 4. LẤY ID CẦN SỬA
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);

// --- BẢO MẬT: CHẶN SỬA NGƯỜI KHÁC (TRỪ ADMIN) ---
if (!$is_admin && $_SESSION['user_id'] != $id) {
    die("<div class='alert alert-danger'>Bạn không có quyền sửa thông tin người này!</div>");
}

// LẤY DỮ LIỆU CŨ TỪ CSDL
$sql_old = "SELECT * FROM users WHERE id = $id";
$res_old = mysqli_query($conn, $sql_old);
$user_old = mysqli_fetch_assoc($res_old);

if (!$user_old) die("<div class='alert alert-danger'>Không tìm thấy nhân viên.</div>");

$message = "";
$msg_type = ""; // 'success' hoặc 'danger'

// 5. XỬ LÝ KHI BẤM LƯU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    
    // --- LẤY DỮ LIỆU TỪ FORM ---
    $fullname = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $birth_year = (int)$_POST['birth_year'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password_new = $_POST['password'];
    $avatar_name = $_POST['old_avatar'];

    // --- XỬ LÝ QUYỀN HẠN ---
    if ($is_admin) {
        $role_new = $_POST['role'];
        $shift = $_POST['shift'];
        $security_code = trim($_POST['security_code']);

        // 🛡️ LUẬT 1: KHÔNG ĐƯỢC TỰ HẠ QUYỀN MÌNH
        if ($id == $_SESSION['user_id'] && $role_new != 'admin') {
            $message = "⚠️ Bạn không thể tự hạ quyền Admin của chính mình xuống Nhân viên!";
            $msg_type = "danger";
        }
        // 🛡️ LUẬT 2: KHÔNG AI ĐƯỢC SỬA QUYỀN CỦA CHỦ HỆ THỐNG (ID = 1)
        elseif ($id == 1 && $role_new != 'admin') {
            $message = "⛔ Không thể thay đổi quyền hạn của Chủ hệ thống (Super Admin)!";
            $msg_type = "danger";
        }
        else {
            $role = $role_new; // Nếu qua được 2 luật trên thì mới cho gán role mới
        }
    } else {
        // Nhân viên thì giữ nguyên
        $role = $user_old['role'];
        $shift = $user_old['shift'];
        $security_code = $user_old['security_code'];
    }

    // --- VALIDATION (BẮT LỖI) ---
    if (empty($fullname)) {
        $message = "Họ tên không được để trống.";
        $msg_type = "danger";
    } elseif ($is_admin && empty($security_code)) {
        $message = "Mã bảo mật không được để trống.";
        $msg_type = "danger";
    } elseif ($birth_year < 1960 || $birth_year > date('Y') - 16) {
        $message = "Năm sinh không hợp lệ (Phải từ 16 tuổi).";
        $msg_type = "danger";
    } elseif (!empty($phone) && !is_numeric($phone)) {
        $message = "Số điện thoại không hợp lệ.";
        $msg_type = "danger";
    } elseif (!empty($password_new) && strlen($password_new) < 6) {
        $message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        $msg_type = "danger";
    } else {
        // --- XỬ LÝ UPLOAD ẢNH (NẾU CÓ) ---
        if (isset($_FILES['user_avatar']) && $_FILES['user_avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['user_avatar']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $_FILES['user_avatar']['size'] <= 5000000) {
                $target_dir = "uploads/";
                $new_avatar_name = "user_" . time() . "_" . uniqid() . "." . $ext;
                
                if (move_uploaded_file($_FILES["user_avatar"]["tmp_name"], $target_dir . $new_avatar_name)) {
                    $avatar_name = $new_avatar_name;
                    // Xóa ảnh cũ (trừ ảnh mặc định)
                    if ($user_old['avatar'] != 'default_user.png' && file_exists($target_dir . $user_old['avatar'])) {
                        unlink($target_dir . $user_old['avatar']);
                    }
                }
            } else {
                $message = "Lỗi ảnh: Chỉ chấp nhận JPG, PNG... dưới 5MB.";
                $msg_type = "danger";
            }
        }

        // --- CẬP NHẬT CSDL NẾU KHÔNG CÓ LỖI ẢNH ---
        if (empty($message)) {
            $fullname = mysqli_real_escape_string($conn, $fullname);
            $phone = mysqli_real_escape_string($conn, $phone);
            $address = mysqli_real_escape_string($conn, $address);
            $security_code = mysqli_real_escape_string($conn, $security_code);

            // Kiểm tra có đổi mật khẩu không
            if (!empty($password_new)) {
                $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=?, password=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssissssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $hashed_password, $id);
            } else {
                $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssisssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $message = "Cập nhật hồ sơ thành công!";
                $msg_type = "success";
                
                // Refresh dữ liệu mới để hiển thị
                $res_old = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
                $user_old = mysqli_fetch_assoc($res_old);
            } else {
                $message = "Lỗi SQL: " . mysqli_error($conn);
                $msg_type = "danger";
            }
        }
    }
}
?>

<style>
    .form-container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); max-width: 800px; margin: 0 auto; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #ffc107; padding-left: 15px; }
    .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
    .form-group { flex: 1; }
    label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; font-size: 14px; }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    
    /* Style cho input bị khóa */
    input[readonly], select[disabled] { background-color: #e9ecef; cursor: not-allowed; color: #6c757d; }

    .btn-save { background-color: #007bff; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;}
    .btn-save:hover { background-color: #0069d9; }
    .btn-back { display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: 500; }
    .current-avatar { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-top: 10px; border: 3px solid #eee; }
    
    /* Thông báo */
    .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    @media (max-width: 600px) { .form-row { flex-direction: column; gap: 0; } }
</style>

    <?php if ($is_admin): ?>
        <a href="user_list.php" class="btn-back">← Quay về danh sách</a>
    <?php else: ?>
        <a href="user_view.php?id=<?php echo $id; ?>" class="btn-back">← Quay lại xem hồ sơ</a>
    <?php endif; ?>

    <div class="form-container">
        <h2>Sửa hồ sơ: <?php echo htmlspecialchars($user_old['full_name']); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $user_old['id']; ?>">
            <input type="hidden" name="old_avatar" value="<?php echo $user_old['avatar']; ?>">

            <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                <label>Ảnh đại diện:</label>
                <img src="uploads/<?php echo !empty($user_old['avatar']) ? $user_old['avatar'] : 'default_user.png'; ?>" class="current-avatar">
                <br>
                <input type="file" name="user_avatar" accept="image/*" style="width: auto; margin-top: 10px;">
                <div style="font-size: 12px; color: #999; margin-top: 5px;">(Để trống nếu không muốn đổi ảnh)</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập (Không thể sửa):</label>
                    <input type="text" name="username" value="<?php echo $user_old['username']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới:</label>
                    <input type="password" name="password" placeholder="Nhập để đổi (Để trống giữ nguyên)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên (*):</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_old['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mã bảo mật <?php echo !$is_admin ? '(Chỉ Admin sửa)' : '(*)'; ?>:</label>
                    <input type="text" name="security_code" value="<?php echo htmlspecialchars($user_old['security_code']); ?>" maxlength="6"
                           <?php echo $is_admin ? 'required' : 'readonly'; ?>>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Năm sinh:</label>
                    <input type="number" name="birth_year" value="<?php echo $user_old['birth_year']; ?>">
                </div>
                <div class="form-group">
                    <label>Giới tính:</label>
                    <select name="gender">
                        <option value="Nam" <?php if($user_old['gender']=='Nam') echo 'selected'; ?>>Nam</option>
                        <option value="Nữ" <?php if($user_old['gender']=='Nữ') echo 'selected'; ?>>Nữ</option>
                        <option value="Khác" <?php if($user_old['gender']=='Khác') echo 'selected'; ?>>Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user_old['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Phân ca làm việc <?php echo !$is_admin ? '(Chỉ Admin sửa)' : ''; ?>:</label>
                    <select name="shift" <?php echo !$is_admin ? 'disabled' : ''; ?>>
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
                    <select name="role" <?php echo !$is_admin ? 'disabled' : ''; ?>>
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
                <textarea name="address" rows="2"><?php echo htmlspecialchars($user_old['address']); ?></textarea>
            </div>

            <button type="submit" name="save_user" class="btn-save">Lưu thay đổi</button>
        </form>
    </div>

<?php 
echo '</div>'; // Đóng admin-wrapper
if ($is_admin) echo '</div>'; // Đóng main-with-sidebar
?>