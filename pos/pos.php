<?php
// =================================================================
// 1. KẾT NỐI VÀ BẢO VỆ TRANG
// =================================================================
require '../includes/auth_pos.php'; 
require '../includes/header.php';   
require '../includes/time_check.php'; 
require '../includes/auto_shift_check.php'; 

// Nhúng Style POS
echo '<link rel="stylesheet" href="../css/pos_style.css">';


// =================================================================
// 2. KHỞI TẠO DỮ LIỆU
// =================================================================
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
    if ($role == 'admin' || $my_shift == 'full') { 
        $can_sell = true; 
    } else { 
        $can_sell = false; 
        $lock_reason = "Đã đóng cửa (23h-06h)"; 
    }
}

// =================================================================
// 3. LẤY SẢN PHẨM & PHÂN LOẠI (LOGIC MỚI)
// =================================================================
$sql = "SELECT p.*, c.name as category_name, c.id as category_id 
        FROM products p JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0 
        ORDER BY c.id ASC, p.name ASC";
$result = mysqli_query($conn, $sql);

$menu_data = [];       
$categories_list = []; 
$stock_list = [];      
$topping_list = [];    

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $stock_list[$row['id']] = (int)$row['stock'];

        $cat_name_lower = mb_strtolower($row['category_name'], 'UTF-8');
        
        // 1. Nếu là Topping: Thêm vào danh sách để hiện trong Modal chọn option
        if (strpos($cat_name_lower, 'topping') !== false) {
            $topping_list[] = $row; 
        } 
        
        // 2. [SỬA LẠI] LUÔN THÊM VÀO MENU CHÍNH
        // Để topping cũng hiện ra bên layout trái như món bình thường
        $menu_data[$row['category_id']]['name'] = $row['category_name'];
        $menu_data[$row['category_id']]['products'][] = $row;
        
        if (!isset($categories_list[$row['category_id']])) {
            $categories_list[$row['category_id']] = $row['category_name'];
        }
    }
}
?>

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
                    <strong>Chế độ Xem</strong><br>
                    <span style="font-size: 14px;"><?php echo $lock_reason; ?></span>
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

<div id="deleteConfirmModal" class="custom-modal-checkout">
    <div class="modal-content-checkout">
        <div class="modal-icon-checkout" style="color: #dc3545;">🗑️</div>
        <div class="modal-title-checkout">Xóa món này?</div>
        <div class="modal-desc-checkout" style="color:#666; font-size:14px;">Bạn có chắc chắn muốn bỏ món này khỏi giỏ hàng?</div>
        <div class="modal-actions-checkout">
            <button class="btn-modal-checkout btn-cancel-checkout" onclick="closeDeleteModal()">Không</button>
            <button class="btn-modal-checkout btn-confirm-checkout" onclick="confirmDeleteAction()" style="background: #dc3545;">Xóa ngay</button>
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
                    <div style="color:#666;">Giá</div>
                    <div id="opt-product-base-price" style="font-weight:bold;">0 đ</div>
                </div>
            </div>
            <div class="opt-section">
                <div class="topping-qty-ctrl" style="justify-content: center; width: 120px;">
                    <button class="btn-qty-top" onclick="changeMainQty(-1)">-</button>
                    <input type="number" id="input-main-qty" class="input-qty-top" value="1" min="1" style="width: 50px; font-weight:bold; font-size:16px;">
                    <button class="btn-qty-top" onclick="changeMainQty(1)">+</button>
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
                <span class="opt-title">Topping / Ăn kèm (Chọn số lượng):</span>
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
    // Dữ liệu từ PHP
    const stockData = <?php echo json_encode($stock_list); ?>;
    const toppingData = <?php echo json_encode($topping_list); ?>;
</script>

<script>
    let cart = {}; 
    let currentProd = {}; 
    let itemToDeleteKey = null; 
    let editingCartKey = null; 

    // --- 1. MỞ MODAL ---
    function openOptionModal(id, name, basePrice, img, isEditMode = false) {
        currentProd = { id: id, name: name, basePrice: basePrice, img: img };
        
        document.getElementById('opt-product-name').innerText = name;
        document.getElementById('opt-product-base-price').innerText = basePrice.toLocaleString() + ' đ';
        document.getElementById('opt-product-img').src = img;

        if (!isEditMode) {
            document.getElementById('input-main-qty').value = 1;
            document.getElementsByName('opt_size').forEach(r => { if(r.value === 'M') r.checked = true; });
            document.getElementsByName('opt_ice').forEach(r => { if(r.value === '100%') r.checked = true; });
            editingCartKey = null; 
            updateModalButtons(false);
        }

        renderToppingList();
        document.getElementById('productOptionModal').style.display = 'flex';
        
        if (!isEditMode) calculateModalPrice();
    }

    function renderToppingList() {
        const toppingContainer = document.getElementById('topping-list-container');
        const toppingSection = document.getElementById('section-topping');
        toppingContainer.innerHTML = ''; 

        if (toppingData && toppingData.length > 0) {
            toppingSection.style.display = 'block';
            toppingData.forEach(top => {
                let isLocked = (top.is_locked == 1);
                let isOutOfStock = (top.stock <= 0);
                let disableAttr = (isLocked || isOutOfStock) ? 'disabled' : '';
                let opacityStyle = (isLocked || isOutOfStock) ? 'opacity: 0.5;' : '';
                let statusText = isLocked ? '(Tạm ngưng)' : (isOutOfStock ? '(Hết hàng)' : '');

                let html = `
                    <div class="topping-row" style="${opacityStyle}">
                        <div class="topping-info">
                            <span>${top.name} <b style="color:red; font-size:12px;">${statusText}</b> (+${parseInt(top.price).toLocaleString()}đ)</span>
                        </div>
                        <div class="topping-qty-ctrl">
                            <button class="btn-qty-top" onclick="changeTopQty(${top.id}, -1)" ${disableAttr}>-</button>
                            <input type="number" id="top-qty-${top.id}" class="input-qty-top" value="0" min="0" 
                                data-id="${top.id}" data-name="${top.name}" data-price="${top.price}" 
                                onchange="manualTopQty(${top.id}, this.value)" onfocus="this.select()" ${disableAttr}>
                            <button class="btn-qty-top" onclick="changeTopQty(${top.id}, 1)" ${disableAttr}>+</button>
                        </div>
                    </div>`;
                toppingContainer.innerHTML += html;
            });
        } else {
            toppingSection.style.display = 'none';
        }
    }

    // --- SỬA MÓN TỪ GIỎ HÀNG ---
    function editCartItem(key) {
        if (!cart[key]) return;
        let item = cart[key];
        
        // Mở modal
        openOptionModal(item.id, item.name, item.basePrice || 0, item.img || '', true);
        editingCartKey = key; // Đánh dấu đang sửa

        // Điền lại dữ liệu
        document.getElementById('input-main-qty').value = item.mainContentQty; 
        document.getElementsByName('opt_size').forEach(r => { if(r.value === item.options.size) r.checked = true; });
        document.getElementsByName('opt_ice').forEach(r => { if(r.value === item.options.ice) r.checked = true; });

        if (item.options.toppings) {
            for (let [topId, qty] of Object.entries(item.options.toppings)) {
                let input = document.getElementById('top-qty-' + topId);
                if (input) input.value = qty;
            }
        }

        updateModalButtons(true);
        calculateModalPrice();
    }

    // --- LOGIC TÍNH TOÁN (ĐỘC LẬP) ---
    function calculateModalPrice() {
        let mainQty = parseInt(document.getElementById('input-main-qty').value) || 1;
        let oneItemPrice = currentProd.basePrice;
        let sizeEl = document.querySelector('input[name="opt_size"]:checked');
        if(sizeEl) oneItemPrice += parseInt(sizeEl.getAttribute('data-price'));
        
        let totalMainPrice = oneItemPrice * mainQty; // Giá nước * SL nước

        let totalToppingPrice = 0;
        let topInputs = document.querySelectorAll('.input-qty-top');
        topInputs.forEach(inp => {
            let qty = parseInt(inp.value) || 0;
            let p = parseInt(inp.getAttribute('data-price')) || 0;
            totalToppingPrice += (qty * p); // Topping cộng riêng
        });

        let unitPrice = totalMainPrice + totalToppingPrice;
        document.getElementById('opt-total-price').innerText = unitPrice.toLocaleString() + ' đ';
        return unitPrice; 
    }

    function changeMainQty(delta) {
        let input = document.getElementById('input-main-qty');
        let currentVal = parseInt(input.value) || 1;
        let newVal = currentVal + delta;
        if (newVal < 1) return;

        if (delta > 0) {
            let maxStock = stockData[currentProd.id] || 0;
            if (newVal > maxStock) { showToast(`⚠️ Hết hàng! Kho chỉ còn ${maxStock}.`, 'error'); return; }
        }
        input.value = newVal;
        calculateModalPrice();
    }

    function changeTopQty(topId, delta) {
        let input = document.getElementById('top-qty-' + topId);
        let newVal = (parseInt(input.value) || 0) + delta;
        if (newVal < 0) return;
        if (delta > 0 && !checkTopStock(topId, newVal)) return;
        input.value = newVal;
        calculateModalPrice();
    }

    function manualTopQty(topId, valStr) {
        let input = document.getElementById('top-qty-' + topId);
        let newVal = parseInt(valStr) || 0;
        if (newVal < 0) newVal = 0;
        if (!checkTopStock(topId, newVal)) {
             let topInfo = toppingData.find(t => t.id == topId);
             newVal = topInfo ? parseInt(topInfo.stock) : 0;
        }
        input.value = newVal;
        calculateModalPrice();
    }

    function checkTopStock(topId, quantityRequest) {
        let topInfo = toppingData.find(t => t.id == topId);
        let maxStock = topInfo ? parseInt(topInfo.stock) : 0;
        if (quantityRequest > maxStock) { showToast(`⚠️ Topping hết hàng!`, 'error'); return false; }
        return true;
    }

    // --- LƯU VÀO GIỎ HÀNG ---
    function confirmAddToCart(isBuyNow) {
        let mainContentQty = parseInt(document.getElementById('input-main-qty').value) || 1;
        let id = currentProd.id;
        
        let cartMultiplier = 1; 
        if (editingCartKey && cart[editingCartKey]) {
            cartMultiplier = cart[editingCartKey].quantity; 
        }

        let maxStock = stockData[id] || 0;
        if (mainContentQty * cartMultiplier > maxStock) {
             showToast(`⚠️ Không đủ hàng! Kho còn ${maxStock}.`, 'error');
             return;
        }

        let size = document.querySelector('input[name="opt_size"]:checked').value;
        let ice = document.querySelector('input[name="opt_ice"]:checked').value;
        let unitPrice = calculateModalPrice(); 

        let toppingMap = {}; 
        let toppingStrForKey = ""; 
        let noteTopping = [];

        let topInputs = document.querySelectorAll('.input-qty-top');
        topInputs.forEach(inp => {
            let qty = parseInt(inp.value) || 0;
            if(qty > 0) {
                let name = inp.getAttribute('data-name');
                let tId = inp.getAttribute('data-id');
                noteTopping.push(`${name} (x${qty})`);
                toppingStrForKey += `_${name}_${qty}`; 
                toppingMap[tId] = qty;
            }
        });

        let uniqueKey = `${currentProd.id}_${mainContentQty}_${size}_${ice}${toppingStrForKey}`;
        
        let note = `SL: ${mainContentQty}, Size: ${size}, Đá: ${ice}`;
        if(noteTopping.length > 0) note += `, Topping: ${noteTopping.join(', ')}`;

        let cartItem = {
            id: currentProd.id, 
            name: currentProd.name, 
            img: currentProd.img,        
            basePrice: currentProd.basePrice,
            unitPrice: unitPrice, 
            quantity: 1, 
            mainContentQty: mainContentQty, 
            note: note,
            options: { size: size, ice: ice, toppings: toppingMap }
        };

        if (editingCartKey) {
            // --- SỬA XONG -> QUAY VỀ GIỎ HÀNG ---
            if (uniqueKey !== editingCartKey) delete cart[editingCartKey];
            cartItem.quantity = cartMultiplier; 
            cart[uniqueKey] = cartItem;
            
            showToast('✅ Đã cập nhật!', 'success');
            
            // QUAN TRỌNG: Mở lại giỏ hàng sau khi lưu
            closeOptionModal();
            updateCartBadge();
            renderCartModal();
            document.getElementById('cart-modal-overlay').style.display = 'flex'; // Hiện lại giỏ
            
        } else {
            // --- THÊM MỚI ---
            if (cart[uniqueKey]) cart[uniqueKey].quantity += 1; 
            else cart[uniqueKey] = cartItem;
            
            updateCartBadge();
            closeOptionModal();
            if (isBuyNow) { renderCartModal(); showCheckoutModal(); } 
            else { showToast(`Đã thêm: <b>${currentProd.name}</b>`, 'info'); }
        }
    }

    // --- CÁC HÀM KHÁC ---
    function renderCartModal() {
        let body = document.getElementById('cart-body');
        let totalSpan = document.getElementById('cart-total-price');
        let grandTotal = 0;
        body.innerHTML = '';

        if (Object.keys(cart).length === 0) {
            body.innerHTML = `<div style="text-align:center;color:#999;padding-top:20px;">Giỏ hàng trống</div>`;
            totalSpan.innerText = '0 đ';
            return;
        }

        for (let key in cart) {
            let item = cart[key];
            let itemTotal = item.unitPrice * item.quantity;
            grandTotal += itemTotal;
            let displayNote = item.note.replace("Topping:", "<br><span style='color:#E67E22; font-size:12px;'>+ Topping:</span>");

            body.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-left" onclick="toggleCart(); editCartItem('${key}')" style="cursor:pointer;" title="Sửa món này">
                        <div class="cart-item-name" style="color:#007bff; display:flex; align-items:center; gap:5px;">
                            ${item.name} <span style="font-size:12px;">✏️</span>
                        </div>
                        <div class="cart-item-note">${displayNote}</div>
                    </div>
                    <div class="cart-item-right" style="align-items: flex-end;">
                        <div style="text-align:right; font-size:12px; margin-bottom:5px;">
                            <div style="color:#28a745; font-weight:600;">
                                ${item.unitPrice.toLocaleString()} x <b style="font-size:14px;">${item.quantity}</b>
                            </div>
                            <div style="border-top:1px solid #eee; margin-top:2px; font-weight:bold; color:#d32f2f;">
                                = ${itemTotal.toLocaleString()} đ
                            </div>
                        </div>
                        <div class="cart-actions">
                            <button class="btn-sm-qty" onclick="changeCartQty('${key}', -1)">-</button>
                            <input type="number" class="input-cart-qty" value="${item.quantity}" readonly>
                            <button class="btn-sm-qty" onclick="changeCartQty('${key}', 1)">+</button>
                        </div>
                        <button class="btn-del-item" onclick="removeItem('${key}')">×</button>
                    </div>
                </div>`;
        }
        totalSpan.innerText = grandTotal.toLocaleString('vi-VN') + ' đ';
    }

    function changeCartQty(key, delta) {
        if (!cart[key]) return;
        let item = cart[key];
        if (delta > 0) {
            let maxStock = stockData[item.id] || 0;
            if (item.mainContentQty * (item.quantity + 1) > maxStock) {
                showToast(`⚠️ Hết hàng!`, 'error'); return;
            }
        }
        let newQty = item.quantity + delta;
        if (newQty <= 0) openDeleteModal(key);
        else { item.quantity = newQty; updateCartBadge(); renderCartModal(); }
    }

    function updateModalButtons(isEditing) {
        const footer = document.querySelector('.opt-footer .btn-group-action');
        if (isEditing) footer.innerHTML = `<button class="btn-add-cart" onclick="confirmAddToCart(false)" style="width:100%; background:#FF9800;">💾 Lưu & Quay lại Giỏ</button>`;
        else footer.innerHTML = `<button class="btn-add-cart" onclick="confirmAddToCart(false)">Thêm vào giỏ</button><button class="btn-buy-now" onclick="confirmAddToCart(true)">Mua ngay</button>`;
    }

    function removeItem(key) { openDeleteModal(key); }
    function openDeleteModal(key) { itemToDeleteKey = key; document.getElementById('deleteConfirmModal').style.display = 'flex'; }
    function closeDeleteModal() { document.getElementById('deleteConfirmModal').style.display = 'none'; itemToDeleteKey = null; renderCartModal(); }
    function confirmDeleteAction() { if (itemToDeleteKey && cart[itemToDeleteKey]) { delete cart[itemToDeleteKey]; updateCartBadge(); renderCartModal(); showToast('Đã xóa món', 'info'); } closeDeleteModal(); }
    function updateCartBadge() { let count = 0; for (let key in cart) count += cart[key].quantity; document.getElementById('cart-badge').innerText = count; }
    
    function showCheckoutModal() {
        if (Object.keys(cart).length === 0) { showToast("Giỏ hàng trống!", 'error'); return; }
        let total = 0;
        for (let key in cart) total += (cart[key].unitPrice * cart[key].quantity);
        document.getElementById('modal-checkout-total').innerText = total.toLocaleString('vi-VN') + ' đ';
        document.getElementById('cart-modal-overlay').style.display = 'none'; 
        document.getElementById('checkoutConfirmModal').style.display = 'flex'; 
    }

    // --- HÀM HỦY THANH TOÁN (Mới) ---
    function cancelCheckout() {
        document.getElementById('checkoutConfirmModal').style.display = 'none';
        document.getElementById('cart-modal-overlay').style.display = 'flex'; // Mở lại giỏ hàng
    }

    function toggleCart() { let overlay = document.getElementById('cart-modal-overlay'); overlay.style.display = (overlay.style.display === 'flex') ? 'none' : 'flex'; if(overlay.style.display === 'flex') renderCartModal(); }
    document.getElementById('cart-modal-overlay').addEventListener('click', function(e){ if(e.target === this) toggleCart(); });
    function closeOptionModal() { document.getElementById('productOptionModal').style.display = 'none'; editingCartKey = null; }

    async function submitCheckoutProcess() {
        document.getElementById('checkoutConfirmModal').style.display = 'none';
        let cartToSend = {};
        for (let key in cart) {
            let item = cart[key];
            cartToSend[key] = { id: item.id, price: item.unitPrice, quantity: item.quantity, note: item.note };
        }
        try {
            const response = await fetch('checkout_process.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cartToSend) });
            const result = await response.json();
            if (result.success) { 
                showToast(result.message, 'success'); 
                cart = {}; updateCartBadge(); 
                document.getElementById('cart-modal-overlay').style.display = 'none'; // Thành công thì tắt hết
            } else { showToast(result.message, 'error'); }
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
<?php disconnect_db(); ?>