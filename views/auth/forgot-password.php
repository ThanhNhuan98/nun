<?php
/**
 * @var string $pageTitle
 * @var array|null $errors
 * @var array|null $old
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Quên mật khẩu - NUN Express') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">


</head>
<body>
    <?= app_render_toast(); ?>
    <div class="auth-card">
        <h2 class="auth-card-title">Quên mật khẩu?</h2>
        <p class="auth-card-desc">Nhập email hoặc số điện thoại để nhận mã OTP</p>

        <form action="/request-otp" method="POST">
            <div class="input-group">
                <label class="auth-label">Email hoặc SĐT <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">mail</span>
                    <input type="text" name="email_or_phone" value="<?= app_e($old['email_or_phone'] ?? '') ?>" placeholder="Nhập email hoặc số điện thoại" required class="auth-input" oninvalid="this.setCustomValidity('Vui lòng nhập email hoặc số điện thoại.')" oninput="this.setCustomValidity('')">
                </div>
                <?php if (!empty($errors['email_or_phone'])): ?>
                    <small class="form-error" style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;"><?= app_e($errors['email_or_phone']) ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="auth-btn-primary">
                Gửi mã OTP
            </button>
        </form>

        <p class="login-text" style="margin-top: 24px; text-align: center;">
            Quay lại <a href="/login" class="login-link">Đăng nhập</a> hoặc 
            <a href="/register" class="login-link">Đăng ký</a>
        </p>
    </div>
</body>
</html>
