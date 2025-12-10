<?php
// --- THAY ĐỔI CƠ CHẾ BẢO VỆ ---
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ------------------------------

$message = "";
$user = null;

// 1. LẤY ID CẦN SỬA
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);

// --- BẢO MẬT: CHẶN SỬA NGƯỜI KHÁC ---
if ($_SESSION['role'] != 'admin' && $_SESSION['user_id'] != $id) {
    die("Bạn không có quyền sửa thông tin người khác!");
}
// -------------------------------------

// ... (Phần code xử lý POST và hiển thị giữ nguyên như bài trước) ...

// LẤY DỮ LIỆU CŨ TỪ CSDL TRƯỚC (Để dùng nếu nhân viên cố tình hack form)
$sql_old = "SELECT * FROM users WHERE id = $id";
$res_old = mysqli_query($conn, $sql_old);
$user_old = mysqli_fetch_assoc($res_old);

if (!$user_old) die("Không tìm thấy nhân viên.");

// 2. XỬ LÝ KHI BẤM LƯU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    
    // --- DỮ LIỆU ĐƯỢC PHÉP SỬA (CHO TẤT CẢ) ---
    $fullname = mysqli_real_escape_string($conn, $_POST['full_name']);
    $gender = $_POST['gender'];
    $birth_year = (int)$_POST['birth_year'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $avatar_name = $_POST['old_avatar'];

    // --- DỮ LIỆU NHẠY CẢM (CHỈ ADMIN ĐƯỢC SỬA) ---
    if ($_SESSION['role'] == 'admin') {
        // Nếu là Admin -> Lấy dữ liệu từ Form
        $role = $_POST['role'];
        $shift = $_POST['shift'];
        $security_code = $_POST['security_code'];
    } else {
        // Nếu là Nhân viên -> Giữ nguyên dữ liệu cũ trong CSDL
        $role = $user_old['role'];
        $shift = $user_old['shift'];
        $security_code = $user_old['security_code'];
    }

    // XỬ LÝ UPLOAD ẢNH
    if (isset($_FILES['user_avatar']) && $_FILES['user_avatar']['error'] == 0) {
        $target_dir = "uploads/";
        $original_name = basename($_FILES["user_avatar"]["name"]);
        $new_avatar_name = "user_" . time() . "_" . $original_name;
        $target_file = $target_dir . $new_avatar_name;

        if (move_uploaded_file($_FILES["user_avatar"]["tmp_name"], $target_file)) {
            $avatar_name = $new_avatar_name;
            if ($_POST['old_avatar'] != 'default_user.png' && file_exists($target_dir . $_POST['old_avatar'])) {
                unlink($target_dir . $_POST['old_avatar']);
            }
        }
    }

    // CÂU LỆNH UPDATE
    if (!empty($_POST['password'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=?, password=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssissssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $hashed_password, $id);
    } else {
        $sql = "UPDATE users SET full_name=?, role=?, security_code=?, shift=?, gender=?, birth_year=?, phone=?, address=?, avatar=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssisssi", $fullname, $role, $security_code, $shift, $gender, $birth_year, $phone, $address, $avatar_name, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $message = "Cập nhật thông tin thành công!";
        // Cập nhật lại biến $user_old để form hiển thị dữ liệu mới
        $res_old = mysqli_query($conn, $sql_old);
        $user_old = mysqli_fetch_assoc($res_old);
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}

require '../includes/header.php'; 

// Biến kiểm tra Admin để dùng trong HTML
$isAdmin = ($_SESSION['role'] == 'admin');
?>

<style>
    .admin-wrapper { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #ffc107; padding-left: 15px; }
    .form-container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
    .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
    .form-group { flex: 1; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; }
    
    /* Style cho input bị khóa */
    input[readonly], select[disabled] { background-color: #e9ecef; cursor: not-allowed; color: #6c757d; }

    .btn-save { background-color: #007bff; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;}
    .btn-save:hover { background-color: #0069d9; }
    .btn-back { display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none; }
    .current-avatar { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-top: 10px; border: 3px solid #eee; }
    .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    .message.ok { background: #d4edda; color: #155724; }
</style>

<div class="admin-wrapper">
    <h2>Sửa thông tin: <?php echo htmlspecialchars($user_old['full_name']); ?></h2>
    
    <?php if ($isAdmin): ?>
        <a href="user_list.php" class="btn-back">← Quay về danh sách</a>
    <?php else: ?>
        <a href="user_view.php?id=<?php echo $id; ?>" class="btn-back">← Quay lại xem hồ sơ</a>
    <?php endif; ?>

    <?php if ($message != "") echo "<div class='message ok'>$message</div>"; ?>

    <div class="form-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $user_old['id']; ?>">
            <input type="hidden" name="old_avatar" value="<?php echo $user_old['avatar']; ?>">

            <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                <label>Ảnh đại diện:</label>
                <img src="/QuanLyCaPhe/admin/uploads/<?php echo $user_old['avatar'] ? $user_old['avatar'] : 'default_user.png'; ?>" class="current-avatar">
                <br>
                <input type="file" name="user_avatar" accept="image/*" style="width: auto; margin-top: 10px;">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập (Cố định):</label>
                    <input type="text" name="username" value="<?php echo $user_old['username']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mật khẩu (Để trống nếu không đổi):</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu mới...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên:</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_old['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mã bảo mật:</label>
                    <input type="text" name="security_code" value="<?php echo htmlspecialchars($user_old['security_code']); ?>" 
                           <?php echo $isAdmin ? '' : 'readonly'; ?>>
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
                    <label>Phân ca làm việc <?php echo $isAdmin ? '' : '(Chỉ Admin sửa)'; ?>:</label>
                    <select name="shift" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                        <option value="sang" <?php if($user_old['shift']=='sang') echo 'selected'; ?>>Ca Sáng (06:00 - 12:00)</option>
                        <option value="chieu" <?php if($user_old['shift']=='chieu') echo 'selected'; ?>>Ca Chiều (12:00 - 18:00)</option>
                        <option value="toi" <?php if($user_old['shift']=='toi') echo 'selected'; ?>>Ca Tối (18:00 - 23:00)</option>
                        <option value="full" <?php if($user_old['shift']=='full') echo 'selected'; ?>>Toàn thời gian (Full)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Vai trò <?php echo $isAdmin ? '' : '(Chỉ Admin sửa)'; ?>:</label>
                    <select name="role" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                        <option value="staff" <?php if($user_old['role']=='staff') echo 'selected'; ?>>Nhân viên</option>
                        <option value="admin" <?php if($user_old['role']=='admin') echo 'selected'; ?>>Quản trị viên</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="address" rows="2"><?php echo htmlspecialchars($user_old['address']); ?></textarea>
            </div>

            <button type="submit" name="save_user" class="btn-save">Lưu thay đổi</button>
        </form>
    </div>
</div>
