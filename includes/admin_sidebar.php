<?php
// Lấy tên file hiện tại để highlight menu đang chọn
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar-wrapper">
    <div class="sidebar-title">QUẢN LÝ</div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                📊 Tổng quan
            </a>
        </li>
        
        <li class="menu-header">CỬA HÀNG</li>
        <li>
            <a href="product_list.php" class="<?php echo ($current_page == 'product_list.php') ? 'active' : ''; ?>">
                ☕ Sản phẩm
            </a>
        </li>
        <li>
            <a href="order_list.php" class="<?php echo ($current_page == 'order_list.php') ? 'active' : ''; ?>">
                🧾 Hóa đơn
            </a>
        </li>
        <li>
            <a href="user_list.php" class="<?php echo ($current_page == 'user_list.php') ? 'active' : ''; ?>">
                👥 Nhân viên
            </a>
        </li>

        <li class="menu-header">BÁO CÁO</li>
        <li>
            <a href="shift_history.php" class="<?php echo ($current_page == 'shift_history.php') ? 'active' : ''; ?>">
                🕒 Lịch sử ca
            </a>
        </li>
        <li>
            <a href="stats.php" class="<?php echo ($current_page == 'stats.php') ? 'active' : ''; ?>">
                📈 Thống kê
            </a>
        </li>
    </ul>
</div>