<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Đăng ký tài khoản mới') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">

    
</head>
<body>
    <?= app_render_toast(); ?>

    <div class="auth-card">
        <h2 class="auth-card-title">Tạo tài khoản mới</h2>
        <p class="auth-card-desc">Tham gia NUN Express để trải nghiệm dịch vụ giao hàng nhanh chóng.</p>

        <?php if (!empty($errors)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php foreach ($errors as $error): ?>
                    if (typeof showToast === 'function') showToast('<?= app_e($error) ?>', 'error');
                    <?php endforeach; ?>
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="/register" enctype="multipart/form-data">
            <div class="input-group">
                <label class="auth-label" for="name">Họ và tên <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">person_outline</span>
                    <input type="text" id="name" name="name" class="auth-input" placeholder="VD: Nguyễn Văn A" required value="<?= app_e($old['name'] ?? '') ?>" oninvalid="this.setCustomValidity('Vui lòng nhập họ và tên.')" oninput="this.setCustomValidity('')">
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="phone">Số điện thoại <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">call</span>
                    <input type="text" id="phone" name="phone" class="auth-input" placeholder="09xx..." required value="<?= app_e($old['phone'] ?? '') ?>" oninvalid="this.setCustomValidity('Vui lòng nhập số điện thoại.')" oninput="this.setCustomValidity('')">
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="email">Email</label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">mail</span>
                    <input type="email" id="email" name="email" class="auth-input" placeholder="VD: a@gmail.com" value="<?= app_e($old['email'] ?? '') ?>">
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="password">Mật khẩu <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_outline</span>
                    <input type="password" id="password" name="password" class="auth-input password-field" placeholder="Nhập mật khẩu" required oninvalid="this.setCustomValidity('Vui lòng nhập mật khẩu.')" oninput="this.setCustomValidity('')">
                    <span class="material-symbols-outlined input-icon-suffix toggle-password">visibility_off</span>
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="password_confirm">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_reset</span>
                    <input type="password" id="password_confirm" name="password_confirm" class="auth-input password-field" placeholder="Nhập lại mật khẩu" required oninvalid="this.setCustomValidity('Vui lòng xác nhận mật khẩu.')" oninput="this.setCustomValidity('')">
                    <span class="material-symbols-outlined input-icon-suffix toggle-password">visibility_off</span>
                </div>
            </div>

            <button type="submit" class="auth-btn-primary">Đăng ký tài khoản</button>
        </form>

        <p class="login-text">
            Đã có tài khoản? <a href="/login" class="login-link">Đăng nhập</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-password');
            const passwordFields = document.querySelectorAll('.password-field');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (passwordFields.length > 0) {
                        const isPassword = passwordFields[0].type === 'password';
                        const newType = isPassword ? 'text' : 'password';
                        const newIcon = isPassword ? 'visibility' : 'visibility_off';
                        
                        passwordFields.forEach(field => field.type = newType);
                        toggleButtons.forEach(btn => btn.textContent = newIcon);
                    }
                });
            });
        });
    </script>
</body>
</html>
