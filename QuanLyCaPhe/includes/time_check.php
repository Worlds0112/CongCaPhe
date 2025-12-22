<?php
// Đặt múi giờ Việt Nam để lấy giờ chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

function is_working_hour($user_shift) {
    // 1. Nếu là Admin hoặc Full ca -> Luôn cho phép
    if ($user_shift == 'full' || $user_shift == 'admin') {
        return true;
    }

    // 2. Lấy giờ hiện tại (0 - 23)
    $current_hour = (int)date('G'); 

    // 3. Định nghĩa khung giờ (Bạn có thể sửa lại theo ý quán)
    // Sáng: 6h - 14h (tức là < 14)
    // Chiều: 14h - 22h (tức là >= 14 và < 22)
    // Tối: 18h - 23h (Ví dụ ca gãy)
    
    switch ($user_shift) {
        case 'sang':
            // Từ 6:00 đến 13:59
            if ($current_hour >= 6 && $current_hour < 12) return true;
            break;
            
        case 'chieu':
            // Từ 14:00 đến 21:59
            if ($current_hour >= 12 && $current_hour < 18) return true;
            break;
            
        case 'toi':
            // Từ 18:00 đến 23:00
            if ($current_hour >= 18 && $current_hour <= 23) return true;
            break;
            
        default:
            return false; // Ca không xác định thì chặn
    }

    return false; // Không đúng giờ
}

function get_shift_name($code) {
    switch ($code) {
        case 'sang': return "Ca Sáng (6h-12h)";
        case 'chieu': return "Ca Chiều (14h-18h)";
        case 'toi': return "Ca Tối (18h-23h)";
        case 'full': return "Toàn thời gian";
        default: return "Chưa phân ca";
    }
}
?>