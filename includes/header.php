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
    <title>Cộng Cà Phê</title> <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
        }

        header {
            background: white; /* Nền trắng */
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem; 
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header-left .logo {
            font-size: 1.8rem;
            font-weight: bold;
            letter-spacing: 1px;
            color: #5B743A;
            text-decoration: none;
        }

        .header-left nav ul {
            list-style: none;
            display: flex;
            gap: 1.5rem;
        }

        .header-left nav ul li a {
            color: #333; /* Chữ màu đen trên nền trắng */
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .header-left nav ul li a:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem; /* Tăng khoảng cách */
            font-size: 0.9rem;
            color: #333; /* Chữ màu đen */
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #5B743A;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .logout-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-link:hover {
            text-decoration: underline;
        }

        /* Class content (để đẩy nội dung xuống) */
        .content {
            margin-top: 100px;  /* Đẩy nội dung xuống dưới header cố định */
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Class welcome (cho trang chủ) */
        .welcome {
            background: white;
            padding: 4rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            color: #333;
        }
        .welcome h1 {
            margin-bottom: 1rem;
            color: #5B743A;
            font-size: 2.5rem;
        }

    </style>
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <a href="/QuanLyCaphe/index.php" class="logo">Cộng</a> 
            
            <?php // Chỉ hiển thị nav nếu đã đăng nhập
            if (isset($_SESSION['user_id'])): ?>
            <nav>
                <ul>
                    <li><a href="/QuanLyCaphe/index.php">Trang chủ</a></li>
                    
                    <?php // Chỉ Admin thấy link quản lý
                    if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="/QuanLyCaPhe/admin/product_list.php">Quản lý Sản phẩm</a></li>
                        <li><a href="/QuanLyCaPhe/admin/order_list.php">Quản lý Hóa đơn</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/QuanLyCaphe/pos/pos.php">Thực đơn (Bán hàng)</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        
        <?php // Kiểm tra xem đã đăng nhập chưa
        if (isset($_SESSION['user_id'])): 
            // Lấy chữ cái đầu của username
            $avatar_char = strtoupper(substr($_SESSION['username'], 0, 1));
        ?>
            <div class="user-info">
                <span>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="avatar"><?php echo $avatar_char; ?></div>
                <a href="/QuanLyCaphe/logout.php" class="logout-link">Đăng xuất</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                <a href="/QuanLyCaphe/login.php" class="logout-link" style="color: #007bff;">Đăng nhập</a>
            </div>
        <?php endif; ?>

    </div>
</header>

<main class="content"></main>