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
// Biến kiểm tra xem có phải Admin không
$is_admin = ($_SESSION['role'] == 'admin');

// Chỉ hiện Sidebar nếu là Admin
if ($is_admin) {
    require '../includes/admin_sidebar.php'; 
    echo '<div class="main-with-sidebar">'; // Mở div đẩy nội dung
}

// 4. LẤY ID TỪ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 5. BẢO MẬT: CHẶN SOI HỒ SƠ NGƯỜI KHÁC
// Nếu không phải Admin và ID muốn xem khác ID đang đăng nhập -> Chặn
if (!$is_admin && $_SESSION['user_id'] != $id) {
    echo "<div class='admin-wrapper'><h3 style='color:red; text-align:center; margin-top:50px;'>⛔ Bạn không có quyền xem hồ sơ này!</h3></div>";
    if ($is_admin) echo '</div>'; // Đóng div sidebar nếu lỡ mở
    require '../includes/footer.php';
    exit();
}

// 6. TRUY VẤN DỮ LIỆU
$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "<div class='admin-wrapper'><h3 style='text-align:center; margin-top:50px;'>Không tìm thấy nhân viên.</h3></div>";
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

<style>
    /* Canh lề: Nếu là Admin (có sidebar) thì padding bình thường, nếu Nhân viên thì ra giữa */
    .admin-wrapper { 
        max-width: 900px; 
        margin: 0 auto; 
        padding: 30px 20px; 
    }
    
    .profile-card { 
        background: white; border-radius: 12px; overflow: hidden; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        display: flex; flex-direction: column; 
    }
    
    .profile-header { 
        background: linear-gradient(135deg, #6f42c1, #8e44ad); 
        padding: 40px 20px; color: white; text-align: center; 
        position: relative;
    }
    
    /* Hiệu ứng cong nhẹ ở header */
    .profile-header::after {
        content: ''; position: absolute; bottom: -20px; left: 0; width: 100%; height: 40px;
        background: white; border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    }
    
    .avatar-container { 
        width: 140px; height: 140px; margin: 0 auto 15px auto; 
        border-radius: 50%; border: 5px solid rgba(255,255,255,0.5); 
        overflow: hidden; background: white; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
        position: relative; z-index: 2;
    }
    .avatar-container img { width: 100%; height: 100%; object-fit: cover; }
    
    .profile-name { font-size: 26px; font-weight: bold; margin-bottom: 5px; position: relative; z-index: 2; }
    .profile-role { opacity: 0.9; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; position: relative; z-index: 2; }
    
    /* Nút Sửa hồ sơ */
    .btn-edit-profile { 
        display: inline-block; background: rgba(255,255,255,0.2); color: white; 
        padding: 8px 25px; border-radius: 30px; text-decoration: none; 
        font-size: 14px; transition: 0.3s; margin-top: 20px; border: 1px solid rgba(255,255,255,0.4);
        position: relative; z-index: 2; font-weight: 500;
    }
    .btn-edit-profile:hover { background: white; color: #6f42c1; }

    .profile-body { padding: 50px 40px 40px 40px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    
    .info-item { 
        background: #f8f9fa; padding: 15px 20px; border-radius: 8px; 
        border-left: 4px solid #ddd; transition: transform 0.2s;
    }
    .info-item:hover { transform: translateY(-2px); }
    
    .info-label { font-size: 12px; color: #888; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; letter-spacing: 0.5px; }
    .info-value { font-size: 16px; color: #333; font-weight: 600; }
    
    .shift-box { border-color: #ffc107; background: #fffdf0; }
    
    /* Nút Quay lại (Chỉ dành cho Admin) */
    .btn-back { 
        display: inline-block; margin-bottom: 20px; 
        text-decoration: none; color: #555; font-weight: bold; 
        background: #e9ecef; padding: 8px 15px; border-radius: 5px;
        transition: 0.2s;
    }
    .btn-back:hover { background: #dee2e6; color: #333; }

    @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } .profile-body { padding: 30px 20px; } }
</style>

<div class="admin-wrapper" <?php if(!$is_admin) echo 'style="margin: 0 auto;"'; ?>>
    
    <?php if ($is_admin): ?>
        <a href="user_list.php" class="btn-back">← Quay lại danh sách</a>
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
                    <div class="info-value"><?php echo $user['phone'] ? $user['phone'] : '<span style="color:#999; font-style:italic;">Chưa cập nhật</span>'; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Mã bảo mật</div>
                    <div class="info-value" style="letter-spacing: 3px;">
                        ***<?php echo substr($user['security_code'], -2); ?>
                    </div>
                </div>

                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Địa chỉ</div>
                    <div class="info-value"><?php echo $user['address'] ? htmlspecialchars($user['address']) : '<span style="color:#999; font-style:italic;">Chưa cập nhật</span>'; ?></div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
// Đóng kết nối & Đóng div sidebar nếu có
if ($is_admin) echo '</div>'; 
?>