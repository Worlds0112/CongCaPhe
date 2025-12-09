<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
?>

<style>
    .dashboard-wrapper {
        max-width: 1000px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .dashboard-title {
        text-align: center;
        color: #5B743A;
        font-size: 28px;
        margin-bottom: 40px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Grid layout cho các nút */
    .admin-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 30px;
    }

    .admin-card {
        background: white;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        text-decoration: none;
        color: #333;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        border-bottom: 5px solid transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 180px;
    }

    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .card-icon {
        font-size: 40px;
        margin-bottom: 15px;
    }

    .card-title {
        font-size: 18px;
        font-weight: bold;
    }

    /* Màu sắc riêng cho từng thẻ */
    .card-product { border-bottom-color: #28a745; }
    .card-product .card-icon { color: #28a745; }

    .card-order { border-bottom-color: #17a2b8; }
    .card-order .card-icon { color: #17a2b8; }

    .card-user { border-bottom-color: #6f42c1; }
    .card-user .card-icon { color: #6f42c1; }

    .card-history { border-bottom-color: #ffc107; }
    .card-history .card-icon { color: #ffc107; }

    .card-stats { border-bottom-color: #dc3545; }
    .card-stats .card-icon { color: #dc3545; }
</style>

<div class="dashboard-wrapper">
    <h2 class="dashboard-title">Trung Tâm Quản Lý</h2>

    <div class="admin-menu-grid">
        
        <a href="product_list.php" class="admin-card card-product">
            <div class="card-icon">☕</div>
            <div class="card-title">Sản Phẩm</div>
        </a>

        <a href="order_list.php" class="admin-card card-order">
            <div class="card-icon">🧾</div>
            <div class="card-title">Hóa Đơn</div>
        </a>

        <a href="user_list.php" class="admin-card card-user">
            <div class="card-icon">👥</div>
            <div class="card-title">Nhân Viên</div>
        </a>

        <a href="shift_history.php" class="admin-card card-history">
            <div class="card-icon">🕒</div>
            <div class="card-title">Lịch Sử Ca</div>
        </a>

        <a href="stats.php" class="admin-card card-stats">
            <div class="card-icon">📊</div>
            <div class="card-title">Báo Cáo & Thống Kê</div>
        </a>

    </div>
</div>

<?php require '../includes/footer.php'; ?>