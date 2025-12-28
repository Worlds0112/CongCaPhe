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
                    <div style="color:#666;">Giá gốc:</div>
                    <div id="opt-product-base-price" style="font-weight:bold;">0 đ</div>
                </div>
            </div>
            <div class="opt-section">
                <span class="opt-title">Số lượng món chính:</span>
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
    let itemToDeleteKey = null; // Biến lưu key món cần xóa

    // --- 1. MỞ MODAL CHỌN MÓN ---
    function openOptionModal(id, name, basePrice, img) {
        currentProd = { id: id, name: name, basePrice: basePrice, img: img };
        
        document.getElementById('opt-product-name').innerText = name;
        document.getElementById('opt-product-base-price').innerText = basePrice.toLocaleString() + ' đ';
        document.getElementById('opt-product-img').src = img;
        
        // --- BỔ SUNG DÒNG NÀY: Reset số lượng món chính về 1 ---
        document.getElementById('input-main-qty').value = 1;

        // Reset Form
        document.getElementsByName('opt_size').forEach(r => { if(r.value === 'M') r.checked = true; });
        document.getElementsByName('opt_ice').forEach(r => { if(r.value === '100%') r.checked = true; });
        
        // ... (phần còn lại giữ nguyên) ...
        
        // Render danh sách Topping
        const toppingContainer = document.getElementById('topping-list-container');
        const toppingSection = document.getElementById('section-topping');
        toppingContainer.innerHTML = ''; 

        if (toppingData && toppingData.length > 0) {
            toppingSection.style.display = 'block';
            toppingData.forEach(top => {
                if(top.stock > 0) {
                    let html = `
                        <div class="topping-row">
                            <div class="topping-info">
                                <span>${top.name} (+${parseInt(top.price).toLocaleString()}đ)</span>
                            </div>
                            <div class="topping-qty-ctrl">
                                <button class="btn-qty-top" onclick="changeTopQty(${top.id}, -1)">-</button>
                                <input type="number" id="top-qty-${top.id}" class="input-qty-top" value="0" min="0" data-name="${top.name}" data-price="${top.price}" readonly>
                                <button class="btn-qty-top" onclick="changeTopQty(${top.id}, 1)">+</button>
                            </div>
                        </div>`;
                    toppingContainer.innerHTML += html;
                }
            });
        } else {
            toppingSection.style.display = 'none';
        }

        calculateModalPrice();
        document.getElementById('productOptionModal').style.display = 'flex';
    }

    function closeOptionModal() {
        document.getElementById('productOptionModal').style.display = 'none';
    }

    // Tăng giảm số lượng Topping trong Modal
    function changeTopQty(id, delta) {
        let input = document.getElementById('top-qty-' + id);
        let val = parseInt(input.value) || 0;
        val += delta;
        if(val < 0) val = 0;
        input.value = val;
        calculateModalPrice();
    }
// --- BỔ SUNG: Hàm tăng giảm số lượng món chính ---
    function changeMainQty(delta) {
        let input = document.getElementById('input-main-qty');
        let val = parseInt(input.value) || 1;
        val += delta;
        if (val < 1) val = 1; // Không cho nhỏ hơn 1
        input.value = val;
        calculateModalPrice(); // Tính lại tiền ngay
    }

    // --- SỬA LẠI: Hàm tính giá trong Modal (Phải nhân với số lượng món chính) ---
    function calculateModalPrice() {
        // 1. Lấy số lượng món chính
        let mainQty = parseInt(document.getElementById('input-main-qty').value) || 1;

        // 2. Tính giá 1 đơn vị (Base + Size)
        let oneItemPrice = currentProd.basePrice;
        
        let sizeEl = document.querySelector('input[name="opt_size"]:checked');
        if(sizeEl) oneItemPrice += parseInt(sizeEl.getAttribute('data-price'));
        
        // 3. Cộng tiền Topping (Topping cũng nhân theo số lượng món chính nếu muốn, 
        // nhưng theo logic code cũ của bạn là cộng dồn topping vào giá 1 món)
        let totalToppingPrice = 0;
        let topInputs = document.querySelectorAll('.input-qty-top');
        topInputs.forEach(inp => {
            let qty = parseInt(inp.value) || 0;
            let p = parseInt(inp.getAttribute('data-price')) || 0;
            totalToppingPrice += (qty * p); 
        });

        // 4. Tổng tiền = (Giá 1 món + Topping của 1 món) * Số lượng món chính
        // Hoặc: (Giá 1 món * SL) + (Topping * SL)
        // Code dưới đây: Tổng tiền hiển thị = (Giá Base + Size + Topping) * Số lượng Main
        let finalPrice = (oneItemPrice + totalToppingPrice) * mainQty;

        document.getElementById('opt-total-price').innerText = finalPrice.toLocaleString() + ' đ';
    }

    // --- 2. THÊM VÀO GIỎ (LOGIC TÁCH GIÁ) ---
    function confirmAddToCart(isBuyNow) {
        let id = currentProd.id;
        let maxStock = stockData[id] || 0;

        // --- BỔ SUNG: Lấy số lượng món chính từ input ---
        let mainQty = parseInt(document.getElementById('input-main-qty').value) || 1;

        // Tính tồn kho
        let currentQtyInCart = 0;
        for (let key in cart) { if (cart[key].id == id) currentQtyInCart += cart[key].quantity; }

        // Kiểm tra tồn kho với số lượng muốn thêm (mainQty)
        if (currentQtyInCart + mainQty > maxStock) {
            showToast(`⚠️ Không thể thêm! Kho chỉ còn ${maxStock}.`, 'error');
            return; 
        }

        // ... (Giữ nguyên phần lấy Size, Ice) ...
        let size = document.querySelector('input[name="opt_size"]:checked').value;
        let sizePrice = parseInt(document.querySelector('input[name="opt_size"]:checked').getAttribute('data-price'));
        let ice = document.querySelector('input[name="opt_ice"]:checked').value;
        
        // ... (Giữ nguyên phần tính Main Price và Topping) ...
        let mainItemPrice = currentProd.basePrice + sizePrice;

        let totalToppingPrice = 0; // Giá topping cho 1 phần
        let toppingArr = [];
        let toppingStrForKey = ""; 
        
        let topInputs = document.querySelectorAll('.input-qty-top');
        topInputs.forEach(inp => {
            let qty = parseInt(inp.value) || 0;
            let price = parseInt(inp.getAttribute('data-price')) || 0;
            if(qty > 0) {
                let name = inp.getAttribute('data-name');
                toppingArr.push(`${name} (x${qty})`);
                toppingStrForKey += `_${name}_${qty}`; 
                totalToppingPrice += (qty * price);
            }
        });
        
        // Tổng tiền topping cho TOÀN BỘ số lượng món chính (Để lưu vào fixedToppingPrice)
        let totalToppingAllParams = totalToppingPrice * mainQty;

        let uniqueKey = `${currentProd.id}_${size}_${ice}${toppingStrForKey}`;
        
        let note = `Size: ${size}, Đá: ${ice}`;
        if(toppingArr.length > 0) note += `, Topping: ${toppingArr.join(', ')}`;

        if (cart[uniqueKey]) {
            // Nếu món đã có: Tăng số lượng theo mainQty vừa chọn
            cart[uniqueKey].quantity += mainQty;
            cart[uniqueKey].fixedToppingPrice += totalToppingAllParams; 
        } else {
            // Món mới
            cart[uniqueKey] = {
                id: currentProd.id, 
                name: currentProd.name, 
                mainPrice: mainItemPrice, 
                fixedToppingPrice: totalToppingAllParams, 
                quantity: mainQty, // <-- SỬA Ở ĐÂY: Dùng mainQty thay vì số 1
                note: note
            };
        }

        updateCartBadge();
        closeOptionModal();
        if (isBuyNow) { renderCartModal(); showCheckoutModal(); } else { showToast(`Đã thêm: <b>${currentProd.name}</b>`, 'info'); }
    }

    // --- 3. RENDER GIỎ HÀNG (QUAN TRỌNG: CÔNG THỨC TÍNH TIỀN) ---
    // --- 3. RENDER GIỎ HÀNG (HIỂN THỊ CHI TIẾT GIÁ) ---
    function renderCartModal() {
        let body = document.getElementById('cart-body');
        let totalSpan = document.getElementById('cart-total-price');
        let grandTotal = 0;
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
            
            // Tính toán riêng lẻ
            let mainTotal = item.mainPrice * item.quantity; // Tiền món chính (Tăng theo SL)
            let toppingTotal = item.fixedToppingPrice;      // Tiền topping (Cố định)
            let itemTotal = mainTotal + toppingTotal;       // Tổng dòng này
            
            grandTotal += itemTotal;

            // Xử lý Note
            let displayNote = item.note;
            if(displayNote.includes("Topping:")) {
                // Ẩn topping khỏi dòng note vì đã có giá riêng, hoặc làm mờ đi
                displayNote = displayNote.replace("Topping:", "<br><span style='opacity:0.7'>+ Topping:</span>");
            }

            body.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-left">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-note" style="font-size: 13px; color: #666; margin-top: 4px; line-height: 1.4;">
                            ${displayNote}
                        </div>
                    </div>
                    
                    <div class="cart-item-right" style="align-items: flex-end;">
                        
                        <div style="text-align:right; font-size:12px; margin-bottom:5px; line-height:1.4;">
                            <div style="color:#28a745; font-weight:600;">
                                ${item.mainPrice.toLocaleString()} x <b style="font-size:14px; color:#000;">${item.quantity}</b> = ${mainTotal.toLocaleString()}
                            </div>
                            
                            ${toppingTotal > 0 ? `<div style="color:#666;">+ Topping: ${toppingTotal.toLocaleString()} (Cố định)</div>` : ''}
                            
                            <div style="border-top:1px solid #eee; margin-top:2px; padding-top:2px; font-weight:bold; color:#d32f2f;">
                                = ${itemTotal.toLocaleString()} đ
                            </div>
                        </div>

                        <div class="cart-actions">
                            <button class="btn-sm-qty" onclick="changeCartQty('${key}', -1)">-</button>
                            <input type="number" class="input-cart-qty" value="${item.quantity}" onchange="manualCartQty('${key}', this.value)">
                            <button class="btn-sm-qty" onclick="changeCartQty('${key}', 1)">+</button>
                        </div>
                        
                        <button class="btn-del-item" onclick="removeItem('${key}')">×</button>
                    </div>
                </div>`;
        }
        totalSpan.innerText = grandTotal.toLocaleString('vi-VN') + ' đ';
    }

    // --- 4. XỬ LÝ TĂNG GIẢM SỐ LƯỢNG & XÓA (CÓ MODAL) ---
    
    // Tăng giảm bằng nút
    function changeCartQty(key, delta) {
        if (!cart[key]) return;
        let newQty = cart[key].quantity + delta;
        
        if (newQty <= 0) {
            openDeleteModal(key); // Số lượng về 0 -> Hỏi xóa
        } else {
            checkAndSetQty(key, newQty);
        }
    }

    // Nhập số trực tiếp
    function manualCartQty(key, val) {
        let newQty = parseInt(val) || 0;
        if (newQty <= 0) {
            openDeleteModal(key); // Nhập 0 -> Hỏi xóa
        } else {
            checkAndSetQty(key, newQty);
        }
    }

    // Bấm nút X
    function removeItem(key) {
        openDeleteModal(key);
    }

    // Hàm kiểm tra tồn kho và cập nhật số lượng
    function checkAndSetQty(key, newQty) {
        let id = cart[key].id;
        let maxStock = stockData[id] || 0;
        
        // Tính tổng số lượng của món này trong giỏ (để check tồn kho)
        let otherQty = 0;
        for (let k in cart) { 
            if(cart[k].id == id && k !== key) otherQty += cart[k].quantity; 
        }

        if (otherQty + newQty > maxStock) {
            showToast(`⚠️ Hết hàng! Kho chỉ còn ${maxStock}.`, 'error');
            renderCartModal(); // Render lại để số lượng quay về cũ
            return;
        }
        
        cart[key].quantity = newQty;
        updateCartBadge();
        renderCartModal();
    }

    // --- LOGIC MODAL XÓA ---
    function openDeleteModal(key) {
        itemToDeleteKey = key;
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
        itemToDeleteKey = null;
        renderCartModal(); // Render lại để reset ô input nếu người dùng hủy xóa
    }

    function confirmDeleteAction() {
        if (itemToDeleteKey && cart[itemToDeleteKey]) {
            delete cart[itemToDeleteKey];
            updateCartBadge();
            renderCartModal();
            showToast('Đã xóa món khỏi giỏ', 'info');
        }
        closeDeleteModal();
    }

    // --- 5. THANH TOÁN (GỬI DỮ LIỆU CHUẨN ĐI) ---
    async function submitCheckoutProcess() {
        document.getElementById('checkoutConfirmModal').style.display = 'none';
        <?php if (!$can_sell): ?>showToast("⛔ <?php echo $lock_reason; ?>", 'error'); return;<?php endif; ?>
        
        // CHUẨN BỊ DỮ LIỆU GỬI ĐI
        // Vì Backend PHP thường tính: Total = Price * Quantity
        // Nhưng logic mới của ta là: Total = (Main * Qty) + Topping
        // => Ta phải tính ra một "Price ảo" (Effective Unit Price) để khi PHP nhân với Quantity sẽ ra đúng Total.
        // Effective Price = Total / Quantity
        
        let cartToSend = {};
        for (let key in cart) {
            let item = cart[key];
            let realTotal = (item.mainPrice * item.quantity) + item.fixedToppingPrice;
            
            // Tính giá trung bình để gửi cho PHP
            let effectivePrice = realTotal / item.quantity;

            cartToSend[key] = {
                id: item.id,
                price: effectivePrice, // Giá đã chia đều
                quantity: item.quantity,
                note: item.note
            };
        }

        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(cartToSend) // Gửi cart đã xử lý giá
            });
            const result = await response.json();
            if (result.success) { 
                showToast(result.message, 'success'); 
                cart = {}; 
                updateCartBadge(); 
            } else { 
                showToast(result.message, 'error'); 
            }
        } catch (error) { 
            showToast('Lỗi kết nối!', 'error'); 
        }
    }

    // --- CÁC HÀM CƠ BẢN KHÁC ---
    function updateCartBadge() {
        let count = 0;
        for (let key in cart) count += cart[key].quantity;
        document.getElementById('cart-badge').innerText = count;
    }

    function showCheckoutModal() {
        if (Object.keys(cart).length === 0) { showToast("Giỏ hàng trống!", 'error'); return; }
        let total = 0;
        for (let key in cart) {
            let item = cart[key];
            total += (item.mainPrice * item.quantity) + item.fixedToppingPrice;
        }
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