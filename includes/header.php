<?php
// 1. KHỞI TẠO SESSION
if (!session_id()) session_start();

// 2. LOGIC XÁC ĐỊNH TRANG ĐANG ĐỨNG (ACTIVE MENU)
$current_page = basename($_SERVER['PHP_SELF']); 

// Hàm kiểm tra: Nếu tên trang trùng khớp thì trả về chữ 'active'
function isActive($page_name, $current_page) {
    // Xử lý trường hợp 1 mục menu ứng với nhiều file (VD: Sản phẩm -> Thêm, Sửa, Xóa)
    if (is_array($page_name)) {
        return in_array($current_page, $page_name) ? 'active' : '';
    }
    return ($current_page == $page_name) ? 'active' : '';
}

// 3. XỬ LÝ USER (AVATAR)
$has_image = false; $avatar_url = ""; $first_char = "U"; $profile_link = "#";

if (isset($_SESSION['user_id'])) {
    if (!isset($conn)) {
        $conn = mysqli_connect("localhost", "root", "", "db_quanlycafe");
        if (!$conn && file_exists(__DIR__ . '/connect.php')) {
            require_once 'connect.php'; 
            if (function_exists('connect_db')) $conn = connect_db();
        }
    }
    
    if ($conn) {
        $uid = intval($_SESSION['user_id']);
        $q_avt = mysqli_query($conn, "SELECT avatar FROM users WHERE id = $uid");
        if ($q_avt && mysqli_num_rows($q_avt) > 0) {
            $r_avt = mysqli_fetch_assoc($q_avt);
            if (!empty($r_avt['avatar']) && $r_avt['avatar'] != 'default_user.png') {
                $has_image = true;
                $avatar_url = "/QuanLyCaPhe/admin/uploads/" . $r_avt['avatar'];
            }
        }
    }
    
    if (isset($_SESSION['username'])) {
        $first_char = strtoupper(substr($_SESSION['username'], 0, 1));
    }
    $profile_link = "/QuanLyCaPhe/admin/user_view.php?id=" . $uid;
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
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <nav>
                <ul>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <?php 
                        // Danh sách tất cả các file thuộc khu vực quản lý
                        $admin_pages = [
                            'dashboard.php', 
                            'product_list.php', 'product_add.php', 'product_edit.php',
                            'order_list.php', 'order_details.php',
                            'user_list.php', 'user_add.php', 'user_edit.php', 'user_view.php',
                            'shift_history.php', 'stats.php'
                        ];
                        ?>
                        <li>
                            <a href="/QuanLyCaPhe/admin/dashboard.php" class="<?php echo isActive($admin_pages, $current_page); ?>">
                                Trang quản lý
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="/QuanLyCaPhe/pos/pos.php" class="<?php echo isActive('pos.php', $current_page); ?>">
                            Thực đơn
                        </a>
                    </li>
                    
                    <li>
                        <a href="/QuanLyCaPhe/pos/shift_report.php" class="<?php echo isActive('shift_report.php', $current_page); ?>">
                            Báo cáo ca
                        </a>
                    </li>
                    
                    <li>
                        <a href="/QuanLyCaPhe/pos/my_orders.php" class="<?php echo isActive('my_orders.php', $current_page); ?>">
                            Đơn của tôi
                        </a>
                    </li>
                    
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
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
                <a href="/QuanLyCaPhe/login.php" class="logout-link" style="color: #007bff; border-color: #007bff;">Đăng nhập</a>
            </div>
        <?php endif; ?>

    </div>
</header>

<main class="content">