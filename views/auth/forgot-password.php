<?php
/**
 * @var string $pageTitle
 * @var array|null $errors
 * @var array|null $old
 * @var string|null $error
 */
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

<style>
    .auth-page-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
        padding: 20px;
    }

    .auth-container {
        width: 100%;
        max-width: 400px;
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .auth-title {
        margin: 0 0 8px;
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
    }

    .auth-subtitle {
        margin: 0;
        color: #64748b;
        font-size: 14px;
    }

    .alert-error-small {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 20px;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .alert-icon {
        flex-shrink: 0;
    }

    .auth-form {
        margin-bottom: 20px;
    }

    .form-group-auth {
        margin-bottom: 20px;
    }

    .form-label-auth {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #0f172a;
        font-size: 14px;
    }

    .form-input-auth {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .form-input-auth:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-error {
        display: block;
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }

    .btn-auth-submit {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-auth-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
    }

    .auth-footer {
        text-align: center;
        border-top: 1px solid #e2e8f0;
        padding-top: 20px;
        font-size: 14px;
        color: #64748b;
    }

    .auth-footer p {
        margin: 0;
    }

    .auth-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .auth-link:hover {
        text-decoration: underline;
    }
</style>

