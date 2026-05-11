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

    <div class="auth-card">
        <h2 class="auth-card-title">Tạo tài khoản mới</h2>
        <p class="auth-card-desc">Tham gia NUN Express để trải nghiệm dịch vụ giao hàng nhanh chóng.</p>

        <!-- Bổ sung khối hiển thị lỗi hệ thống / lỗi gửi Mail -->
        <?php if ($message = app_flash('flash_error')): ?>
            <div class="alert-danger" style="display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                <span><?= app_e($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($message = app_flash('flash_success')): ?>
            <div style="background-color: #D1FAE5; color: #065F46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                <span><?= app_e($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= app_e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register" enctype="multipart/form-data">
            <div class="input-group">
                <label class="auth-label" for="name">Họ và tên <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">person_outline</span>
                    <input type="text" id="name" name="name" class="auth-input" placeholder="VD: Nguyễn Văn A" required value="<?= app_e($old['name'] ?? '') ?>">
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="phone">Số điện thoại <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">call</span>
                    <input type="text" id="phone" name="phone" class="auth-input" placeholder="09xx..." required value="<?= app_e($old['phone'] ?? '') ?>">
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
                <label class="auth-label">Loại tài khoản:</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="role" value="user" <?= app_checked($old['role'] ?? 'user', 'user') ?>>
                        Khách hàng
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="role" value="driver" <?= app_checked($old['role'] ?? '', 'driver') ?>>
                        Tài xế giao hàng
                    </label>
                </div>
            </div>

            <div id="driver_fields_wrapper" style="display: none;">
                <div class="input-group">
                    <label class="auth-label" for="license_plate">Biển số xe <span class="text-danger">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon-prefix">directions_car</span>
                        <input type="text" id="license_plate" name="license_plate" class="auth-input" placeholder="VD: 59A-123.45" value="<?= app_e($old['license_plate'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="input-group">
                    <label class="auth-label">Ảnh Giấy đăng ký xe (Cà vẹt) <span class="text-danger">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon-prefix">image</span>
                        <input type="file" name="vehicle_registration" accept="image/*" class="auth-input" style="padding-top: 9px; padding-bottom: 9px;">
                    </div>
                    <small style="color: #6B7280; font-size: 12px; margin-top: 4px; display: block;">Admin sẽ kiểm tra hình ảnh này để duyệt tài khoản.</small>
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="password">Mật khẩu <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_outline</span>
                    <input type="password" id="password" name="password" class="auth-input password-field" placeholder="Nhập mật khẩu" required>
                    <span class="material-symbols-outlined input-icon-suffix toggle-password">visibility_off</span>
                </div>
            </div>

            <div class="input-group">
                <label class="auth-label" for="password_confirm">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon-prefix">lock_reset</span>
                    <input type="password" id="password_confirm" name="password_confirm" class="auth-input password-field" placeholder="Nhập lại mật khẩu" required>
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

            const roleInputs = document.querySelectorAll('input[name="role"]');
            const driverFieldsWrapper = document.getElementById('driver_fields_wrapper');

            function toggleDriverFields() {
                const selectedRole = document.querySelector('input[name="role"]:checked')?.value;
                if (selectedRole === 'driver') {
                    driverFieldsWrapper.style.display = 'block';
                } else {
                    driverFieldsWrapper.style.display = 'none';
                }
            }

            toggleDriverFields();
            roleInputs.forEach(input => input.addEventListener('change', toggleDriverFields));
        });
    </script>
</body>
</html>