<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 

// Đẩy nội dung sang phải để không bị Sidebar che
echo '<div class="main-with-sidebar">';
?>

<div class="dashboard-wrapper">
    
    
    <h2 class="dashboard-title">Tổng Quan Quản Lý</h2>

    <div class="admin-menu-grid">
        
        <a href="product_list.php" class="admin-card card-product">
            <div class="card-icon">☕</div>
            <div class="card-title">Quản lý Sản Phẩm</div>
        </a>

        <a href="inventory_import.php" class="admin-card card-import">
            <div class="card-icon">📥</div>
            <div class="card-title">Nhập Hàng Nhanh</div>
        </a>

        <a href="inventory_history.php" class="admin-card card-inventory">
            <div class="card-icon">📦</div>
            <div class="card-title">Lịch Sử Nhập/Xuất</div>
        </a>

        <a href="order_list.php" class="admin-card card-order">
            <div class="card-icon">🧾</div>
            <div class="card-title">Danh sách Hóa Đơn</div>
        </a>

        <a href="user_list.php" class="admin-card card-user">
            <div class="card-icon">👥</div>
            <div class="card-title">Quản lý Nhân Viên</div>
        </a>

        <a href="shift_history.php" class="admin-card card-history">
            <div class="card-icon">🕒</div>
            <div class="card-title">Lịch Sử Giao Ca</div>
        </a>

        <a href="stats.php" class="admin-card card-stats">
            <div class="card-icon">📊</div>
            <div class="card-title">Báo Cáo Thống Kê</div>
        </a>

    </div>
</div>

<?php 
echo '</div>'; // Đóng div.main-with-sidebar
?>