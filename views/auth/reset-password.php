<?php
/**
 * @var string $pageTitle
 * @var array|null $errors
 * @var string|null $error
 */
echo '<link rel="stylesheet" href="/assets/css/auth.css">';
?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<div class="auth-page-wrapper">
    <div class="auth-container">
        <div class="auth-header">
            <h2 class="auth-title">Đặt lại mật khẩu</h2>
            <p class="auth-subtitle">Vui lòng nhập mật khẩu mới cho tài khoản của bạn</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error-small">
                <span class="alert-icon">⚠️</span>
                <?= app_e($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert-error-small" style="flex-direction: column; align-items: flex-start;">
                <?php foreach ($errors as $err): ?>
                    <div>⚠️ <?= app_e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($message = app_flash('flash_success')): ?>
            <div style="background: #ecfdf5; color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #a7f3d0;">
                <?= app_e($message) ?>
            </div>
        <?php endif; ?>

        <form action="/reset-password" method="POST" class="auth-form">
            <div class="form-group-auth">
                <label class="form-label-auth">Mật khẩu mới *</label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu mới"
                        required
                        minlength="6"
                        class="form-input-auth password-field"
                        style="padding-right: 40px;"
                    >
                    <span class="material-symbols-outlined toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; user-select: none;">visibility_off</span>
                </div>
            </div>
            
            <div class="form-group-auth">
                <label class="form-label-auth">Xác nhận mật khẩu mới *</label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        name="password_confirm" 
                        placeholder="Nhập lại mật khẩu mới"
                        required
                        minlength="6"
                        class="form-input-auth password-field"
                        style="padding-right: 40px;"
                    >
                    <span class="material-symbols-outlined toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; user-select: none;">visibility_off</span>
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">
                Cập nhật mật khẩu
            </button>
        </form>

        <div class="auth-footer">
            <p>
                Quay lại <a href="/login" class="auth-link">Đăng nhập</a>
            </p>
        </div>
    </div>
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

<?php require_once __DIR__ . '/../layouts/user_footer.php'; ?>