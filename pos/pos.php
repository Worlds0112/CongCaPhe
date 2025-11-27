<?php
require '../includes/auth_pos.php'; 
require '../includes/header.php'; 

// Lấy danh sách món
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0 
        ORDER BY c.id ASC, p.name ASC";
$result = mysqli_query($conn, $sql);

$menu_data = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_data[$row['category_name']][] = $row; 
    }
}
?>

<style>
    main.content { padding: 0; max-width: none; margin-top: 65px; }
    
    .pos-container { 
        display: flex; width: 100%; height: calc(100vh - 65px); background-color: #f4f6f9;
    }
    .product-list-container {
        flex: 2; overflow-y: auto; padding: 20px;
    }
    .category-title {
        font-size: 18px; font-weight: bold; color: #5B743A; margin: 20px 0 10px 0;
        border-bottom: 2px solid #ddd;
    }
    .category-title:first-child { margin-top: 0; }
    .category-grid { display: flex; flex-wrap: wrap; gap: 15px; }
    
    .product-item {
        width: 140px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px;
        padding: 10px; text-align: center; cursor: pointer; 
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative; /* Để tạo hiệu ứng bấm */
    }
    .product-item:active { transform: scale(0.95); } /* Hiệu ứng nhấn nút */
    .product-item:hover { 
        transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #5B743A;
    }
    .product-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; margin-bottom: 8px; }
    .product-item h5 { 
        margin: 0; font-size: 14px; color: #333; 
        height: 34px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .product-item p { margin: 5px 0 0 0; font-weight: bold; color: #d32f2f; font-size: 14px; }
    
    /* --- CSS MỚI CHO GIỎ HÀNG --- */
    .cart-section {
        flex: 1; background: #fff; display: flex; flex-direction: column; border-left: 1px solid #ddd;
    }
    .cart-header {
        background: #333; color: white; padding: 15px; font-size: 18px; font-weight: bold; text-align: center;
    }
    .cart-items { flex-grow: 1; overflow-y: auto; padding: 10px; }
    
    /* Dòng sản phẩm trong giỏ */
    .cart-item {
        display: flex; flex-direction: column; /* Xếp dọc để tên ở trên */
        padding: 10px; border-bottom: 1px dashed #eee; font-size: 14px;
        background: #fafafa; border-radius: 5px; margin-bottom: 8px;
    }
    .cart-item-top {
        display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: 500;
    }
    .cart-item-controls {
        display: flex; justify-content: space-between; align-items: center;
    }
    
    /* Nút tăng giảm */
    .qty-group {
        display: flex; align-items: center; gap: 5px;
    }
    .btn-qty {
        width: 28px; height: 28px; border: 1px solid #ddd; background: #fff;
        border-radius: 4px; cursor: pointer; font-weight: bold; color: #333;
        display: flex; align-items: center; justify-content: center;
    }
    .btn-qty:hover { background: #eee; }
    .qty-display {
        width: 30px; text-align: center; font-weight: bold;
    }
    
    /* Nút xóa */
    .btn-remove {
        color: #dc3545; cursor: pointer; font-size: 18px; font-weight: bold;
        background: none; border: none; padding: 0 5px;
    }
    .btn-remove:hover { color: #a71d2a; }

    .cart-footer { padding: 20px; border-top: 2px solid #eee; background: #f9f9f9; }
    .cart-total {
        font-size: 20px; font-weight: bold; display: flex; justify-content: space-between;
        margin-bottom: 15px; color: #333;
    }
    #checkout-btn {
        width: 100%; padding: 15px; font-size: 16px; font-weight: bold; color: white;
        background: #28a745; border: none; border-radius: 6px;
        cursor: pointer; text-transform: uppercase; letter-spacing: 1px;
    }
    #checkout-btn:hover { background: #218838; }
</style>

<div class="pos-container">
    
    <div class="product-list-container">
        <?php
        if (!empty($menu_data)) {
            foreach ($menu_data as $cat_name => $products) {
                echo '<div class="category-title">' . htmlspecialchars($cat_name) . '</div>';
                echo '<div class="category-grid">';
                foreach ($products as $row) {
                    echo '<div class="product-item" onclick="addToCart(
                        ' . $row['id'] . ', 
                        \'' . htmlspecialchars(addslashes($row['name'])) . '\', 
                        ' . $row['price'] . '
                    )">';
                    echo '<img src="../admin/uploads/' . htmlspecialchars($row['image']) . '" alt="Img">';
                    echo '<h5>' . htmlspecialchars($row['name']) . '</h5>';
                    echo '<p>' . number_format($row['price']) . ' đ</p>';
                    echo '</div>'; 
                }
                echo '</div>'; 
            }
        } else {
            echo "<p style='padding:20px'>Chưa có sản phẩm nào.</p>";
        }
        ?>
    </div>
    
    <div class="cart-section">
        <div class="cart-header">Đơn hàng</div>
        <div id="cart-items" class="cart-items">
            <p style="text-align:center; color:#999; margin-top:20px">Chưa có món nào</p>
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span>TỔNG:</span>
                <span id="cart-total" style="color: #d32f2f;">0 đ</span>
            </div>
            <button id="checkout-btn" onclick="checkout()">THANH TOÁN</button>
        </div>
    </div>

</div>

<script>
    let cart = {}; 

    // 1. THÊM MÓN (MẶC ĐỊNH)
    function addToCart(id, name, price) {
        if (cart[id]) {
            cart[id].quantity++;
        } else {
            cart[id] = { name: name, price: price, quantity: 1 };
        }
        renderCart();
    }

    // 2. HÀM MỚI: GIẢM SỐ LƯỢNG
    function decreaseQty(id) {
        if (cart[id]) {
            cart[id].quantity--;
            // Nếu giảm xuống 0 thì xóa luôn
            if (cart[id].quantity <= 0) {
                delete cart[id];
            }
            renderCart();
        }
    }

    // 3. HÀM MỚI: TĂNG SỐ LƯỢNG (Dùng cho nút +)
    function increaseQty(id) {
        if (cart[id]) {
            cart[id].quantity++;
            renderCart();
        }
    }

    // 4. HÀM MỚI: XÓA HẲN MÓN
    function removeItem(id) {
        if (confirm('Xóa món này khỏi đơn?')) {
            delete cart[id];
            renderCart();
        }
    }

    // 5. VẼ GIỎ HÀNG (ĐÃ CẬP NHẬT GIAO DIỆN)
    function renderCart() {
        let cartItemsDiv = document.getElementById('cart-items');
        let cartTotalSpan = document.getElementById('cart-total');
        let totalAmount = 0;
        
        cartItemsDiv.innerHTML = ''; 

        if (Object.keys(cart).length === 0) {
            cartItemsDiv.innerHTML = '<p style="text-align:center; color:#999; margin-top:20px">Chưa có món nào</p>';
            cartTotalSpan.innerHTML = '0 đ';
            return;
        }

        for (let id in cart) {
            let item = cart[id];
            let itemTotal = item.price * item.quantity;
            totalAmount += itemTotal; 

            cartItemsDiv.innerHTML += `
                <div class="cart-item">
                    <div class="cart-item-top">
                        <span>${item.name}</span>
                        <span style="color:#5B743A">${itemTotal.toLocaleString('vi-VN')}</span>
                    </div>
                    <div class="cart-item-controls">
                        <div class="qty-group">
                            <button class="btn-qty" onclick="decreaseQty(${id})">-</button>
                            
                            <span class="qty-display">${item.quantity}</span>
                            
                            <button class="btn-qty" onclick="increaseQty(${id})">+</button>
                        </div>
                        
                        <button class="btn-remove" onclick="removeItem(${id})" title="Xóa món">×</button>
                    </div>
                </div>
            `;
        }
        cartTotalSpan.innerHTML = totalAmount.toLocaleString('vi-VN') + ' đ';
    }

    async function checkout() {
        if (Object.keys(cart).length === 0) {
            alert("Giỏ hàng đang trống!");
            return;
        }
        
        if (!confirm("Xác nhận thanh toán?")) return;

        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cart) 
            });
            const result = await response.json();
            
            if (result.success) {
                alert(result.message); 
                cart = {}; 
                renderCart(); 
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi kết nối.');
        }
    }
</script>

<?php
disconnect_db();
?>