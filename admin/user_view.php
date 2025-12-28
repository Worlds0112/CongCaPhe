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

// 3. XỬ LÝ QUYỀN VÀ SIDEBAR
$is_admin = ($_SESSION['role'] == 'admin');

if ($is_admin) {
    require '../includes/admin_sidebar.php'; 
    echo '<div class="main-with-sidebar">'; 
}

// 4. LẤY ID TỪ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 5. BẢO MẬT: CHẶN SOI HỒ SƠ NGƯỜI KHÁC
if (!$is_admin && $_SESSION['user_id'] != $id) {
    echo "<div class='admin-wrapper'><div class='alert error'>⛔ Bạn không có quyền xem hồ sơ này!</div></div>";
    if ($is_admin) echo '</div>'; 
    require '../includes/footer.php';
    exit();
}

// 6. TRUY VẤN DỮ LIỆU
$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "<div class='admin-wrapper'><div class='alert error'>Không tìm thấy nhân viên.</div></div>";
    if ($is_admin) echo '</div>';
    require '../includes/footer.php';
    exit();
}

// 7. XỬ LÝ ẢNH & TUỔI
$avatar_name = !empty($user['avatar']) ? $user['avatar'] : 'default_user.png';
$avatar_path = "uploads/" . $avatar_name;
if (!file_exists(__DIR__ . '/' . $avatar_path)) $avatar_path = "uploads/default_user.png";

$current_year = date("Y");
$age = $current_year - $user['birth_year'];

function get_shift_label($code) {
    switch ($code) {
        case 'sang': return "Ca Sáng (06:00 - 12:00)";
        case 'chieu': return "Ca Chiều (12:00 - 18:00)";
        case 'toi': return "Ca Tối (18:00 - 23:00)";
        default: return "Toàn thời gian";
    }
}
?>

<div class="admin-wrapper" <?php if(!$is_admin) echo 'style="margin: 0 auto;"'; ?>>
    
    <?php if ($is_admin): ?>
        <a href="user_list.php" class="btn-back" style="margin-bottom: 20px;">← Quay lại danh sách</a>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <div class="avatar-container">
                <img src="<?php echo $avatar_path; ?>" alt="Avatar">
            </div>

            <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            
            <div class="profile-role">
                <?php echo ($user['role'] == 'admin') ? 'Quản Trị Viên' : 'Nhân Viên Bán Hàng'; ?>
            </div>

            <div style="text-align: center;">
                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn-edit-profile">
                    ✏️ Cập nhật hồ sơ
                </a>
            </div>
        </div>

        <div class="profile-body">
            <div class="info-grid">
                
                <div class="info-item">
                    <div class="info-label">Tên đăng nhập</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>

                <div class="info-item shift-box">
                    <div class="info-label">Ca làm việc</div>
                    <div class="info-value"><?php echo get_shift_label($user['shift']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Giới tính</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['gender']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Tuổi</div>
                    <div class="info-value"><?php echo $age; ?> tuổi (Sinh năm <?php echo $user['birth_year']; ?>)</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Số điện thoại</div>
                    <div class="info-value">
                        <?php echo $user['phone'] ? $user['phone'] : '<span class="text-muted" style="font-style:italic;">Chưa cập nhật</span>'; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Mã bảo mật</div>
                    <div class="info-value" style="letter-spacing: 3px;">
                        ***<?php echo substr($user['security_code'], -2); ?>
                    </div>
                </div>

                <div class="info-item info-full">
                    <div class="info-label">Địa chỉ</div>
                    <div class="info-value">
                        <?php echo $user['address'] ? htmlspecialchars($user['address']) : '<span class="text-muted" style="font-style:italic;">Chưa cập nhật</span>'; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
// Đóng kết nối & Đóng div sidebar nếu có
if ($is_admin) echo '</div>'; 
?>