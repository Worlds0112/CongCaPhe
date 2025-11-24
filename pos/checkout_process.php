<?php
// 1. BẢO VỆ FILE VÀ KẾT NỐI CSDL
require '../includes/auth_pos.php'; 

header('Content-Type: application/json');

// 2. NHẬN DỮ LIỆU JSON TỪ JAVASCRIPT
$json_data = file_get_contents('php://input');
$cart = json_decode($json_data, true); 

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: Giỏ hàng rỗng!']);
    exit();
}

try {
    // === BƯỚC NÂNG CAO 1: LẤY DỮ LIỆU TIN CẬY (GIÁ VÀ KHO) TỪ CSDL ===
    
    $product_ids = array_keys($cart);
    if (empty($product_ids)) {
        throw new Exception("Giỏ hàng không hợp lệ.");
    }
    
    // Chuẩn bị các dấu ? (ví dụ: '?, ?, ?')
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    // Chuẩn bị các kiểu (ví dụ: 'iii')
    $types = str_repeat('i', count($product_ids));
    
    // Lấy giá và kho của TẤT CẢ sản phẩm trong giỏ chỉ bằng 1 câu lệnh
    $sql_prices = "SELECT id, price, stock FROM products WHERE id IN ($placeholders)";
    $stmt_prices = mysqli_prepare($conn, $sql_prices);
    
    mysqli_stmt_bind_param($stmt_prices, $types, ...$product_ids);
    mysqli_stmt_execute($stmt_prices);
    
    $result_prices = mysqli_stmt_get_result($stmt_prices);
    $trusted_data = [];
    while ($row = mysqli_fetch_assoc($result_prices)) {
        $trusted_data[$row['id']] = $row; // Lưu vào mảng [id] => [thông tin]
    }
    mysqli_stmt_close($stmt_prices);

    // === BƯỚC NÂNG CAO 2: KIỂM TRA HÀNG VÀ TÍNH TỔNG TIỀN (AN TOÀN) ===
    
    $total_amount = 0;
    
    // Vòng lặp này để "KIỂM TRA TRƯỚC" (chưa lưu gì cả)
    foreach ($cart as $product_id => $item) {
        $product_id = (int)$product_id;
        $quantity_wanted = (int)$item['quantity'];

        // Kiểm tra xem có sản phẩm này trong CSDL không
        if (!isset($trusted_data[$product_id])) {
            throw new Exception("Sản phẩm không tồn tại: ID " . $product_id);
        }
        
        $stock_available = $trusted_data[$product_id]['stock'];
        
        // Kiểm tra xem số lượng muốn mua có hợp lệ không
        if ($quantity_wanted <= 0) {
            throw new Exception("Số lượng không hợp lệ.");
        }
        
        // KIỂM TRA TỒN KHO
        if ($quantity_wanted > $stock_available) {
            throw new Exception("Không đủ hàng! Món hàng (ID: $product_id) chỉ còn $stock_available sản phẩm.");
        }
        
        // TÍNH TỔNG TIỀN (Dùng giá từ CSDL, không dùng giá từ JS)
        $real_price = (int)$trusted_data[$product_id]['price'];
        $total_amount += $real_price * $quantity_wanted;
    }
    
    // === BƯỚC 3: XỬ LÝ TRANSACTION (Nếu mọi thứ ở trên OK) ===
    
    $user_id = $_SESSION['user_id'];
    mysqli_begin_transaction($conn);

    // BƯỚC 3A: LƯU VÀO BẢNG `orders`
    $sql_order = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'paid')";
    $stmt_order = mysqli_prepare($conn, $sql_order);
    // Dùng $total_amount đã được tính toán an toàn
    mysqli_stmt_bind_param($stmt_order, "ii", $user_id, $total_amount); 
    
    if (!mysqli_stmt_execute($stmt_order)) {
        throw new Exception("Lỗi khi tạo hóa đơn: " . mysqli_stmt_error($stmt_order));
    }
    
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_order);

    // BƯỚC 3B: LƯU VÀO `order_details` VÀ TRỪ KHO `products`
    $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt_detail = mysqli_prepare($conn, $sql_detail);
    
    // === BƯỚC NÂNG CAO 3: MỞ KHÓA CHỨC NĂNG TRỪ KHO ===
    $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
    $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);
    
    foreach ($cart as $product_id => $item) {
        $product_id = (int)$product_id;
        $qty = (int)$item['quantity'];
        // Lấy lại giá thật từ mảng tin cậy
        $price = (int)$trusted_data[$product_id]['price'];
        
        // Thêm vào chi tiết hóa đơn (dùng giá thật)
        mysqli_stmt_bind_param($stmt_detail, "iiii", 
            $order_id, 
            $product_id, 
            $qty, 
            $price
        );
        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Lỗi khi lưu chi tiết hóa đơn.");
        }
        
        // TRỪ TỒN KHO
        mysqli_stmt_bind_param($stmt_update_stock, "ii", $qty, $product_id);
        if (!mysqli_stmt_execute($stmt_update_stock)) {
            throw new Exception("Lỗi khi cập nhật kho.");
        }
    }
    mysqli_stmt_close($stmt_detail);
    mysqli_stmt_close($stmt_update_stock);

    // 4. MỌI THỨ OK -> COMMIT
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thanh toán thành công! Mã hóa đơn: ' . $order_id
    ]);

} catch (Exception $e) {
    // 5. CÓ LỖI XẢY RA -> ROLLBACK
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Thanh toán thất bại: ' . $e->getMessage()
    ]);
}

// 6. ĐÓNG KẾT NỐI CSDL
disconnect_db();
?>