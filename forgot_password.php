<?php
session_start();
require 'includes/connect.php';
require 'includes/disconnect.php';

$message = "";
$step = 1; // Mặc định là Bước 1 (Xác minh)
$user_id_found = 0;

// XỬ LÝ KHI FORM ĐƯỢC GỬI ĐI
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    connect_db();

    // --- XỬ LÝ BƯỚC 1: KIỂM TRA TÀI KHOẢN & MÃ BẢO MẬT ---
    if (isset($_POST['check_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $security_code = mysqli_real_escape_string($conn, $_POST['security_code']);

        // Kiểm tra xem có user nào khớp cả tên và mã không
        $sql = "SELECT id FROM users WHERE username = ? AND security_code = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $security_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Đúng rồi! Chuyển sang bước 2
            $step = 2;
            $user_id_found = $row['id'];
        } else {
            $message = "Sai Tên đăng nhập hoặc Mã bảo mật!";
        }
        mysqli_stmt_close($stmt);
    }

    // --- XỬ LÝ BƯỚC 2: ĐỔI MẬT KHẨU ---
    if (isset($_POST['change_pass'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $uid = (int)$_POST['user_id_hidden'];

        if ($new_pass === $confirm_pass) {
            // Mã hóa mật khẩu mới
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $uid);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Đổi mật khẩu thành công! <a href='login.php'>Đăng nhập ngay</a>";
                $step = 3; // Bước 3: Hoàn tất
            } else {
                $message = "Lỗi hệ thống: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Mật khẩu nhập lại không khớp!";
            $step = 2; // Quay lại bước 2 để nhập lại
            $user_id_found = $uid;
        }
    }
    
    disconnect_db();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <style>
        body { 
            font-family: Arial, sans-serif; background-color: #f4f6f9; 
            display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;
        }
        .container {
            background-color: #fff; padding: 2.5rem; border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 350px; text-align: center;
        }
        h2 { margin-bottom: 1.5rem; color: #333; }
        .form-group { margin-bottom: 1rem; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; color: #555; }
        input {
            width: 100%; padding: 10px; 
            border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;
        }
        button {
            width: 100%; padding: 10px; background-color: #007bff;
            border: none; border-radius: 5px; color: white; cursor: pointer; font-size: 16px; font-weight: bold;
        }
        button:hover { background-color: #0056b3; }
        .message { 
            padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;
        }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .back-link { display: block; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Khôi phục mật khẩu</h2>

        <?php if ($message != ""): ?>
            <div class="message <?php echo ($step==3)?'success':'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập:</label>
                    <input type="text" name="username" required placeholder="Ví dụ: admin">
                </div>
                <div class="form-group">
                    <label>Mã bảo mật (được cấp):</label>
                    <input type="password" name="security_code" required placeholder="Nhập mã bảo mật">
                </div>
                <button type="submit" name="check_user">Tiếp tục</button>
            </form>
            <a href="login.php" class="back-link">Quay về trang Đăng nhập</a>
        <?php endif; ?>

        <?php if ($step == 2): ?>
            <form action="" method="POST">
                <input type="hidden" name="user_id_hidden" value="<?php echo $user_id_found; ?>">
                
                <div class="form-group">
                    <label>Mật khẩu mới:</label>
                    <input type="password" name="new_password" required placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-group">
                    <label>Nhập lại mật khẩu:</label>
                    <input type="password" name="confirm_password" required placeholder="Xác nhận mật khẩu">
                </div>
                <button type="submit" name="change_pass" style="background-color: #28a745;">Đổi mật khẩu</button>
            </form>
            <a href="forgot_password.php" class="back-link">Hủy bỏ</a>
        <?php endif; ?>

        <?php if ($step == 3): ?>
            <?php endif; ?>

    </div>

</body>
</html>