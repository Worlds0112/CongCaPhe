<?php
require '../includes/auth_admin.php';
require '../includes/header.php';
require '../includes/admin_sidebar.php'; 
echo '<div class="main-with-sidebar">';

// --- XỬ LÝ LỌC & TÌM KIẾM ---
$search = "";
$role_filter = "all";
$shift_filter = "all";
$sort_by = "role"; 
$order_dir = "ASC";

$sql = "SELECT * FROM users WHERE 1=1";

// 1. Tìm kiếm từ khóa
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $s = mysqli_real_escape_string($conn, $search);
        $sql .= " AND (username LIKE '%$s%' OR full_name LIKE '%$s%' OR id LIKE '%$s%')";
    }
}

// 2. Lọc theo Vai trò
if (isset($_GET['role']) && $_GET['role'] != 'all') {
    $role_filter = $_GET['role'];
    $r = mysqli_real_escape_string($conn, $role_filter);
    $sql .= " AND role = '$r'";
}

// 3. Lọc theo Ca làm việc
if (isset($_GET['shift']) && $_GET['shift'] != 'all') {
    $shift_filter = $_GET['shift'];
    $sh = mysqli_real_escape_string($conn, $shift_filter);
    $sql .= " AND shift = '$sh'";
}

// 4. Sắp xếp
$allowed_sort = ['id', 'username', 'full_name', 'role', 'shift'];
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort)) {
    $sort_by = $_GET['sort_by'];
}

if (isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC'])) {
    $order_dir = strtoupper($_GET['order_dir']);
}

$sql .= " ORDER BY $sort_by $order_dir, username ASC";
$result = mysqli_query($conn, $sql);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* * CHỈ GIỮ LẠI CÁC STYLE GHI ĐÈ MÀU TÍM ĐẶC TRƯNG CỦA TRANG USER * */
    h2 { border-left-color: #6f42c1; }

    /* Nút thêm mới */
    .btn-add { background-color: #6f42c1; }
    .btn-add:hover { background-color: #59359a; }

    /* Màu focus cho input/select (Ghi đè biến --filter-focus-color trong .form-control) */
    .form-control:focus {
        border-color: #6f42c1; 
        box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
    }
    
    /* Nút Lọc */
    .btn-filter { background: #6f42c1; } 
    .btn-filter:hover { background: #59359a; } 
</style>

<div class="admin-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="title-user" style="margin-bottom:0">Quản lý Tài khoản</h2>
        <a href="user_add.php" class="btn-add">+ Thêm nhân viên mới</a>
    </div>

    <div class="filter-card">
        <form method="GET" action="" class="filter-row">
            
            <div class="filter-group">
                <label>Tên / Username</label>
                <input type="text" name="search" class="form-control" placeholder="Nhập từ khóa..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group">
                <label>Vai trò</label>
                <select name="role" class="form-control">
                    <option value="all" <?php if ($role_filter == 'all') echo 'selected'; ?>>Tất cả</option>
                    <option value="admin" <?php if ($role_filter == 'admin') echo 'selected'; ?>>Quản trị (Admin)</option>
                    <option value="staff" <?php if ($role_filter == 'staff') echo 'selected'; ?>>Nhân viên</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Ca làm việc</label>
                <select name="shift" class="form-control">
                    <option value="all" <?php if ($shift_filter == 'all') echo 'selected'; ?>>Tất cả ca</option>
                    <option value="sang" <?php if ($shift_filter == 'sang') echo 'selected'; ?>>Ca Sáng</option>
                    <option value="chieu" <?php if ($shift_filter == 'chieu') echo 'selected'; ?>>Ca Chiều</option>
                    <option value="toi" <?php if ($shift_filter == 'toi') echo 'selected'; ?>>Ca Tối</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Sắp xếp theo</label>
                <select name="sort_by" class="form-control">
                    <option value="role" <?php if ($sort_by == 'role') echo 'selected'; ?>>Vai trò</option>
                    <option value="full_name" <?php if ($sort_by == 'full_name') echo 'selected'; ?>>Tên nhân viên</option>
                    <option value="username" <?php if ($sort_by == 'username') echo 'selected'; ?>>Username</option>
                </select>
            </div>

            <div class="filter-group action-group" style="flex-direction: row; align-items: flex-end;">
                 <select name="order_dir" class="form-control" style="min-width: 100px; margin-right: 5px;">
                    <option value="ASC" <?php if ($order_dir == 'ASC') echo 'selected'; ?>>A-Z</option>
                    <option value="DESC" <?php if ($order_dir == 'DESC') echo 'selected'; ?>>Z-A</option>
                </select>

                <button type="submit" class="btn-filter">🔍 Lọc</button>
                <?php if (!empty($search) || $role_filter != 'all' || $shift_filter != 'all' || $sort_by != 'role'): ?>
                    <a href="user_list.php" class="btn-reset" title="Đặt lại">↺</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($role_filter != 'all' || $shift_filter != 'all'): ?>
        <div style="margin-bottom: 15px; font-style: italic; color: #555; padding-left: 5px;">
            Đang lọc: 
            <?php 
                if($role_filter == 'admin') echo '<span style="color:#6f42c1; font-weight:bold">Quản trị viên</span> ';
                elseif($role_filter == 'staff') echo '<span style="color:#0f5132; font-weight:bold">Nhân viên</span> ';
                
                if($shift_filter != 'all') echo " - Ca " . ucfirst($shift_filter);
            ?>
        </div>
    <?php endif; ?>

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
                            <a href="user_view.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view">
                                👤 Xem
                            </a>

                            <?php
                            if ($row['id'] == $_SESSION['user_id']) {
                                echo '<a href="#" class="btn-action btn-disabled" title="Không thể tự xóa mình">🔒 Xóa</a>';
                            } else {
                                ?>
                                <a href="user_delete.php?id=<?php echo $row['id']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="confirmDeleteUser(event, this.href, '<?php echo htmlspecialchars(addslashes($row['username'])); ?>')">
                                   🗑 Xóa
                                </a>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; color:#999; margin-top: 30px; background: white; padding: 20px; border-radius: 8px;">
            Không tìm thấy nhân viên nào phù hợp với bộ lọc.
        </p>
    <?php endif; ?>
</div>

<script>
    function confirmDeleteUser(event, deleteUrl, username) {
        event.preventDefault(); // Chặn chuyển trang

        // BƯỚC 1: HỎI XÁC NHẬN
        Swal.fire({
            title: 'Xóa tài khoản ' + username + '?',
            text: "Bạn có chắc muốn xóa nhân viên này không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đúng, tôi muốn xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                // BƯỚC 2: CẢNH BÁO MẠNH (CRITICAL)
                Swal.fire({
                    title: 'XÁC NHẬN CUỐI CÙNG!',
                    text: "Tài khoản này sẽ bị xóa vĩnh viễn và không thể khôi phục. Người này sẽ mất quyền truy cập hệ thống ngay lập tức.",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Màu đỏ cảnh báo
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'XÓA VĨNH VIỄN',
                    cancelButtonText: 'Thôi, giữ lại'
                }).then((result2) => {
                    if (result2.isConfirmed) {
                        window.location.href = deleteUrl; // Chuyển hướng để xóa thật
                    }
                });
            }
        });
    }
</script>

<?php
echo '</div>'; // Đóng main-with-sidebar
require '../includes/footer.php'; 
?>