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
    $product_ids = [];
    foreach ($cart as $key => $item) {
        $product_ids[] = (int)$item['id'];
    }
    $product_ids = array_unique($product_ids);

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

    // === BƯỚC 3: KIỂM TRA TỒN KHO TỔNG HỢP (MÓN CHÍNH) ===
    $total_qty_needed = []; 
    $total_amount = 0;

    foreach ($cart as $key => $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['quantity'];
        
        if (!isset($trusted_data[$pid])) throw new Exception("Sản phẩm không tồn tại: ID " . $pid);
        if ($qty <= 0) throw new Exception("Số lượng không hợp lệ.");

        // Cộng dồn nhu cầu món chính
        if (!isset($total_qty_needed[$pid])) $total_qty_needed[$pid] = 0;
        $total_qty_needed[$pid] += $qty;

        // Tính tổng tiền
        $total_amount += $item['price'] * $qty;
    }

    // Check kho món chính
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

    // B. Chuẩn bị các câu lệnh (Prepared Statements)
    $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price, note) VALUES (?, ?, ?, ?, ?)";
    $stmt_detail = mysqli_prepare($conn, $sql_detail);
    
    $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
    $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);

    // --- MỚI: Câu lệnh tìm ID Topping theo tên ---
    $sql_find_topping = "SELECT id FROM products WHERE name = ? LIMIT 1";
    $stmt_find_topping = mysqli_prepare($conn, $sql_find_topping);
    // ---------------------------------------------

    $today_str = date('Y-m-d H:i:s');

    foreach ($cart as $key => $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $price = (int)$item['price'];
        $note = isset($item['note']) ? $item['note'] : ''; 

        // 1. Lưu Order Detail
        mysqli_stmt_bind_param($stmt_detail, "iiiis", $order_id, $pid, $qty, $price, $note);
        if (!mysqli_stmt_execute($stmt_detail)) throw new Exception("Lỗi lưu chi tiết.");
        
        // 2. Trừ Kho Món Chính
        mysqli_stmt_bind_param($stmt_update_stock, "ii", $qty, $pid);
        if (!mysqli_stmt_execute($stmt_update_stock)) throw new Exception("Lỗi trừ kho món chính.");

        // 3. Ghi Lịch Sử Món Chính
        $qty_export = -1 * $qty;
        $hist_note = "Bán hàng - Đơn #$order_id";
        mysqli_query($conn, "INSERT INTO inventory_history (product_id, quantity, note, created_at) VALUES ('$pid', '$qty_export', '$hist_note', '$today_str')");

        // --- MỚI: XỬ LÝ TRỪ KHO TOPPING DỰA TRÊN NOTE ---
        // Chuỗi note có dạng: "Size: M, Đá: 100%, Topping: Hạt hướng dương, Trân châu đen"
        if (!empty($note) && strpos($note, 'Topping:') !== false) {
            // Tách lấy phần sau chữ "Topping:"
            $parts = explode('Topping:', $note);
            if (isset($parts[1])) {
                $topping_list_str = $parts[1]; // Ví dụ: " Hạt hướng dương, Trân châu đen"
                $toppings = explode(',', $topping_list_str); // Tách thành mảng các tên

                foreach ($toppings as $top_name) {
                    $top_name = trim($top_name); // Xóa khoảng trắng thừa
                    if (empty($top_name)) continue;

                    // Tìm ID của topping trong CSDL
                    mysqli_stmt_bind_param($stmt_find_topping, "s", $top_name);
                    mysqli_stmt_execute($stmt_find_topping);
                    $res_top = mysqli_stmt_get_result($stmt_find_topping);
                    $row_top = mysqli_fetch_assoc($res_top);

                    if ($row_top) {
                        $tid = $row_top['id'];
                        
                        // Trừ kho Topping (Số lượng topping trừ theo số lượng ly nước)
                        mysqli_stmt_bind_param($stmt_update_stock, "ii", $qty, $tid);
                        mysqli_stmt_execute($stmt_update_stock);

                        // Ghi lịch sử Topping
                        $h_note_top = "Topping (kèm đơn #$order_id)";
                        mysqli_query($conn, "INSERT INTO inventory_history (product_id, quantity, note, created_at) VALUES ('$tid', '$qty_export', '$h_note_top', '$today_str')");
                    }
                }
            }
        }
        // ----------------------------------------------------
    }

    mysqli_stmt_close($stmt_detail);
    mysqli_stmt_close($stmt_update_stock);
    mysqli_stmt_close($stmt_find_topping);

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