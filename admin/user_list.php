<?php
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 

// Lấy danh sách user
$sql = "SELECT * FROM users ORDER BY role ASC, username ASC";
$result = mysqli_query($conn, $sql);
?>

<style>
    .admin-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    h2 { color: #333; margin-bottom: 1.5rem; border-left: 5px solid #6f42c1; padding-left: 15px; } /* Màu tím cho User */
    
    .btn-add {
        display: inline-block; background-color: #6f42c1; color: white;
        padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-bottom: 20px;
    }
    .btn-add:hover { background-color: #59359a; }

    table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden; }
    th, td { border-bottom: 1px solid #eee; padding: 15px 20px; text-align: left; vertical-align: middle; }
    th { background-color: #f8f9fa; font-weight: 700; color: #555; text-transform: uppercase; font-size: 13px; }
    tr:hover { background-color: #f1f3f5; }

    .role-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; }
    .role-admin { background-color: #dc3545; } /* Đỏ */
    .role-staff { background-color: #28a745; } /* Xanh */

    .btn-delete { text-decoration: none; padding: 6px 12px; border-radius: 4px; color: white; font-size: 13px; font-weight: 500; background-color: #dc3545; }
    .btn-delete:hover { background-color: #c82333; }
    .btn-disabled { background-color: #ccc; cursor: not-allowed; pointer-events: none; }
</style>

<div class="admin-wrapper">
    <h2>Quản lý Tài khoản</h2>
    <p><a href="user_add.php" class="btn-add">+ Thêm nhân viên mới</a></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ và tên</th>
                <th>Vai trò</th>
                <th>Mã bảo mật</th> <th style="text-align: center;">Hành động</th>
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
                <td><?php echo htmlspecialchars($row['security_code']); ?></td>
                <td style="text-align: center;">
                    <?php 
                    // Không cho phép xóa chính mình
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
</div>

<?php
require '../includes/footer.php'; 
?>