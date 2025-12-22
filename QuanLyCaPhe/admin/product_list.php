<?php
// 1. BẢO VỆ TRANG
require '../includes/auth_admin.php'; 
require '../includes/header.php'; 
require '../includes/admin_sidebar.php'; 
echo '<div class="main-with-sidebar">';

// --- XỬ LÝ TÌM KIẾM & SẮP XẾP ---
$search = "";
$stock_filter = "all"; // Mặc định là xem tất cả
$sort_by = "id";
$order_dir = "DESC";

$sql = "SELECT * FROM products WHERE 1=1"; 

// 1. Lấy từ khóa tìm kiếm
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $s = mysqli_real_escape_string($conn, $search);
        $sql .= " AND (id LIKE '%$s%' OR name LIKE '%$s%')";
    }
}

// 2. LỌC THEO SỐ LƯỢNG (MỚI THÊM)
if (isset($_GET['stock_filter'])) {
    $stock_filter = $_GET['stock_filter'];
    switch ($stock_filter) {
        case 'out': // Hết hàng
            $sql .= " AND stock = 0";
            break;
        case 'low': // Sắp hết (1-5)
            $sql .= " AND stock > 0 AND stock <= 5";
            break;
        case 'high': // Còn nhiều (>5)
            $sql .= " AND stock > 5";
            break;
        default: // 'all' hoặc không hợp lệ -> Không làm gì
            break;
    }
}
// Hỗ trợ link cũ (view=low) chuyển sang logic mới
if (isset($_GET['view']) && $_GET['view'] == 'low') {
    $stock_filter = 'low';
    $sql .= " AND stock <= 5"; 
}

// 3. Sắp xếp
$allowed_sort = ['id', 'name', 'price', 'stock'];
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort)) $sort_by = $_GET['sort_by'];

if (isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC'])) $order_dir = strtoupper($_GET['order_dir']);

$sql .= " ORDER BY $sort_by $order_dir";
$result = mysqli_query($conn, $sql);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* CHỈ GIỮ LẠI CÁC STYLE ĐẶC TRƯNG CHO TRẠNG THÁI KHÓA SẢN PHẨM  */
    .row-locked { 
        background-color: #f9f9f9; 
    }
    .row-locked td { 
        color: #999; 
    }
    .row-locked img { 
        filter: grayscale(100%); 
        opacity: 0.7; 
    }
    
    .badge-locked {
        background: #6c757d; color: white; 
        font-size: 10px; padding: 2px 6px; 
        border-radius: 4px; font-weight: bold; margin-left: 5px;
        vertical-align: middle; text-transform: uppercase;
    }
</style>

<div class="admin-wrapper" style="margin: 0; max-width: none;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
        <h2 class="title-product" style="margin-bottom:0;">Quản lý sản phẩm</h2>
        <a href="product_add.php" class="btn-add">+ Thêm sản phẩm mới</a>
    </div>

    <div class="filter-card">
        <form method="GET" action="" class="filter-row">
            
            <div class="filter-group">
                <label>Tên / Mã SP</label>
                <input type="text" name="search" class="form-control" placeholder="Nhập từ khóa..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group">
                <label>Tồn kho</label>
                <select name="stock_filter" class="form-control">
                    <option value="all" <?php if($stock_filter == 'all') echo 'selected'; ?>>📦 Tất cả kho</option>
                    <option value="out" <?php if($stock_filter == 'out') echo 'selected'; ?>>🔥 Hết hàng (0)</option>
                    <option value="low" <?php if($stock_filter == 'low') echo 'selected'; ?>>⚠️ Sắp hết (1-5)</option>
                    <option value="high" <?php if($stock_filter == 'high') echo 'selected'; ?>>✅ Còn nhiều (>5)</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Sắp xếp theo</label>
                <select name="sort_by" class="form-control">
                    <option value="id" <?php if($sort_by == 'id') echo 'selected'; ?>>Mã sản phẩm</option>
                    <option value="name" <?php if($sort_by == 'name') echo 'selected'; ?>>Tên sản phẩm</option>
                    <option value="price" <?php if($sort_by == 'price') echo 'selected'; ?>>Giá bán</option>
                    <option value="stock" <?php if($sort_by == 'stock') echo 'selected'; ?>>Số lượng tồn</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Thứ tự</label>
                <select name="order_dir" class="form-control" style="min-width: 100px;">
                    <option value="DESC" <?php if($order_dir == 'DESC') echo 'selected'; ?>>Giảm dần</option>
                    <option value="ASC" <?php if($order_dir == 'ASC') echo 'selected'; ?>>Tăng dần</option>
                </select>
            </div>
            
            <div class="filter-group action-group" style="flex-direction: row; align-items: flex-end;">
                <button type="submit" class="btn-filter">🔍 Lọc</button>
                <?php if(!empty($search) || $sort_by != 'id' || $stock_filter != 'all'): ?>
                    <a href="product_list.php" class="btn-reset" title="Đặt lại">↺</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if($stock_filter != 'all'): ?>
        <div style="margin-bottom: 15px; font-style: italic; color: #555; padding-left: 5px;">
            Đang xem: 
            <strong>
                <?php 
                    if($stock_filter=='out') echo '<span style="color:red">Sản phẩm hết hàng</span>';
                    elseif($stock_filter=='low') echo '<span style="color:orange">Sản phẩm sắp hết</span>';
                    elseif($stock_filter=='high') echo '<span style="color:green">Sản phẩm còn nhiều</span>';
                ?>
            </strong>
        </div>
    <?php endif; ?>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th class="actions">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { 
                $is_locked = (isset($row['is_locked']) && $row['is_locked'] == 1);
            ?>
                <tr class="<?php echo $is_locked ? 'row-locked' : ''; ?>">
                    <td>#<?php echo $row['id']; ?></td>
                    <td>
                        <?php if (!empty($row['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Img" style="<?php echo $is_locked ? 'filter:grayscale(100%);' : ''; ?>">
                        <?php else: ?>
                            <span style="color:#ccc; font-size:12px;">No img</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        <?php if($is_locked) echo '<span class="badge-locked">TẠM NGƯNG</span>'; ?>
                    </td>
                    <td style="color: #28a745; font-weight: bold;">
                        <?php echo number_format($row['price']); ?> ₫
                    </td>
                    <td>
                        <?php 
                        if($row['stock'] > 5) echo $row['stock']; 
                        elseif($row['stock'] > 0) echo '<span style="color:orange; font-weight:bold;">'.$row['stock'].' (Sắp hết)</span>';
                        else echo '<span style="color:red; font-weight:bold;">Hết hàng</span>';
                        ?>
                    </td>
                    <td class="actions">
                    <div style="display: flex; gap: 5px; justify-content: flex-start;">
                        
                        <?php if ($is_locked): ?>
                            <a href="product_toggle.php?id=<?php echo $row['id']; ?>" class="btn-action btn-unlock" title="Mở bán lại">
                                🔓 Mở lại
                            </a>
                        <?php else: ?>
                            <a href="product_toggle.php?id=<?php echo $row['id']; ?>" class="btn-action btn-lock" title="Tạm ngưng món này">
                                ⛔ Tạm ngưng
                            </a>
                        <?php endif; ?>

                        <a href="product_edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">
                            Sửa
                        </a>
                        
                        <a href="product_delete.php?id=<?php echo $row['id']; ?>" 
                           onclick="confirmDelete(event, this.href, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')"
                           class="btn-action btn-delete">
                           Xóa
                        </a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; color: #777; background:white; border-radius:8px; margin-top:20px;">
            Không tìm thấy sản phẩm nào phù hợp với bộ lọc hiện tại.
        </div>
    <?php endif; ?>

</div> 

<script>
    function confirmDelete(event, deleteUrl, productName) {
        event.preventDefault(); 
        Swal.fire({
            title: 'Bạn muốn xóa sản phẩm?',
            text: "Sản phẩm: " + productName,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đúng, tôi muốn xóa',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'CẢNH BÁO LẦN CUỐI!',
                    text: "Hành động này sẽ xóa vĩnh viễn dữ liệu và không thể khôi phục. Bạn có CHẮC CHẮN 100% không?",
                    icon: 'error', 
                    showCancelButton: true,
                    confirmButtonColor: '#d33', 
                    cancelButtonColor: '#3085d6', 
                    confirmButtonText: 'XÓA NGAY LẬP TỨC',
                    cancelButtonText: 'Thôi, tôi suy nghĩ lại'
                }).then((result2) => {
                    if (result2.isConfirmed) {
                        window.location.href = deleteUrl; 
                    }
                });
            }
        });
    }
</script>

<?php
if ($result) mysqli_free_result($result);
disconnect_db();
echo '</div>'; 
?>