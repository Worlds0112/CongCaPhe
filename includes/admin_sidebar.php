<?php
// Lấy tên file hiện tại để highlight menu đang chọn
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS cho Sidebar */
    .admin-sidebar {
        width: 250px;
        background: #343a40; /* Màu tối sang trọng */
        min-height: calc(100vh - 65px); /* Full chiều cao trừ header */
        color: white;
        padding-top: 20px;
        flex-shrink: 0;
    }
    
    .admin-sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .admin-sidebar li a {
        display: block;
        padding: 15px 25px;
        color: #c2c7d0;
        text-decoration: none;
        border-bottom: 1px solid #4b545c;
        transition: 0.3s;
        font-size: 15px;
    }

    .admin-sidebar li a:hover {
        background-color: #494e53;
        color: white;
        padding-left: 30px; /* Hiệu ứng đẩy chữ */
    }

    /* Class active cho mục đang chọn */
    .admin-sidebar li a.active {
        background-color: #28a745; /* Màu xanh lá chủ đạo */
        color: white;
        font-weight: bold;
        border-left: 5px solid #1e7e34;
    }
    
    .sidebar-heading {
        padding: 10px 25px;
        font-size: 12px;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: bold;
        margin-top: 10px;
    }
</style>

<div class="admin-sidebar">
    <ul>
        <div class="sidebar-heading">Quản lý cửa hàng</div>
        
        <li>
            <a href="product_list.php" class="<?php echo ($current_page == 'product_list.php') ? 'active' : ''; ?>">
                📦 Quản lý Sản phẩm
            </a>
        </li>
        
        <li>
            <a href="order_list.php" class="<?php echo ($current_page == 'order_list.php') ? 'active' : ''; ?>">
                🧾 Quản lý Hóa đơn
            </a>
        </li>
        
        <li>
            <a href="user_list.php" class="<?php echo ($current_page == 'user_list.php') ? 'active' : ''; ?>">
                👥 Quản lý Nhân viên
            </a>
        </li>

        <div class="sidebar-heading">Thống kê</div>
        <li>
            <a href="thong_ke.php" class="<?php echo ($current_page == 'thong_ke.php') ? 'active' : ''; ?>">
                📊 Báo cáo doanh thu
            </a>
        </li>
    </ul>
</div>