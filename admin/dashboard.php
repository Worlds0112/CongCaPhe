<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; // Thêm Sidebar vào Dashboard cho đồng bộ

echo '<div class="main-with-sidebar">'; // Đẩy nội dung sang phải để không bị Sidebar che
?>

<style>
    .dashboard-wrapper {
        max-width: 1100px; /* Tăng chiều rộng lên xíu cho thoải mái */
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .dashboard-title {
        text-align: left; /* Căn trái cho giống các trang khác */
        color: #333;
        font-size: 24px;
        margin-bottom: 30px;
        border-left: 5px solid #5B743A; /* Thêm gạch màu chủ đạo */
        padding-left: 15px;
        font-weight: bold;
        text-transform: uppercase;
    }

    /* Grid layout */
    .admin-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
    }

    .admin-card {
        background: white;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        text-decoration: none;
        color: #333;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        border-bottom: 4px solid transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 160px;
    }

    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .card-icon { font-size: 40px; margin-bottom: 15px; }
    .card-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }

    /* --- MÀU SẮC RIÊNG CHO TỪNG THẺ --- */
    
    /* 1. Sản phẩm (Xanh lá) */
    .card-product { border-bottom-color: #28a745; }
    .card-product .card-icon { color: #28a745; }

    /* 2. Kho hàng (MỚI - Xanh Teal & Tím) */
    .card-import { border-bottom-color: #20c997; }
    .card-import .card-icon { color: #20c997; }

    .card-inventory { border-bottom-color: #6610f2; }
    .card-inventory .card-icon { color: #6610f2; }

    /* 3. Hóa đơn (Xanh dương) */
    .card-order { border-bottom-color: #17a2b8; }
    .card-order .card-icon { color: #17a2b8; }

    /* 4. Nhân viên (Tím than) */
    .card-user { border-bottom-color: #343a40; }
    .card-user .card-icon { color: #343a40; }

    /* 5. Báo cáo (Vàng & Đỏ) */
    .card-history { border-bottom-color: #ffc107; }
    .card-history .card-icon { color: #ffc107; }

    .card-stats { border-bottom-color: #dc3545; }
    .card-stats .card-icon { color: #dc3545; }

</style>

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