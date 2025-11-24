<?php
// 1. BẢO VỆ TRANG (File này tự động session_start() và connect_db())
require '../includes/auth_pos.php'; 

// 2. GỌI HEADER CHUNG (File này sẽ in ra <head>, <body>, và <header>)
// Header này đã có link "Cộng" và "Trang chủ" để quay về
require '../includes/header.php'; 

// 3. LẤY SẢN PHẨM (Biến $conn đã có từ auth_pos.php)
$sql = "SELECT * FROM products WHERE stock > 0 ORDER BY category_id, name";
$result_products = mysqli_query($conn, $sql);
?>

<style>
    /* Đè CSS của header.php: Bỏ padding/max-width của .content */
    main.content {
        padding: 0;
        max-width: none;
    }

    /* Giữ nguyên CSS cũ của pos.php (bỏ .header-bar) */
    .pos-container { 
        display: flex; 
        width: 100%; 
        /* Tính toán chiều cao: 100% viewport TRỪ đi 80px của header */
        /* (Giả sử header cao 80px, bạn có thể chỉnh lại) */
        height: calc(100vh - 80px); 
    }
    .product-list {
        flex: 2; overflow-y: auto; padding: 15px;
        display: flex; flex-wrap: wrap; gap: 10px;
        background: #f4f6f9; /* Thêm nền cho cột trái */
    }
    .product-item {
        width: 120px; border: 1px solid #ddd; border-radius: 5px;
        padding: 10px; background: #fff; text-align: center;
        cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .product-item:hover { background: #e9f5ff; }
    .product-item img { width: 80px; height: 80px; object-fit: cover; }
    .product-item h5 { margin: 5px 0 0 0; font-size: 14px; }
    .product-item p { margin: 5px 0; font-size: 13px; color: #007bff; }
    
    .cart-section {
        flex: 1; background: #fff; padding: 20px;
        box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        display: flex; flex-direction: column;
    }
    .cart-header {
        font-size: 20px; font-weight: bold;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px; margin-bottom: 15px;
    }
    .cart-items { flex-grow: 1; overflow-y: auto; }
    .cart-item {
        display: flex; justify-content: space-between;
        padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 14px;
    }
    .cart-item-name { flex-grow: 1; }
    .cart-item-qty { margin: 0 10px; }
    .cart-item-price { min-width: 70px; text-align: right; }
    .cart-total {
        border-top: 2px solid #333; margin-top: 15px; padding-top: 15px;
        font-size: 22px; font-weight: bold;
        display: flex; justify-content: space-between;
    }
    #checkout-btn {
        width: 100%; padding: 15px; font-size: 18px; color: white;
        background: #28a745; border: none; border-radius: 5px;
        cursor: pointer; margin-top: 15px;
    }
    #checkout-btn:hover { background: #218838; }
</style>

<div class="pos-container">
    
    <div class="product-list">
        <?php
        if (mysqli_num_rows($result_products) > 0) {
            while ($row = mysqli_fetch_assoc($result_products)) {
                echo '<div class="product-item" onclick="addToCart(
                    ' . $row['id'] . ', 
                    \'' . htmlspecialchars(addslashes($row['name'])) . '\', 
                    ' . $row['price'] . '
                )">';
                
                // ĐƯỜNG DẪN ẢNH ĐÚNG (đi ra pos/, vào admin/, vào uploads/)
                echo '<img src="../admin/uploads/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                
                echo '<h5>' . htmlspecialchars($row['name']) . '</h5>';
                echo '<p>' . number_format($row['price']) . ' đ</p>';
                echo '</div>';
            }
        } else {
            echo "Không có sản phẩm nào.";
        }
        ?>
    </div>
    
    <div class="cart-section">
        <div class="cart-header">Đơn hàng hiện tại</div>
        <div id="cart-items" class="cart-items"></div>
        <div class="cart-total">
            <span>TỔNG CỘNG:</span>
            <span id="cart-total">0 đ</span>
        </div>
        <button id="checkout-btn" onclick="checkout()">THANH TOÁN</button>
    </div>

</div>

<script>
    let cart = {}; 

    function addToCart(id, name, price) {
        if (cart[id]) {
            cart[id].quantity++;
        } else {
            cart[id] = { name: name, price: price, quantity: 1 };
        }
        renderCart();
    }

    function renderCart() {
        let cartItemsDiv = document.getElementById('cart-items');
        let cartTotalSpan = document.getElementById('cart-total');
        let totalAmount = 0;
        
        cartItemsDiv.innerHTML = ''; 

        for (let id in cart) {
            let item = cart[id];
            let itemTotal = item.price * item.quantity;
            totalAmount += itemTotal; 

            cartItemsDiv.innerHTML += `
                <div class="cart-item">
                    <span class="cart-item-name">${item.name}</span>
                    <span class="cart-item-qty">${item.quantity}</span>
                    <span class="cart-item-price">${itemTotal.toLocaleString('vi-VN')} đ</span>
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
        try {
            const response = await fetch('checkout_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cart) 
            });
            const result = await response.json();
            alert(result.message); 
            if (result.success) {
                cart = {}; 
                renderCart(); 
            }
        } catch (error) {
            console.error('Lỗi:', error);
            alert('Đã xảy ra lỗi khi thanh toán.');
        }
    }
</script>

<?php
// 7. GỌI FOOTER CHUNG (để đóng </body></html>)
require '../includes/footer.php'; 

// 8. ĐÓNG KẾT NỐI CSDL
disconnect_db();
?>