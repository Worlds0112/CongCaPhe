<?php
// Bắt đầu session để kiểm tra xem họ ĐÃ đăng nhập chưa
session_start();

// Nếu đã đăng nhập rồi, tự động chuyển hướng
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/product_list.php');
        exit();
    } else {
        header('Location: pos/pos.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 300px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Quan trọng để padding không làm vỡ layout */
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: red;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Đăng nhập</h2>
        
        <form action="login_process.php" method="POST">
            <div>
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
            </div>
            <div>
                <input type="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <button type="submit">Đăng nhập</button>
        </form>

        <?php
        // Hiển thị thông báo lỗi nếu bị chuyển về
        if (isset($_GET['error'])) {
            echo '<p class="error-message">Sai tên đăng nhập hoặc mật khẩu!</p>';
        }
        ?>
    </div>

</body>
</html>