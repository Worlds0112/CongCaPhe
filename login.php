<?php
// Bắt đầu session để kiểm tra xem họ ĐÃ đăng nhập chưa
session_start();

// Nếu đã đăng nhập rồi, tự động chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Cộng Cà Phê</title>
    <style>
        body { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
            position: relative; /* Để định vị nút back */
        }
        
        /* CSS CHO NÚT VỀ TRANG CHỦ */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #5B743A;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .back-home:hover {
            transform: translateX(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .back-home svg {
            width: 20px;
            height: 20px;
            fill: #5B743A;
        }

        /* CSS LOGIN CŨ */
        .login-container {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 1.5rem;
            color: #5B743A; 
            font-weight: bold;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #5B743A; 
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-container button:hover {
            background-color: #4a602f;
        }
        .error-message {
            color: #dc3545;
            margin-top: 1rem;
            font-size: 14px;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 4px;
        }
        .forgot-link {
            display: block;
            margin-top: 15px;
            text-align: right;
            font-size: 14px;
        }
        .forgot-link a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <a href="index.php" class="back-home">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        Trang chủ
    </a>

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

            <div class="forgot-link">
                <a href="forgot_password.php">Quên mật khẩu?</a>
            </div>

        </form>

        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error-message">Sai tên đăng nhập hoặc mật khẩu!</p>';
        }
        ?>
    </div>

</body>
</html>