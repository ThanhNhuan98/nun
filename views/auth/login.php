<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUN Express - Đăng nhập</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">

    
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