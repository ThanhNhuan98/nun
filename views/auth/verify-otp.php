<?php
/**
 * @var string $pageTitle
 * @var string $email_hint
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Xác thực OTP - NUN Express') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">

    
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-container">
            <div class="auth-header">
                <h2 class="auth-title">Xác thực OTP</h2>
                <p class="auth-subtitle">Mã OTP gồm 6 chữ số đã được gửi đến email <strong><?= app_e($email_hint) ?></strong></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-error-small">
                    <span class="alert-icon">⚠️</span>
                    <?= app_e($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($message = app_flash('flash_success')): ?>
                <div style="background: #ecfdf5; color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #a7f3d0;">
                    <?= app_e($message) ?>
                </div>
            <?php endif; ?>

            <form action="/verify-otp" method="POST" class="auth-form">
                <div class="form-group-auth">
                    <label class="form-label-auth">Mã OTP *</label>
                    <input 
                        type="text" 
                        name="otp" 
                        placeholder="Nhập mã 6 chữ số"
                        required
                        maxlength="6"
                        pattern="\d{6}"
                        title="Mã OTP phải là 6 chữ số"
                        class="form-input-auth"
                        style="text-align: center; font-size: 20px; letter-spacing: 5px;"
                    >
                </div>

                <button type="submit" class="btn-auth-submit">
                    Xác thực
                </button>
            </form>

            <div class="auth-footer">
                <p>
                    Chưa nhận được mã? <a href="/forgot-password" class="auth-link">Gửi lại</a>
                </p>
                <p style="margin-top: 10px;">
                    Quay lại <a href="/login" class="auth-link">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
