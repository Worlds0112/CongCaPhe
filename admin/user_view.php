<?php
// --- THAY ĐỔI CƠ CHẾ BẢO VỆ ---
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Kết nối CSDL thủ công
require '../includes/connect.php';
connect_db();
// ------------------------------

require '../includes/header.php'; 

// Lấy ID từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- BẢO MẬT ---
if ($_SESSION['role'] != 'admin' && $_SESSION['user_id'] != $id) {
    echo "<div class='admin-wrapper'><h3 style='color:red'>Bạn không có quyền xem hồ sơ người khác!</h3></div>";
    require '../includes/footer.php';
    exit();
}
// ----------------

$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "<div class='admin-wrapper'><h3>Không tìm thấy nhân viên.</h3></div>";
    require '../includes/footer.php';
    exit();
}

// --- XỬ LÝ HIỂN THỊ ẢNH AVATAR (LOGIC MỚI) ---
$avatar_name = !empty($user['avatar']) ? $user['avatar'] : 'default_user.png';
$avatar_path = "uploads/" . $avatar_name;

// Kiểm tra xem file ảnh có thực sự tồn tại trong thư mục uploads không
// __DIR__ là đường dẫn đến thư mục chứa file hiện tại (admin)
if (!file_exists(__DIR__ . '/' . $avatar_path)) {
    $avatar_path = "uploads/default_user.png"; // Nếu không thấy file thì lấy ảnh mặc định
}
// ---------------------------------------------

// Tính tuổi
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
    .admin-wrapper { max-width: 900px; margin: 0 auto; padding: 30px 20px; }
    .profile-card {
        background: white; border-radius: 12px; overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        display: flex; flex-direction: column;
    }
    .profile-header {
        background: linear-gradient(135deg, #6f42c1, #8e44ad);
        padding: 30px; color: white; text-align: center;
    }
    
    /* Chỉnh lại avatar cho đẹp */
    .avatar-container {
        width: 130px; 
        height: 130px; 
        margin: 0 auto 15px auto;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.4);
        overflow: hidden; /* Cắt ảnh thừa ra ngoài hình tròn */
        background: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .avatar-container img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Đảm bảo ảnh không bị méo */
    }

    .profile-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
    .profile-role { opacity: 0.9; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }

    .profile-body { padding: 30px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-item { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ddd; }
    .info-label { font-size: 12px; color: #777; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
    .info-value { font-size: 16px; color: #333; font-weight: 500; }
    
    .shift-box { border-color: #ffc107; background: #fffdf0; }
    .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #555; font-weight: bold; }
    
    /* Nút sửa (ẩn hiện tùy ý) */
    .btn-edit-profile {
        display: inline-block; background: rgba(255,255,255,0.2); color: white; 
        padding: 6px 16px; border-radius: 20px; text-decoration: none; font-size: 13px;
        transition: 0.3s; margin-bottom: 15px;
    }
    .btn-edit-profile:hover { background: rgba(255,255,255,0.4); }
</style>

<div class="admin-wrapper">
    <a href="user_list.php" class="btn-back">← Quay lại danh sách</a>

    <div class="profile-card">
        <div class="profile-header">
            
            <div class="avatar-container">
                <img src="<?php echo $avatar_path; ?>" alt="Avatar">
            </div>

            <div style="text-align: center;">
                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn-edit-profile">
                ✏️ Sửa hồ sơ
                </a>
            </div>

            <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="profile-role">
                <?php echo ($user['role'] == 'admin') ? 'Quản Trị Viên' : 'Nhân Viên Bán Hàng'; ?>
            </div>
        </div>

        <div class="profile-body">
            <div class="info-grid">
                
                <div class="info-item">
                    <div class="info-label">Tên đăng nhập</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>

                <div class="info-item shift-box">
                    <div class="info-label">Ca làm việc hiện tại</div>
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
                    <div class="info-value"><?php echo $user['phone'] ? $user['phone'] : 'Chưa cập nhật'; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Mã bảo mật</div>
                    <div class="info-value" style="letter-spacing: 2px;">***<?php echo substr($user['security_code'], -2); ?></div>
                </div>

                <div class="info-item" style="grid-column: span 2;">
                    <div class="info-label">Địa chỉ</div>
                    <div class="info-value"><?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Chưa cập nhật'; ?></div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>