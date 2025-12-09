<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';
require '../includes/auto_shift_check.php';

// --- PHẦN XỬ LÝ PHP (Giữ nguyên) ---
$uid = $_SESSION['user_id'];
$q_user = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$r_user = mysqli_fetch_assoc($q_user);
$my_shift = $r_user['shift'];
$can_sell = is_working_hour($my_shift);

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

<div class="pos-container">
    
    <aside class="sidebar-menu">
        <div class="sidebar-header">
            Danh mục món
        </div>
        <ul>
            <li><a href="#" onclick="window.scrollTo(0,0); return false;">Tất cả món</a></li>
            
            <?php foreach ($categories_list as $id => $name): ?>
                <li><a href="#cat-<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="main-product-area">
        <?php if (!$can_sell): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba; display:flex; align-items:center; gap: 10px;">
                <span style="font-size: 20px;">⚠️</span>
                <div>
                    <strong>Ngoài ca làm việc!</strong> Chỉ xem thực đơn, không thể thanh toán.
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
                        <?php foreach ($data['products'] as $prod): ?>
                            <div class="product-card" onclick="addToCart(
                                <?php echo $prod['id']; ?>, 
                                '<?php echo htmlspecialchars(addslashes($prod['name'])); ?>', 
                                <?php echo $prod['price']; ?>
                            )">
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
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
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
            <button onclick="checkout()" style="width: 100%; padding: 12px; background: #5B743A; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#4a602e'" onmouseout="this.style.background='#5B743A'"
            <?php echo (!$can_sell) ? 'disabled style="background: #ccc !important"' : ''; ?>>
                <?php echo (!$can_sell) ? 'ĐANG KHÓA' : 'THANH TOÁN'; ?>
            </button>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
    let cart = {};

    function addToCart(id, name, price) {
        if (cart[id]) { cart[id].quantity++; } 
        else { cart[id] = { name: name, price: price, quantity: 1 }; }
        updateCartBadge();
        showToast(`Đã thêm: <b>${name}</b>`, 'info');
        if(document.getElementById('cart-modal-overlay').style.display === 'flex') renderCartModal();
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
        if(overlay.style.display === 'flex') renderCartModal();
    }
    
    document.getElementById('cart-modal-overlay').addEventListener('click', function(e){
        if(e.target === this) toggleCart();
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

    async function checkout() {
        if (Object.keys(cart).length === 0) { showToast("Giỏ hàng trống!", 'error'); return; }
        if (!confirm("Thanh toán?")) return;

        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cart) 
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success'); 
                cart = {}; updateCartBadge(); toggleCart(); 
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi kết nối!', 'error');
        }
    }
</script>

<?php disconnect_db(); ?>