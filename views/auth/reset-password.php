<?php
/**
 * @var string $pageTitle
 * @var array|null $errors
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Đặt lại mật khẩu - NUN Express') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">

    
</head>
<body>
    <?= app_render_toast(); ?>
    <div class="auth-card">
        <h2 class="auth-card-title">Đặt lại mật khẩu</h2>
        <p class="auth-card-desc">Vui lòng nhập mật khẩu mới cho tài khoản của bạn</p>

        <?php if (!empty($errors)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php foreach ($errors as $err): ?>
                    if (typeof showToast === 'function') showToast('<?= app_e($err) ?>', 'error');
                    <?php endforeach; ?>
                });
            </script>
        <?php endif; ?>

        <form action="/reset-password" method="POST">
            <div class="input-group">
                <label class="auth-label">Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_outline</span>
                    <input type="password" name="password" class="auth-input password-field" placeholder="Nhập mật khẩu mới" required minlength="6" oninvalid="this.setCustomValidity('Vui lòng nhập mật khẩu mới (ít nhất 6 ký tự).')" oninput="this.setCustomValidity('')">
                    <span class="material-symbols-outlined input-icon-suffix toggle-password">visibility_off</span>
                </div>
            </div>
            
            <div class="input-group">
                <label class="auth-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_reset</span>
                    <input type="password" name="password_confirm" class="auth-input password-field" placeholder="Nhập lại mật khẩu mới" required minlength="6" oninvalid="this.setCustomValidity('Vui lòng xác nhận mật khẩu mới (ít nhất 6 ký tự).')" oninput="this.setCustomValidity('')">
                    <span class="material-symbols-outlined input-icon-suffix toggle-password">visibility_off</span>
                </div>
            </div>

            <button type="submit" class="auth-btn-primary">
                Cập nhật mật khẩu
            </button>
        </form>

        <p class="login-text" style="margin-top: 24px; text-align: center;">
            Quay lại <a href="/login" class="login-link">Đăng nhập</a>
        </p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
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