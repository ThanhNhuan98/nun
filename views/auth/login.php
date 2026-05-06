<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUN Express - Đăng nhập</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <style>
        /* Phong cách cơ bản */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F7F8FC; /* Màu nền trang */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Thanh điều hướng mờ phía trên */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 500;
            color: #2A59EB; /* Màu xanh của logo */
            text-decoration: none;
        }

        .navbar-back-icon {
            font-size: 20px;
            margin-right: 10px;
        }

        /* Vùng chính cho nội dung đăng nhập */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 80px; /* Bù cho navbar */
            padding-bottom: 100px; /* Khoảng cách cho hình nền */
            position: relative;
        }

        /* Thẻ Đăng nhập (Card) */
        .auth-card {
            background-color: #FFFFFF;
            width: 100%;
            max-width: 440px; /* Chiều rộng tối đa */
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); /* Bóng đổ nhẹ */
            z-index: 10;
        }

        .auth-card-title {
            font-size: 24px;
            font-weight: 700;
            color: #1A1A1A;
            text-align: center;
            margin-bottom: 10px;
        }

        .auth-card-desc {
            font-size: 14px;
            color: #666666;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        /* Kiểu dáng Input */
        .input-group {
            margin-bottom: 20px;
        }

        .auth-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #1A1A1A;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon-prefix {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #999999;
        }

        .auth-input {
            width: 100%;
            padding: 12px 14px 12px 42px; /* Chừa chỗ cho icon prefix */
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            background-color: #F7F8FC;
            color: #333333;
            transition: border-color 0.2s;
        }

        .auth-input:focus {
            outline: none;
            border-color: #2A59EB;
            background-color: #FFFFFF;
        }

        .auth-input::placeholder {
            color: #BDBDBD;
        }

        /* Xử lý mật khẩu */
        .input-icon-suffix {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #999999;
            cursor: pointer;
        }

        .forgot-password-link {
            font-size: 13px;
            color: #2A59EB;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            padding: 4px 8px;
            margin-right: -8px;
        }
        
        .alert-danger, .alert-success {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-danger {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
        }

        /* Nút đăng nhập chính */
        .auth-btn-primary {
            width: 100%;
            padding: 14px;
            background-color: #2A59EB;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .auth-btn-primary:hover {
            background-color: #1A49DA;
        }

        .auth-btn-icon {
            font-size: 18px;
        }

        /* Ngăn cách Hoặc */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #999999;
            font-size: 13px;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #E0E0E0;
        }

        .divider::before {
            margin-right: 15px;
        }

        .divider::after {
            margin-left: 15px;
        }

        /* Liên kết đăng ký */
        .register-text {
            text-align: center;
            font-size: 13px;
            color: #666666;
        }

        .register-link {
            color: #2A59EB;
            font-weight: 500;
            text-decoration: none;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        /* Hình nền nhà kho mờ ở phía dưới */
        .warehouse-background {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 300px; /* Chiều cao tối đa của hình nền */
            background-image: url('https://i.imgur.com/your-warehouse-image.jpg'); /* Đặt URL hình ảnh nhà kho của bạn ở đây */
            background-size: cover;
            background-position: center bottom;
            opacity: 0.15; /* Làm mờ */
            filter: blur(5px); /* Làm nhòe */
            pointer-events: none; /* Không che khuất các phần tử khác */
            z-index: 1;
        }
        
        /* Một gradient làm mờ dần lên trên để hòa trộn hình nền */
        .warehouse-gradient {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 400px;
            background: linear-gradient(to bottom, #F7F8FC 0%, rgba(247, 248, 252, 0) 50%, #F7F8FC 100%);
            z-index: 2;
            pointer-events: none;
        }

        /* Ghi đè CSS cho Material Symbols để khớp với thiết kế */
        .material-symbols-outlined {
            font-variation-settings:
            'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
    </style>
</head>
<script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.input-icon-suffix');
            const passwordInput = document.getElementById('login_password');

            togglePassword.addEventListener('click', function () {
                // Kiểm tra type hiện tại và chuyển đổi qua lại giữa 'password' và 'text'
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                this.textContent = type === 'password' ? 'visibility_off' : 'visibility';
            });
        });
    </script>
</body>
</html>
<body class="auth-body">

    <header class="navbar">
        <a href="\" class="navbar-brand">
            <span class="material-symbols-outlined navbar-back-icon">arrow_back_ios</span>
            NUN Express
        </a>
    </header>

    <main class="main-content">
        <div class="auth-card">
            <h2 class="auth-card-title">Chào mừng trở lại</h2>
            <p class="auth-card-desc">Đăng nhập để theo dõi đơn hàng, quản lý giao nhận.</p>

            <?php if (!empty($error)): ?>
                <div class="alert-danger">
                    <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($msg = app_flash('flash_success')): ?>
                <div class="alert-success">
                    <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($msg = app_flash('flash_error')): ?>
                <div class="alert-danger">
                    <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST">
                <div class="input-group">
                    <label class="auth-label" for="account">Email hoặc số điện thoại</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon-prefix">person_outline</span>
                        <input type="text" id="account" name="account" class="auth-input" placeholder="VD: nguyenvan@gmail.com" value="<?= htmlspecialchars($old['account'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 12px;">
                    <label class="auth-label" for="login_password">Mật khẩu</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon-prefix">lock_outline</span>
                        <input type="password" id="login_password" name="password" class="auth-input" placeholder="Nhập mật khẩu của bạn" required>
                        <span class="material-symbols-outlined input-icon-suffix">visibility_off</span>
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <a href="/forgot-password" class="forgot-password-link">Quên mật khẩu?</a>
                    </div>
                </div>

                <button type="submit" class="auth-btn-primary">
                    Đăng nhập
                    <span class="material-symbols-outlined auth-btn-icon">arrow_forward</span>
                </button>
            </form>

            <div class="divider">Hoặc đăng nhập bằng</div>

            

            <p class="register-text">
                Chưa có tài khoản? <a href="/register" class="register-link">Đăng ký ngay</a>
            </p>
        </div>

        <div class="warehouse-background"></div>
        <div class="warehouse-gradient"></div>

    </main>

</body>
</html>