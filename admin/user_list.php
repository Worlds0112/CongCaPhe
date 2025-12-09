<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// --- XỬ LÝ TÌM KIẾM & SẮP XẾP ---
$search = "";
$sort_by = "role"; // Mặc định xếp Admin lên trước
$order_dir = "ASC";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$allowed_sort = ['id', 'username', 'full_name', 'role', 'shift'];
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort)) {
    $sort_by = $_GET['sort_by'];
}

if (isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC'])) {
    $order_dir = strtoupper($_GET['order_dir']);
}

// Query
$sql = "SELECT * FROM users WHERE 1=1";

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (username LIKE '%$s%' OR full_name LIKE '%$s%' OR id LIKE '%$s%')";
}

// Nếu sort theo role thì thêm username để phụ
$sql .= " ORDER BY $sort_by $order_dir, username ASC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* CSS CŨ GIỮ NGUYÊN */
    .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #6f42c1; padding-left: 15px; } 
    .btn-add { display: inline-block; background-color: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; }
    .btn-add:hover { background-color: #59359a; }
    table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden; margin-top: 20px; }
    th, td { border-bottom: 1px solid #eee; padding: 15px 20px; text-align: left; vertical-align: middle; }
    th { background-color: #f8f9fa; font-weight: 700; color: #555; text-transform: uppercase; font-size: 13px; }
    tr:hover { background-color: #f1f3f5; }
    .role-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; }
    .role-admin { background-color: #dc3545; }
    .role-staff { background-color: #28a745; } 
    .btn-delete { text-decoration: none; padding: 6px 12px; border-radius: 4px; color: white; font-size: 13px; font-weight: 500; background-color: #dc3545; }
    .btn-delete:hover { background-color: #c82333; }
    .btn-disabled { background-color: #ccc; cursor: not-allowed; pointer-events: none; }

    /* CSS THANH TÌM KIẾM (MỚI) */
    .filter-bar { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .filter-input, .filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; outline: none; }
    .filter-input:focus, .filter-select:focus { border-color: #6f42c1; }
    .btn-search { background-color: #333; color: white; border: none; padding: 9px 15px; border-radius: 4px; cursor: pointer; font-weight: 500; }
    .btn-reset { color: #dc3545; text-decoration: none; font-weight: 500; font-size: 14px; margin-left: 5px; }
</style>

<div class="admin-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin-bottom:0">Quản lý Tài khoản</h2>
        <a href="user_add.php" class="btn-add">+ Thêm nhân viên mới</a>
    </div>

    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="search" class="filter-input" placeholder="Tìm Username hoặc Tên..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="sort_by" class="filter-select">
                <option value="role" <?php if($sort_by == 'role') echo 'selected'; ?>>Theo Vai trò</option>
                <option value="full_name" <?php if($sort_by == 'full_name') echo 'selected'; ?>>Theo Tên</option>
                <option value="username" <?php if($sort_by == 'username') echo 'selected'; ?>>Theo Username</option>
                <option value="shift" <?php if($sort_by == 'shift') echo 'selected'; ?>>Theo Ca</option>
            </select>

            <select name="order_dir" class="filter-select">
                <option value="ASC" <?php if($order_dir == 'ASC') echo 'selected'; ?>>A-Z (Tăng dần)</option>
                <option value="DESC" <?php if($order_dir == 'DESC') echo 'selected'; ?>>Z-A (Giảm dần)</option>
            </select>

            <button type="submit" class="btn-search">Lọc</button>
            <?php if(!empty($search) || $sort_by != 'role'): ?>
                <a href="user_list.php" class="btn-reset">Đặt lại</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ và tên</th>
                <th>Vai trò</th>
                <th>Ca làm việc</th> 
                <th>Mã bảo mật</th>
                <th style="text-align: center;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td>
                    <?php if ($row['role'] == 'admin'): ?>
                        <span class="role-badge role-admin">Quản trị</span>
                    <?php else: ?>
                        <span class="role-badge role-staff">Nhân viên</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    switch ($row['shift']) {
                        case 'sang': echo '<span style="color:green; font-weight:bold;">Ca Sáng</span>'; break;
                        case 'chieu': echo '<span style="color:orange; font-weight:bold;">Ca Chiều</span>'; break;
                        case 'toi': echo '<span style="color:purple; font-weight:bold;">Ca Tối</span>'; break;
                        default: echo 'Full time';
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['security_code']); ?></td>
                <td style="text-align: center;">
                    <a href="user_view.php?id=<?php echo $row['id']; ?>" 
                    style="background: #17a2b8; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 500; margin-right: 5px;">
                    Xem
                    </a>

                    <?php 
                    if ($row['id'] == $_SESSION['user_id']) {
                        echo '<a href="#" class="btn-delete btn-disabled">Xóa</a>';
                    } else {
                        echo '<a href="user_delete.php?id=' . $row['id'] . '" 
                                 class="btn-delete"
                                 onclick="return confirm(\'Xóa tài khoản: ' . htmlspecialchars($row['username']) . '?\');">Xóa</a>';
                    }
                    ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#999; margin-top: 30px; background: white; padding: 20px; border-radius: 8px;">Không tìm thấy nhân viên nào.</p>
    <?php endif; ?>
</div>

<?php
require '../includes/footer.php'; 
?>