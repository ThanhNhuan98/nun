<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Đăng ký tài khoản mới') ?></title>
    
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
            background-color: #F8F9FB; /* Nền xám nhạt */
            /* Tạo họa tiết chấm bi mờ giống trong ảnh */
            background-image: radial-gradient(#D0D5DD 1px, transparent 1px);
            background-size: 24px 24px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Thẻ Đăng ký (Card) */
        .auth-card {
            background-color: #FFFFFF;
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .auth-card-title {
            font-size: 28px;
            font-weight: 700;
            color: #1A56DB; /* Màu xanh đậm của tiêu đề */
            text-align: center;
            margin-bottom: 8px;
        }

        .auth-card-desc {
            font-size: 14px;
            color: #666666;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        /* Khối hiển thị lỗi */
        .alert-danger {
            background-color: #FEE2E2;
            color: #B91C1C;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* Kiểu dáng Input */
        .input-group {
            margin-bottom: 20px;
        }

        .auth-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 8px;
        }

        .text-danger {
            color: #DC2626;
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
            color: #9CA3AF;
        }

        .auth-input {
            width: 100%;
            padding: 12px 14px 12px 44px; /* Chừa chỗ cho icon prefix */
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 14px;
            background-color: #F9FAFB;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .auth-input:focus {
            outline: none;
            border-color: #1A56DB;
            background-color: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.1);
        }

        .auth-input::placeholder {
            color: #9CA3AF;
        }

        /* Icon xem mật khẩu */
        .input-icon-suffix {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #9CA3AF;
            cursor: pointer;
            transition: color 0.2s;
        }

        .input-icon-suffix:hover {
            color: #4B5563;
        }

        /* Radio Buttons - Loại tài khoản */
        .radio-group {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #4B5563;
            cursor: pointer;
        }

        .radio-label input[type="radio"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #D1D5DB;
            border-radius: 50%;
            outline: none;
            cursor: pointer;
            position: relative;
            background-color: #FFFFFF;
            transition: border-color 0.2s;
        }

        .radio-label input[type="radio"]:checked {
            border-color: #1A56DB;
        }

        .radio-label input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background-color: #1A56DB;
            border-radius: 50%;
        }

        /* Nút đăng ký chính */
        .auth-btn-primary {
            width: 100%;
            padding: 14px;
            background-color: #2563EB;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s;
            margin-top: 10px;
        }

        .auth-btn-primary:hover {
            background-color: #1D4ED8;
        }

        /* Liên kết đăng nhập */
        .login-text {
            text-align: center;
            font-size: 13px;
            color: #6B7280;
            margin-top: 24px;
        }

        .login-link {
            color: #2563EB;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        /* Chỉnh sửa hiển thị icon */
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
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