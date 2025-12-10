<?php
// File: includes/auto_shift_check.php

if (!isset($conn)) {
    // Đảm bảo có kết nối DB nếu file này được gọi lẻ
    
    if (function_exists('connect_db')) $conn = connect_db();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
$now_timestamp = time();
$today_str = date('Y-m-d');

// 1. ĐỊNH NGHĨA CÁC MỐC GIỜ KẾT THÚC CA TRONG NGÀY
// Bạn có thể sửa giờ tại đây
$shift_end_points = [
    'sang'  => strtotime("$today_str 12:00:00"), // Ca sáng kết thúc 12h
    'chieu' => strtotime("$today_str 18:00:00"), // Ca chiều kết thúc 18h
    'toi'   => strtotime("$today_str 23:00:00")  // Ca tối kết thúc 23h
];

// 2. LẤY THỜI ĐIỂM BÁO CÁO GẦN NHẤT
$sql_last = "SELECT created_at, report_date FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last);
$r_last = mysqli_fetch_assoc($q_last);

// Nếu chưa có báo cáo nào thì mốc bắt đầu là đầu ngày hôm nay
$last_report_time = $r_last ? strtotime($r_last['created_at']) : strtotime("$today_str 00:00:00");

// 3. DUYỆT QUA CÁC CA ĐÃ QUA ĐỂ KIỂM TRA
foreach ($shift_end_points as $shift_code => $end_time) {
    
    // Chỉ kiểm tra các ca nằm trong khoảng thời gian từ (Báo cáo cuối) -> (Hiện tại)
    // Và ca đó phải kết thúc rồi
    if ($end_time > $last_report_time && $end_time < $now_timestamp) {
        
        // KIỂM TRA ĐIỀU KIỆN: ĐÃ QUÁ 1 TIẾNG CHƯA? (3600 giây)
        if (($now_timestamp - $end_time) >= 3600) {
            
            // a. Xác định khoảng thời gian của ca bị quên
            // Bắt đầu từ mốc báo cáo gần nhất (hoặc mốc kết thúc ca liền trước)
            $calc_start = date('Y-m-d H:i:s', $last_report_time);
            $calc_end   = date('Y-m-d H:i:s', $end_time);

            // b. Tính doanh thu của ca bị quên này
            $sql_check = "SELECT SUM(total_amount) as total FROM orders 
                          WHERE order_date > '$calc_start' AND order_date <= '$calc_end'";
            $r_check = mysqli_fetch_assoc(mysqli_query($conn, $sql_check));
            $revenue = $r_check['total'] ?? 0;

            // c. XỬ LÝ THEO YÊU CẦU CỦA BẠN
            if ($revenue > 0) {
                // TRƯỜNG HỢP 1: CÓ DOANH THU -> TỰ ĐỘNG CHỐT
                // User ID = 0 (để đánh dấu là Hệ thống tự chốt)
                // Real cash = Revenue (coi như đủ tiền vì không ai đếm)
                // Difference = 0
                
                $note = "Hệ thống tự động chốt do nhân viên quên quá 1 tiếng.";
                $date_report = date('Y-m-d', $end_time);
                
                $sql_insert = "INSERT INTO shift_reports 
                (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes, created_at) 
                VALUES 
                (0, '$shift_code', '$date_report', '$revenue', '$revenue', 0, '$note', '$calc_end')";
                
                mysqli_query($conn, $sql_insert);
                
                // Cập nhật lại mốc thời gian báo cáo cuối để vòng lặp sau tính tiếp
                $last_report_time = $end_time; 

            } else {
                // TRƯỜNG HỢP 2: KHÔNG CÓ DOANH THU -> KHÔNG CẦN TỔNG KẾT
                // Ta không làm gì cả. 
                // Ở lần tính toán tiếp theo (hoặc ca hiện tại), hệ thống sẽ tính từ $last_report_time cũ.
                // Doanh thu = 0 + Doanh thu ca sau = Doanh thu ca sau (ĐÚNG)
            }
        }
    }
}
?>