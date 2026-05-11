<?php
/**
 * @var string $pageTitle
 * @var array|null $errors
 * @var array|null $old
 * @var string|null $error
 */
echo '<link rel="stylesheet" href="/assets/css/auth.css">';

?>

<div class="auth-page-wrapper">
    <div class="auth-container">
        <div class="auth-header">
            <h2 class="auth-title">Quên mật khẩu?</h2>
            <p class="auth-subtitle">Nhập email hoặc số điện thoại để nhận mã OTP</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error-small">
                <span class="alert-icon">⚠️</span>
                <?= app_e($error) ?>
            </div>
        <?php endif; ?>

        <form action="/request-otp" method="POST" class="auth-form">
            <div class="form-group-auth">
                <label class="form-label-auth">Email hoặc SĐT *</label>
                <input 
                    type="text" 
                    name="email_or_phone" 
                    value="<?= app_e($old['email_or_phone'] ?? '') ?>" 
                    placeholder="Nhập email hoặc số điện thoại"
                    required
                    class="form-input-auth"
                >
                <?php if (!empty($errors['email_or_phone'])): ?>
                    <small class="form-error"><?= app_e($errors['email_or_phone']) ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-auth-submit">
                Gửi mã OTP
            </button>
        </form>

        <div class="auth-footer">
            <p>
                Quay lại <a href="/login" class="auth-link">Đăng nhập</a> hoặc 
                <a href="/register" class="auth-link">Đăng ký</a>
            </p>
        </div>
    </div>
</div>

