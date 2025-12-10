<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';
require '../includes/auto_shift_check.php';

// --- 1. LẤY THÔNG TIN NHÂN VIÊN ---
$uid = $_SESSION['user_id'];
$role = $_SESSION['role']; // Lấy vai trò (admin/staff)

$q_user = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$r_user = mysqli_fetch_assoc($q_user);
$my_shift = $r_user['shift']; // Ví dụ: 'sang', 'chieu', 'toi', 'full'

// --- 2. LOGIC KIỂM TRA QUYỀN BÁN HÀNG ---
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_hour = date('H');
$can_sell = is_working_hour($my_shift); // Kiểm tra theo ca phân công
$lock_reason = "Ngoài ca làm việc!";

// LUẬT ĐẶC BIỆT: GIỜ GIỚI NGHIÊM (23h00 - 06h00 sáng hôm sau)
if ($current_hour >= 23 || $current_hour < 6) {
    if ($role == 'admin' || $my_shift == 'full') {
        $can_sell = true; 
    } else {
        $can_sell = false;
        $lock_reason = "Đã đóng cửa (23h-06h)";
    }
}

// --- 3. LẤY DANH SÁCH SẢN PHẨM (CÓ is_locked) ---
$sql = "SELECT p.*, c.name as category_name, c.id as category_id 
        FROM products p 
        JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0 
        ORDER BY c.id ASC, p.name ASC";
$result = mysqli_query($conn, $sql);

$menu_data = [];
$categories_list = []; 

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_data[$row['category_id']]['name'] = $row['category_name'];
        $menu_data[$row['category_id']]['products'][] = $row;
        if (!isset($categories_list[$row['category_id']])) {
            $categories_list[$row['category_id']] = $row['category_name'];
        }
    }
}
?>

<link rel="stylesheet" href="/QuanLyCaPhe/css/pos_style.css">

<style>
    /* CSS CHO MÓN BỊ KHÓA */
    .product-card.locked-item {
        opacity: 0.6;
        background-color: #f0f0f0;
        cursor: not-allowed;
        position: relative;
    }
    .locked-overlay {
        position: absolute; top: 10px; right: 10px;
        background: #dc3545; color: white;
        font-size: 10px; font-weight: bold;
        padding: 3px 8px; border-radius: 4px;
        z-index: 2;
    }
    .product-card.locked-item img {
        filter: grayscale(100%);
    }

    /* --- CSS CHO MODAL XÁC NHẬN THANH TOÁN --- */
    .custom-modal-checkout {
        display: none;
        position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center; align-items: center;
    }
    .modal-content-checkout {
        background-color: white; padding: 30px; border-radius: 12px;
        text-align: center; width: 350px; max-width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); animation: fadeIn 0.3s;
    }
    .modal-icon-checkout { font-size: 50px; margin-bottom: 15px; display: block; color: #5B743A; }
    .modal-title-checkout { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 10px; }
    .modal-desc-checkout { color: #666; margin-bottom: 25px; line-height: 1.5; }
    .modal-actions-checkout { display: flex; gap: 10px; justify-content: center; }
    .btn-modal-checkout { padding: 10px 20px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; font-size: 14px; }
    .btn-cancel-checkout { background: #eee; color: #333; }
    .btn-confirm-checkout { background: #5B743A; color: white; }
    .btn-confirm-checkout:hover { background: #4a602e; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="pos-container">
    
    <aside class="sidebar-menu">
        <div class="sidebar-header">Danh mục món</div>
        <ul>
            <li><a href="#" onclick="window.scrollTo(0,0); return false;">Tất cả món</a></li>
            <?php foreach ($categories_list as $id => $name): ?>
                <li><a href="#cat-<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="main-product-area">
        <?php if (!$can_sell): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba; display:flex; align-items:center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <span style="font-size: 24px;">⛔</span>
                <div>
                    <strong style="text-transform: uppercase;">Chế độ Xem (View Only)</strong><br>
                    <span style="font-size: 14px;"><?php echo $lock_reason; ?> Bạn không thể thực hiện thanh toán lúc này.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($menu_data)): ?>
            <?php foreach ($menu_data as $cat_id => $data): ?>
                <div id="cat-<?php echo $cat_id; ?>" class="category-section">
                    <div class="category-section-title">
                        <?php echo htmlspecialchars($data['name']); ?>
                    </div>
                    
                    <div class="product-grid">
                        <?php foreach ($data['products'] as $prod): 
                            // KIỂM TRA TRẠNG THÁI KHÓA MÓN
                            $is_locked = (isset($prod['is_locked']) && $prod['is_locked'] == 1);
                            
                            // Xác định hành động khi click
                            $click_action = $is_locked 
                                ? "showToast('⛔ Món này đang tạm ngưng phục vụ!', 'error')" 
                                : "addToCart({$prod['id']}, '" . htmlspecialchars(addslashes($prod['name'])) . "', {$prod['price']})";
                            
                            $card_class = $is_locked ? "product-card locked-item" : "product-card";
                        ?>
                            <div class="<?php echo $card_class; ?>" onclick="<?php echo $click_action; ?>">
                                
                                <?php if($is_locked): ?>
                                    <div class="locked-overlay">TẠM NGƯNG</div>
                                <?php endif; ?>

                                <img src="../admin/uploads/<?php echo htmlspecialchars($prod['image']); ?>" class="product-img" alt="img">
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div>
                                    <div class="product-price"><?php echo number_format($prod['price']); ?> đ</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #999;">
                <p>Chưa có sản phẩm nào.</p>
            </div>
        <?php endif; ?>
    </main>

</div>

<div class="fab-cart" onclick="toggleCart()">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>
    <div id="cart-badge" class="cart-badge">0</div>
</div>

<div id="cart-modal-overlay" class="cart-modal-overlay">
    <div class="cart-modal">
        <div class="cart-modal-header">
            <h3 style="margin:0; font-size: 18px;">Giỏ hàng</h3>
            <span style="cursor: pointer; font-size: 24px;" onclick="toggleCart()">×</span>
        </div>
        <div class="cart-modal-body" id="cart-body"></div>
        <div class="cart-modal-footer">
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold; font-size: 16px;">
                <span>Tổng tiền:</span>
                <span id="cart-total-price" style="color: #d32f2f;">0 đ</span>
            </div>

            <button onclick="showCheckoutModal()" 
                style="width: 100%; padding: 12px; background: #5B743A; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s;"
                id="btn-checkout"
                <?php echo (!$can_sell) ? 'disabled' : ''; ?>>
                <?php echo (!$can_sell) ? '⛔ ĐANG KHÓA' : 'THANH TOÁN'; ?>
            </button>

            <?php if (!$can_sell): ?>
                <script>
                    document.getElementById('btn-checkout').style.backgroundColor = '#ccc';
                    document.getElementById('btn-checkout').style.cursor = 'not-allowed';
                </script>
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="toast-container"></div>

<div id="checkoutConfirmModal" class="custom-modal-checkout">
    <div class="modal-content-checkout">
        <div class="modal-icon-checkout">🧾</div>
        <div class="modal-title-checkout">Xác nhận Thanh toán?</div>
        <div class="modal-desc-checkout">
            Tổng tiền: <strong id="modal-checkout-total" style="color: #d32f2f;">0 đ</strong><br>
            Bạn có chắc chắn muốn xuất hóa đơn?
        </div>
        <div class="modal-actions-checkout">
            <button class="btn-modal-checkout btn-cancel-checkout" onclick="closeCheckoutModal()">Hủy</button>
            <button class="btn-modal-checkout btn-confirm-checkout" onclick="submitCheckoutProcess()">Xác nhận</button>
        </div>
    </div>
</div>

<script>
    let cart = {};

    function addToCart(id, name, price) {
        if (cart[id]) { cart[id].quantity++; } 
        else { cart[id] = { name: name, price: price, quantity: 1 }; }
        updateCartBadge();
        showToast(`Đã thêm: <b>${name}</b>`, 'info');
        if (document.getElementById('cart-modal-overlay').style.display === 'flex') renderCartModal();
    }

    function updateCartBadge() {
        let count = 0;
        for (let id in cart) count += cart[id].quantity;
        document.getElementById('cart-badge').innerText = count;
        let fab = document.querySelector('.fab-cart');
        fab.style.transform = 'scale(1.15)';
        setTimeout(() => fab.style.transform = 'scale(1)', 200);
    }

    function showToast(message, type = 'info') {
        let container = document.getElementById('toast-container');
        let toast = document.createElement('div');
        let icon = type === 'success' ? '🎉' : (type === 'error' ? '⚠️' : '✅');
        let borderColor = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#5B743A');
        
        toast.className = 'toast';
        toast.style.borderLeftColor = borderColor;
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, type === 'success' ? 5000 : 2500);
    }

    function toggleCart() {
        let overlay = document.getElementById('cart-modal-overlay');
        overlay.style.display = (overlay.style.display === 'flex') ? 'none' : 'flex';
        if (overlay.style.display === 'flex') renderCartModal();
    }
    
    document.getElementById('cart-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) toggleCart();
    });

    function renderCartModal() {
        let body = document.getElementById('cart-body');
        let totalSpan = document.getElementById('cart-total-price');
        let total = 0;
        body.innerHTML = '';

        if (Object.keys(cart).length === 0) {
            body.innerHTML = `<div style="text-align:center; color:#999; padding-top:20px;">Giỏ hàng trống</div>`;
            totalSpan.innerText = '0 đ';
            return;
        }

        for (let id in cart) {
            let item = cart[id];
            total += item.price * item.quantity;
            body.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-name">${item.name}<br><small style="color:#888; font-weight:normal;">${item.price.toLocaleString()} đ</small></div>
                    <div class="cart-actions">
                        <button class="btn-sm-qty" onclick="adjustQty(${id}, -1)">-</button>
                        <span style="width:20px; text-align:center;">${item.quantity}</span>
                        <button class="btn-sm-qty" onclick="adjustQty(${id}, 1)">+</button>
                        <button class="btn-del" onclick="removeItem(${id})">×</button>
                    </div>
                </div>`;
        }
        totalSpan.innerText = total.toLocaleString('vi-VN') + ' đ';
    }

    function adjustQty(id, delta) {
        if (cart[id]) {
            cart[id].quantity += delta;
            if (cart[id].quantity <= 0) delete cart[id];
            updateCartBadge();
            renderCartModal();
        }
    }

    function removeItem(id) {
        delete cart[id];
        updateCartBadge();
        renderCartModal();
    }
    
    // --- HÀM XÁC NHẬN MODAL ---
    function showCheckoutModal() {
        if (Object.keys(cart).length === 0) { 
            showToast("Giỏ hàng trống!", 'error'); 
            return; 
        }

        let total = 0;
        for (let id in cart) total += cart[id].price * cart[id].quantity;

        document.getElementById('modal-checkout-total').innerText = total.toLocaleString('vi-VN') + ' đ';
        document.getElementById('checkoutConfirmModal').style.display = 'flex';
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutConfirmModal').style.display = 'none';
    }

    // Hàm này chứa logic xử lý thanh toán thực tế
    async function submitCheckoutProcess() {
        closeCheckoutModal(); // Đóng modal ngay

        // --- CHẶN Ở JAVASCRIPT CHO CHẮC ---
        <?php if (!$can_sell): ?>
            showToast("⛔ <?php echo $lock_reason; ?>", 'error');
            return;
        <?php endif; ?>
        // ----------------------------------------

        if (Object.keys(cart).length === 0) { return; }

        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cart)
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                cart = {};
                updateCartBadge();
                toggleCart();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối!', 'error');
        }
    }
</script>

<?php disconnect_db(); ?>