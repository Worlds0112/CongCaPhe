<?php
// File: includes/auto_shift_check.php

if (!isset($conn)) {
    if (function_exists('connect_db')) $conn = connect_db();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
$now_timestamp = time();
$today_str = date('Y-m-d');

// 1. ĐỊNH NGHĨA GIỜ KẾT THÚC
$shift_end_points = [
    'sang'  => strtotime("$today_str 12:00:00"), 
    'chieu' => strtotime("$today_str 18:00:00"), 
    'toi'   => strtotime("$today_str 23:00:00")  
];

// 2. LẤY MỐC THỜI GIAN BÁO CÁO GẦN NHẤT
$sql_last = "SELECT created_at FROM shift_reports ORDER BY id DESC LIMIT 1";
$q_last = mysqli_query($conn, $sql_last);
$r_last = mysqli_fetch_assoc($q_last);
$last_report_time = $r_last ? strtotime($r_last['created_at']) : strtotime("$today_str 00:00:00");

// 3. DUYỆT CÁC CA ĐỂ KIỂM TRA
foreach ($shift_end_points as $shift_code => $end_time) {
    
    // Nếu ca này nằm trong khoảng chưa chốt và đã kết thúc
    if ($end_time > $last_report_time && $end_time < $now_timestamp) {
        
        // SỬA: GIẢM XUỐNG 15 PHÚT (900 GIÂY)
        if (($now_timestamp - $end_time) >= 900) {
            
            $calc_start = date('Y-m-d H:i:s', $last_report_time);
            $calc_end   = date('Y-m-d H:i:s', $end_time);

            // Tính doanh thu
            $sql_check = "SELECT SUM(total_amount) as total FROM orders 
                          WHERE order_date > '$calc_start' AND order_date <= '$calc_end'";
            $r_check = mysqli_fetch_assoc(mysqli_query($conn, $sql_check));
            $revenue = $r_check['total'] ?? 0;

            if ($revenue > 0) {
                // SỬA: TÌM NHÂN VIÊN BÁN ĐƠN CUỐI CÙNG TRONG CA
                $sql_user = "SELECT user_id FROM orders 
                             WHERE order_date > '$calc_start' AND order_date <= '$calc_end' 
                             ORDER BY id DESC LIMIT 1";
                $q_user = mysqli_query($conn, $sql_user);
                $r_user = mysqli_fetch_assoc($q_user);
                
                // Nếu tìm thấy nhân viên thì lấy ID, không thì để 0 (Hệ thống)
                $staff_id = $r_user ? $r_user['user_id'] : 0;
                
                $note = "Hệ thống tự động chốt (Quá hạn 15p). NV cuối cùng bán hàng.";
                $date_report = date('Y-m-d', $end_time);
                
                // Insert báo cáo
                $sql_insert = "INSERT INTO shift_reports 
                (user_id, shift_code, report_date, system_revenue, real_cash, difference, notes, created_at) 
                VALUES 
                ('$staff_id', '$shift_code', '$date_report', '$revenue', '$revenue', 0, '$note', '$calc_end')";
                
                mysqli_query($conn, $sql_insert);
                
                // Cập nhật mốc thời gian để vòng lặp sau tính tiếp
                $last_report_time = $end_time; 

            } else {
                // Không có doanh thu -> Bỏ qua, không chốt
                // (Ca sau sẽ gộp khoảng thời gian này vào để tính tiếp)
            }
        }
    }
}
?>