<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 
require '../includes/time_check.php';
require '../includes/auto_shift_check.php';

// --- 1. LẤY THÔNG TIN & QUYỀN ---
$uid = $_SESSION['user_id'];
$role = $_SESSION['role']; 
$q_user = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$r_user = mysqli_fetch_assoc($q_user);
$my_shift = $r_user['shift']; 

date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_hour = date('H');
$can_sell = is_working_hour($my_shift);
$lock_reason = "Ngoài ca làm việc!";

if ($current_hour >= 23 || $current_hour < 6) {
    if ($role == 'admin' || $my_shift == 'full') { $can_sell = true; } 
    else { $can_sell = false; $lock_reason = "Đã đóng cửa (23h-06h)"; }
}

// --- 2. LẤY DANH SÁCH SẢN PHẨM ---
$sql = "SELECT p.*, c.name as category_name, c.id as category_id 
        FROM products p JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0 ORDER BY c.id ASC, p.name ASC";
$result = mysqli_query($conn, $sql);

$menu_data = [];
$categories_list = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Lưu tồn kho
        $stock_list[$row['id']] = (int)$row['stock'];

        // Logic phân loại MỚI: Chỉ tìm chữ "topping"
        $cat_name_lower = mb_strtolower($row['category_name'], 'UTF-8');
        
        // CHỈNH SỬA TẠI ĐÂY: Bỏ điều kiện kiểm tra chữ "thêm"
        if (strpos($cat_name_lower, 'topping') !== false) {
            $topping_list[] = $row; // Chỉ "Topping..." mới vào đây
        } else {
            // "Đồ ăn thêm", "Đồ ăn chơi"... sẽ chạy vào đây và hiện lên Menu
            $menu_data[$row['category_id']]['name'] = $row['category_name'];
            $menu_data[$row['category_id']]['products'][] = $row;
            
            if (!isset($categories_list[$row['category_id']])) {
                $categories_list[$row['category_id']] = $row['category_name'];
            }
        }
    }
}
?>

<link rel="stylesheet" href="/QuanLyCaPhe/css/pos_style.css">

<div class="pos-container">
    <aside class="sidebar-menu">
        <div class="sidebar-header">Danh mục</div>
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
                <span style="font-size: 24px;">⛔</span>
                <div>
                    <strong>Chế độ Xem (View Only)</strong><br>
                    <span style="font-size: 14px;"><?php echo $lock_reason; ?> Bạn không thể thanh toán.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($menu_data)): ?>
            <?php foreach ($menu_data as $cat_id => $data): ?>
                <div id="cat-<?php echo $cat_id; ?>" class="category-section">
                    <div class="category-section-title"><?php echo htmlspecialchars($data['name']); ?></div>
                    <div class="product-grid">
                        <?php foreach ($data['products'] as $prod): 
                            $is_locked = (isset($prod['is_locked']) && $prod['is_locked'] == 1);
                            $card_class = $is_locked ? "product-card locked-item" : "product-card";
                            // Gọi hàm mở Modal chọn món
                            $click_action = $is_locked 
                                ? "showToast('⛔ Món này đang tạm ngưng!', 'error')" 
                                : "openOptionModal({$prod['id']}, '" . htmlspecialchars(addslashes($prod['name'])) . "', {$prod['price']}, '../admin/uploads/" . htmlspecialchars($prod['image']) . "')";
                        ?>
                            <div class="<?php echo $card_class; ?>" onclick="<?php echo $click_action; ?>">
                                <?php if($is_locked): ?><div class="locked-overlay">TẠM NGƯNG</div><?php endif; ?>
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
        <?php endif; ?>
    </main>
</div>

<div class="fab-cart" onclick="toggleCart()">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
    <div id="cart-badge" class="cart-badge">0</div>
</div>

<div id="cart-modal-overlay" class="cart-modal-overlay">
    <div class="cart-modal">
        <div class="cart-modal-header">
            <h3 style="margin:0;">Giỏ hàng</h3>
            <span style="cursor: pointer; font-size: 24px;" onclick="toggleCart()">×</span>
        </div>
        <div class="cart-modal-body" id="cart-body"></div>
        <div class="cart-modal-footer">
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold; font-size: 16px;">
                <span>Tổng tiền:</span>
                <span id="cart-total-price" style="color: #d32f2f;">0 đ</span>
            </div>
            <button onclick="showCheckoutModal()" id="btn-checkout" style="width: 100%; padding: 12px; background: #5B743A; color: white; border: none; border-radius: 6px; font-weight: bold;" <?php echo (!$can_sell) ? 'disabled' : ''; ?>>
                <?php echo (!$can_sell) ? '⛔ ĐANG KHÓA' : 'THANH TOÁN'; ?>
            </button>
            <?php if (!$can_sell): ?><script>document.getElementById('btn-checkout').style.backgroundColor = '#ccc';</script><?php endif; ?>
        </div>
    </div>
</div>

<div id="checkoutConfirmModal" class="custom-modal-checkout">
    <div class="modal-content-checkout">
        <div class="modal-icon-checkout">🧾</div>
        <div class="modal-title-checkout">Xác nhận Thanh toán?</div>
        <div class="modal-desc-checkout">Tổng tiền: <strong id="modal-checkout-total" style="color: #d32f2f;">0 đ</strong></div>
        <div class="modal-actions-checkout">
            <button class="btn-modal-checkout btn-cancel-checkout" onclick="document.getElementById('checkoutConfirmModal').style.display='none'">Hủy</button>
            <button class="btn-modal-checkout btn-confirm-checkout" onclick="submitCheckoutProcess()">Xác nhận</button>
        </div>
    </div>
</div>

<div id="productOptionModal" class="custom-modal">
    <div class="modal-content-option">
        <div class="opt-header">
            <h3 id="opt-product-name">Tên Món</h3>
            <span style="cursor:pointer; font-size:24px;" onclick="closeOptionModal()">×</span>
        </div>
        <div class="opt-body">
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <img id="opt-product-img" src="" style="width:80px; height:80px; border-radius:8px; object-fit:cover;">
                <div>
                    <div style="color:#666;">Giá gốc:</div>
                    <div id="opt-product-base-price" style="font-weight:bold;">0 đ</div>
                </div>
            </div>

            <div class="opt-section">
                <span class="opt-title">Kích cỡ (Size):</span>
                <div class="radio-group">
                    <label class="radio-btn"><input type="radio" name="opt_size" value="S" data-price="0" onclick="updateTotalPrice()"><span>Nhỏ (S)</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_size" value="M" data-price="0" checked onclick="updateTotalPrice()"><span>Vừa (M)</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_size" value="L" data-price="5000" onclick="updateTotalPrice()"><span>Lớn (L) +5k</span></label>
                </div>
            </div>

            <div class="opt-section">
                <span class="opt-title">Lượng Đá (Miễn phí):</span>
                <div class="radio-group">
                    <label class="radio-btn"><input type="radio" name="opt_ice" value="100%" checked><span>100% Đá</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_ice" value="70%"><span>70% Đá</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_ice" value="30%"><span>30% Đá</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_ice" value="0%"><span>Không Đá</span></label>
                    <label class="radio-btn"><input type="radio" name="opt_ice" value="Nóng"><span>Nóng</span></label>
                </div>
            </div>

            <div class="opt-section" id="section-topping" style="display:none;">
                <span class="opt-title">Topping / Ăn kèm:</span>
                <div id="topping-list-container">
                    </div>
            </div>
        </div>
        
        <div class="opt-footer">
            <div>Tổng: <span id="opt-total-price" class="price-tag">0 đ</span></div>
            <div class="btn-group-action">
                <button class="btn-add-cart" onclick="confirmAddToCart(false)">Thêm vào giỏ</button>
                <button class="btn-buy-now" onclick="confirmAddToCart(true)">Mua ngay</button>
            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
    // 1. Danh sách tồn kho của tất cả sản phẩm
    const stockData = <?php echo json_encode($stock_list); ?>;
    
    // 2. Danh sách Topping lấy từ DB
    const toppingData = <?php echo json_encode($topping_list); ?>;
</script>

<script>
    let cart = {}; 
    let currentProd = {}; 

    // --- HÀM MỞ MODAL CHỌN MÓN ---
    function openOptionModal(id, name, basePrice, img) {
        currentProd = { id: id, name: name, basePrice: basePrice, img: img };
        
        document.getElementById('opt-product-name').innerText = name;
        document.getElementById('opt-product-base-price').innerText = basePrice.toLocaleString() + ' đ';
        document.getElementById('opt-product-img').src = img;
        
        // Reset Inputs cơ bản
        document.getElementsByName('opt_size').forEach(r => { if(r.value === 'M') r.checked = true; });
        document.getElementsByName('opt_ice').forEach(r => { if(r.value === '100%') r.checked = true; });
        
        // --- PHẦN MỚI: RENDER TOPPING TỪ CSDL ---
        const toppingContainer = document.getElementById('topping-list-container');
        const toppingSection = document.getElementById('section-topping');
        toppingContainer.innerHTML = ''; // Xóa cũ

        // Kiểm tra xem có Topping nào trong CSDL không
        if (toppingData && toppingData.length > 0) {
            toppingSection.style.display = 'block';
            toppingData.forEach(top => {
                // Chỉ hiện topping còn hàng
                if(top.stock > 0) {
                    let html = `
                        <label class="topping-item">
                            <input type="checkbox" class="chk-topping" value="${top.name}" data-price="${top.price}" onclick="updateTotalPrice()"> 
                            <span>${top.name} (+${parseInt(top.price).toLocaleString()}đ)</span>
                        </label>`;
                    toppingContainer.innerHTML += html;
                }
            });
        } else {
            // Nếu không có topping nào thì ẩn mục này đi
            toppingSection.style.display = 'none';
        }

        updateTotalPrice();
        document.getElementById('productOptionModal').style.display = 'flex';
    }

    function closeOptionModal() {
        document.getElementById('productOptionModal').style.display = 'none';
    }

    // --- TÍNH TOÁN GIÁ ---
    function updateTotalPrice() {
        let price = currentProd.basePrice;
        let sizeEl = document.querySelector('input[name="opt_size"]:checked');
        if(sizeEl) price += parseInt(sizeEl.getAttribute('data-price'));
        
        document.querySelectorAll('.chk-topping:checked').forEach(t => price += parseInt(t.getAttribute('data-price')));
        document.getElementById('opt-total-price').innerText = price.toLocaleString() + ' đ';
        return price;
    }

    // --- THÊM VÀO GIỎ ---
    function confirmAddToCart(isBuyNow) {

        let id = currentProd.id;
        let maxStock = stockData[id] || 0;

        let currentQtyInCart = 0;
        for (let key in cart) {
            if (cart[key].id == id) {
                currentQtyInCart += cart[key].quantity;
            }
        }

        if (currentQtyInCart + 1 > maxStock) {
            showToast(`⚠️ Không thể thêm! Kho chỉ còn ${maxStock} món.`, 'error');
            return; // Dừng hàm ngay lập tức, không cho thêm
        }

        let size = document.querySelector('input[name="opt_size"]:checked').value;
        let ice = document.querySelector('input[name="opt_ice"]:checked').value;
        let toppingArr = [];
        document.querySelectorAll('.chk-topping:checked').forEach(t => toppingArr.push(t.value));
        
        let finalPrice = updateTotalPrice(); 
        let uniqueKey = `${currentProd.id}_${size}_${ice}_${toppingArr.join('')}`;
        let note = `Size: ${size}, Đá: ${ice}`;
        if(toppingArr.length > 0) note += `, Topping: ${toppingArr.join(', ')}`;

        if (cart[uniqueKey]) {
            cart[uniqueKey].quantity++;
        } else {
            cart[uniqueKey] = {
                id: currentProd.id, name: currentProd.name, price: finalPrice, quantity: 1, note: note
            };
        }

        updateCartBadge();
        closeOptionModal();
        if (isBuyNow) { renderCartModal(); showCheckoutModal(); } else { showToast(`Đã thêm: <b>${currentProd.name}</b>`, 'info'); }
    }

    // --- CÁC HÀM XỬ LÝ KHÁC (GIỮ NGUYÊN) ---
    function updateCartBadge() {
        let count = 0;
        for (let key in cart) count += cart[key].quantity;
        document.getElementById('cart-badge').innerText = count;
    }

    function renderCartModal() {
        let body = document.getElementById('cart-body');
        let totalSpan = document.getElementById('cart-total-price');
        let total = 0;
        body.innerHTML = '';

        if (Object.keys(cart).length === 0) {
            body.innerHTML = `<div style="text-align:center;color:#999;padding-top:20px;">
                                <div style="font-size: 30px; margin-bottom: 10px;">🛒</div>
                                Giỏ hàng trống
                              </div>`;
            totalSpan.innerText = '0 đ';
            return;
        }

        for (let key in cart) {
            let item = cart[key];
            let itemTotal = item.price * item.quantity;
            total += itemTotal;

            // Xử lý chuỗi Note để hiển thị đẹp hơn
            // Ví dụ note gốc: "Size: M, Đá: 50%, Topping: Kem muối, Thạch trà"
            // Ta sẽ tách dòng Topping ra cho dễ nhìn
            let displayNote = item.note;
            if(displayNote.includes("Topping:")) {
                // Thay thế dấu phẩy ngăn cách topping bằng thẻ xuống dòng <br> hoặc dấu chấm tròn
                displayNote = displayNote.replace("Topping:", "<br><b>+ Topping:</b>");
            }

            body.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-left">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-note" style="font-size: 13px; color: #666; margin-top: 4px; line-height: 1.4;">
                            ${displayNote}
                        </div>
                    </div>
                    
                    <div class="cart-item-right">
                        <div class="cart-price">${item.price.toLocaleString()} đ</div>
                        
                        <div class="cart-actions">
                            <button class="btn-sm-qty" onclick="adjustQty('${key}', -1)">-</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="btn-sm-qty" onclick="adjustQty('${key}', 1)">+</button>
                        </div>
                        
                        <button class="btn-del-item" onclick="removeItem('${key}')">×</button>
                    </div>
                </div>`;
        }
        totalSpan.innerText = total.toLocaleString('vi-VN') + ' đ';
    }

    function adjustQty(key, delta) {
        if (cart[key]) {
            if (delta > 0) { // Chỉ kiểm tra khi bấm Tăng
                let id = cart[key].id;
                let maxStock = stockData[id] || 0;
                
                let currentQtyInCart = 0;
                for (let k in cart) { if(cart[k].id == id) currentQtyInCart += cart[k].quantity; }

                if (currentQtyInCart + 1 > maxStock) {
                    showToast(`⚠️ Hết hàng! Kho chỉ còn ${maxStock}.`, 'error');
                    return; // Chặn không cho tăng
                }
            }
            cart[key].quantity += delta;
            if (cart[key].quantity <= 0) delete cart[key];
            updateCartBadge(); renderCartModal();
        }
    }

    function removeItem(key) {
        delete cart[key]; updateCartBadge(); renderCartModal();
    }

    function showCheckoutModal() {
        if (Object.keys(cart).length === 0) { showToast("Giỏ hàng trống!", 'error'); return; }
        let total = 0;
        for (let key in cart) total += cart[key].price * cart[key].quantity;
        document.getElementById('modal-checkout-total').innerText = total.toLocaleString('vi-VN') + ' đ';
        document.getElementById('cart-modal-overlay').style.display = 'none'; 
        document.getElementById('checkoutConfirmModal').style.display = 'flex'; 
    }

    function toggleCart() {
        let overlay = document.getElementById('cart-modal-overlay');
        overlay.style.display = (overlay.style.display === 'flex') ? 'none' : 'flex';
        if(overlay.style.display === 'flex') renderCartModal();
    }
    document.getElementById('cart-modal-overlay').addEventListener('click', function(e){ if(e.target === this) toggleCart(); });

    async function submitCheckoutProcess() {
        document.getElementById('checkoutConfirmModal').style.display = 'none';
        <?php if (!$can_sell): ?>showToast("⛔ <?php echo $lock_reason; ?>", 'error'); return;<?php endif; ?>
        
        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cart) 
            });
            const result = await response.json();
            if (result.success) { showToast(result.message, 'success'); cart = {}; updateCartBadge(); } 
            else { showToast(result.message, 'error'); }
        } catch (error) { showToast('Lỗi kết nối!', 'error'); }
    }

    function showToast(message, type = 'info') {
        let container = document.getElementById('toast-container');
        let toast = document.createElement('div');
        let icon = type === 'success' ? '🎉' : (type === 'error' ? '⚠️' : '✅');
        let borderColor = type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#5B743A');
        toast.className = 'toast'; toast.style.borderLeftColor = borderColor;
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2500);
    }
</script>

<?php 

disconnect_db(); 
?>
