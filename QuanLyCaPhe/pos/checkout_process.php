<?php
// 1. BẢO VỆ FILE VÀ KẾT NỐI CSDL
require '../includes/auth_pos.php';
require '../includes/time_check.php'; 

header('Content-Type: application/json');

$uid = $_SESSION['user_id'];
$q_check = mysqli_query($conn, "SELECT shift FROM users WHERE id = $uid");
$user_data = mysqli_fetch_assoc($q_check);

// Kiểm tra giờ làm việc
if (!is_working_hour($user_data['shift'])) {
    echo json_encode(['success' => false, 'message' => 'LỖI: Bạn không thể thanh toán ngoài ca làm việc!']);
    exit(); 
}

// 2. NHẬN DỮ LIỆU JSON
$json_data = file_get_contents('php://input');
$cart = json_decode($json_data, true); 

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: Giỏ hàng rỗng!']);
    exit();
}

try {
    // === BƯỚC 1: LẤY DANH SÁCH ID SẢN PHẨM ===
    // (Vì key giỏ hàng giờ là chuỗi phức tạp '47_L_itda', ta cần tách lấy ID gốc để check kho)
    $product_ids = [];
    foreach ($cart as $key => $item) {
        $product_ids[] = (int)$item['id']; // Lấy ID gốc từ object item
    }
    $product_ids = array_unique($product_ids); // Loại bỏ trùng lặp

    if (empty($product_ids)) throw new Exception("Giỏ hàng không hợp lệ.");
    
    // === BƯỚC 2: LẤY DỮ LIỆU KHO TỪ DB ===
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    
    $sql_prices = "SELECT id, price, stock, name FROM products WHERE id IN ($placeholders)";
    $stmt_prices = mysqli_prepare($conn, $sql_prices);
    mysqli_stmt_bind_param($stmt_prices, $types, ...$product_ids);
    mysqli_stmt_execute($stmt_prices);
    
    $result_prices = mysqli_stmt_get_result($stmt_prices);
    $trusted_data = [];
    while ($row = mysqli_fetch_assoc($result_prices)) {
        $trusted_data[$row['id']] = $row; 
    }
    mysqli_stmt_close($stmt_prices);

    // === BƯỚC 3: KIỂM TRA TỒN KHO TỔNG HỢP ===
    // (Vì 1 món có thể xuất hiện nhiều lần với size khác nhau, ta cần cộng dồn số lượng trước khi check kho)
    $total_qty_needed = []; 
    $total_amount = 0;

    foreach ($cart as $key => $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['quantity'];
        
        if (!isset($trusted_data[$pid])) throw new Exception("Sản phẩm không tồn tại: ID " . $pid);
        if ($qty <= 0) throw new Exception("Số lượng không hợp lệ.");

        // Cộng dồn nhu cầu
        if (!isset($total_qty_needed[$pid])) $total_qty_needed[$pid] = 0;
        $total_qty_needed[$pid] += $qty;

        // Tính tổng tiền (Dùng giá từ Frontend gửi lên vì giá này đã bao gồm Size/Topping)
        // Lưu ý: Trong thực tế nên tính lại giá Size/Topping từ DB để bảo mật tuyệt đối, 
        // nhưng ở mức độ đồ án này thì tin tưởng giá từ JS gửi lên là chấp nhận được.
        $total_amount += $item['price'] * $qty;
    }

    // Check kho tổng
    foreach ($total_qty_needed as $pid => $needed) {
        $available = $trusted_data[$pid]['stock'];
        if ($needed > $available) {
            throw new Exception("Không đủ hàng! Món '{$trusted_data[$pid]['name']}' chỉ còn $available (Bạn cần $needed).");
        }
    }
    
    // === BƯỚC 4: XỬ LÝ TRANSACTION ===
    mysqli_begin_transaction($conn);

    // A. Tạo Hóa Đơn
    $user_id = $_SESSION['user_id'];
    $sql_order = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'paid')";
    $stmt_order = mysqli_prepare($conn, $sql_order);
    mysqli_stmt_bind_param($stmt_order, "ii", $user_id, $total_amount); 
    
    if (!mysqli_stmt_execute($stmt_order)) throw new Exception("Lỗi tạo hóa đơn.");
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_order);

    // B. Lưu Chi Tiết & Trừ Kho & Ghi Lịch Sử
    $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price, note) VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = mysqli_prepare($conn, $sql_detail);
    
    $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
    $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);

    $today_str = date('Y-m-d H:i:s');

    foreach ($cart as $key => $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $price = (int)$item['price']; // Giá đã gồm topping
        $note = $item['note']; // Ghi chú: Size L, Ít đá...

        // Lưu Order Detail
        mysqli_stmt_bind_param($stmt_detail, "iiiis", $order_id, $pid, $qty, $price, $note);
        if (!mysqli_stmt_execute($stmt_detail)) throw new Exception("Lỗi lưu chi tiết.");
        
        // Trừ Kho
        mysqli_stmt_bind_param($stmt_update_stock, "ii", $qty, $pid);
        if (!mysqli_stmt_execute($stmt_update_stock)) throw new Exception("Lỗi trừ kho.");

        // Ghi Lịch Sử Xuất Kho (QUAN TRỌNG)
        // Số lượng xuất là số âm
        $qty_export = -1 * $qty;
        $hist_note = "Bán hàng - Đơn #$order_id";
        // Do query này đơn giản nên chạy trực tiếp
        mysqli_query($conn, "INSERT INTO inventory_history (product_id, quantity, note, created_at) VALUES ('$pid', '$qty_export', '$hist_note', '$today_str')");
    }

    mysqli_stmt_close($stmt_detail);
    mysqli_stmt_close($stmt_update_stock);

    // 5. COMMIT
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thanh toán thành công! Đơn #' . $order_id
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false, 
        'message' => 'Thất bại: ' . $e->getMessage()
    ]);
}

disconnect_db();
?>