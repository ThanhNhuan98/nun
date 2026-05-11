<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Xác thực tài khoản') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">

    
</head>
<body>

    <div class="auth-card">
        <div class="card-top-accent"></div>

        <div class="auth-icon-wrapper">
            <span class="material-symbols-outlined">lock</span>
        </div>

        <h2 class="auth-title">Xác thực tài khoản</h2>
        <p class="auth-desc">Mã OTP gồm 6 chữ số đã được gửi đến email<br><strong><?= app_e($email ?? 'của bạn') ?></strong>.</p>

        <?php if ($message = app_flash('flash_error')): ?>
            <div class="alert-danger"><?= app_e($message) ?></div>
        <?php endif; ?>
        
        <?php if ($message = app_flash('flash_info')): ?>
            <div class="alert-success-soft"><?= app_e($message) ?></div>
        <?php endif; ?>

        <form action="/auth/verify" method="POST">
            
            <div class="otp-input-group">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" autofocus>
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
            </div>

            <input type="hidden" id="real-otp" name="otp" required pattern="\d{6}" title="Mã OTP phải là 6 chữ số.">

            <div class="info-box">
                <span class="material-symbols-outlined unfilled info-box-icon">info</span>
                <span class="info-box-text">Vui lòng kiểm tra cả hộp thư rác nếu chưa thấy email.</span>
            </div>

            <button type="submit" class="auth-btn">Xác thực</button>
        </form>

        <a href="/register" class="back-link">
            <span class="material-symbols-outlined back-icon">arrow_back</span>
            Quay lại trang đăng ký
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const otpBoxes = document.querySelectorAll('.otp-box');
            const hiddenOtpInput = document.getElementById('real-otp');

            // Hàm cập nhật giá trị cho input ẩn
            const updateHiddenInput = () => {
                const otpValue = Array.from(otpBoxes).map(box => box.value).join('');
                hiddenOtpInput.value = otpValue;
            };

            otpBoxes.forEach((box, index) => {
                // Xử lý khi nhập
                box.addEventListener('input', (e) => {
                    // Chỉ cho phép nhập số
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    
                    if (e.target.value !== '') {
                        // Nhảy sang ô tiếp theo nếu có
                        if (index < otpBoxes.length - 1) {
                            otpBoxes[index + 1].focus();
                        }
                    }
                    updateHiddenInput();
                });

                // Xử lý phím Backspace để lùi lại ô trước đó
                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && e.target.value === '') {
                        if (index > 0) {
                            otpBoxes[index - 1].focus();
                        }
                    }
                });

                // Xử lý khi dán (Paste) mã 6 số
                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6).split('');
                    
                    if (pasteData.length > 0) {
                        otpBoxes.forEach((b, i) => {
                            b.value = pasteData[i] || '';
                        });
                        // Đặt con trỏ ở ô tiếp theo sau khi dán
                        const focusIndex = Math.min(pasteData.length, otpBoxes.length - 1);
                        if(pasteData.length === 6) {
                            otpBoxes[5].focus(); 
                        } else {
                            otpBoxes[focusIndex].focus();
                        }
                        updateHiddenInput();
                    }
                });
            });
        });
    </script>
</body>
</html>