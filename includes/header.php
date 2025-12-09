<?php
// Bắt đầu session ở đây, mọi trang gọi file này sẽ tự động có session
if (!session_id()) { // Chỉ start nếu session chưa được bắt đầu
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cộng Cà Phê</title> 
    <link rel="stylesheet" href="/QuanLyCaPhe/css/admin_style.css">
    <link rel="stylesheet" href="/QuanLyCaPhe/css/header_style.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <a href="/QuanLyCaPhe/index.php" class="logo">Cộng</a> 
            
            <?php // Chỉ hiển thị nav nếu đã đăng nhập
            if (isset($_SESSION['user_id'])): ?>
            <nav>
                <ul>
                    
                    <?php // Chỉ Admin thấy link quản lý
                    if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="/QuanLyCaPhe/admin/product_list.php">Sản phẩm</a></li>
                        <li><a href="/QuanLyCaPhe/admin/order_list.php">Hóa đơn</a></li>
                        <li><a href="/QuanLyCaPhe/admin/user_list.php">Nhân viên</a></li>
                        
                        <li><a href="/QuanLyCaPhe/admin/shift_history.php">Lịch sử ca</a></li>
                        <li><a href="/QuanLyCaPhe/admin/stats.php">Thống kê</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/QuanLyCaPhe/pos/pos.php">Thực đơn</a></li>
                    <li><a href="/QuanLyCaPhe/pos/shift_report.php">Báo cáo ca</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        
        <?php 
        if (isset($_SESSION['user_id'])): 
            // KẾT NỐI DB ĐỂ LẤY ẢNH
            if (!isset($conn)) {
                $conn = mysqli_connect("localhost", "root", "", "db_quanlycafe");
                // Nếu kết nối lỗi thì thử include file chuẩn
                if (!$conn && file_exists(__DIR__ . '/connect.php')) {
                    require_once 'connect.php';
                    connect_db();
                }
            }
            
            $uid = $_SESSION['user_id'];
            $q_avt = mysqli_query($conn, "SELECT avatar FROM users WHERE id = $uid");
            $r_avt = mysqli_fetch_assoc($q_avt);
            
            $has_image = false;
            $avatar_url = "";
            
            // Kiểm tra ảnh
            if ($r_avt && !empty($r_avt['avatar']) && $r_avt['avatar'] != 'default_user.png') {
                $has_image = true;
                // QUAN TRỌNG: Thêm /QuanLyCaPhe/ vào trước đường dẫn ảnh
                $avatar_url = "/QuanLyCaPhe/admin/uploads/" . $r_avt['avatar'];
            }
            
            $first_char = strtoupper(substr($_SESSION['username'], 0, 1));
            // Link vào trang cá nhân
            $profile_link = "/QuanLyCaPhe/admin/user_view.php?id=" . $uid;
        ?>
            <div class="user-info">
                <a href="<?php echo $profile_link; ?>" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:#333;">
                    <span>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    
                    <?php if ($has_image): ?>
                        <img src="<?php echo $avatar_url; ?>" class="avatar-img" alt="Avt">
                    <?php else: ?>
                        <div class="avatar-letter"><?php echo $first_char; ?></div>
                    <?php endif; ?>
                </a>
                
                <a href="/QuanLyCaPhe/logout.php" class="logout-link">Đăng xuất</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                <a href="/QuanLyCaPhe/login.php" class="logout-link" style="color: #007bff;">Đăng nhập</a>
            </div>
        <?php endif; ?>

    </div>
</header>

<main class="content">