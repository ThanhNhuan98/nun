<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Xác thực tài khoản') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <style>
        /* Cài đặt cơ bản */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F9FB; /* Nền xám xanh nhạt */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #1A1A1A;
        }

        /* Thẻ trung tâm (Card) */
        .auth-card {
            background-color: #FFFFFF;
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Thanh viền màu gradient phía trên cùng */
        .card-top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #1D4ED8 0%, #60A5FA 100%);
        }

        /* Icon ổ khóa */
        .auth-icon-wrapper {
            background-color: #2563EB;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px auto;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .auth-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        .auth-desc {
            font-size: 14px;
            color: #6B7280;
            line-height: 1.5;
            margin-bottom: 25px;
            padding: 0 10px;
        }

        /* Hiển thị lỗi từ PHP */
        .alert-danger {
            background-color: #FEE2E2;
            color: #B91C1C;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .alert-success-soft {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: left;
        }

        /* Nhập OTP (6 ô) */
        .otp-input-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .otp-box {
            width: 45px;
            height: 55px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: #111827;
            background-color: #FFFFFF;
            transition: all 0.2s ease;
        }

        .otp-box:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Hộp thông báo (Info box) */
        .info-box {
            background-color: #EEF2FF;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 25px;
            text-align: left;
        }

        .info-box-icon {
            color: #6B7280;
            font-size: 18px !important;
            margin-top: 2px;
        }

        .info-box-text {
            font-size: 13px;
            color: #6B7280;
            line-height: 1.4;
        }

        /* Nút Xác thực */
        .auth-btn {
            width: 100%;
            background-color: #2563EB;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 20px;
        }

        .auth-btn:hover {
            background-color: #1D4ED8;
        }

        /* Nút quay lại */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #2563EB;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #1D4ED8;
            text-decoration: underline;
        }

        .back-icon {
            font-size: 16px !important;
        }

        /* Material Symbols setup */
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.unfilled {
            font-variation-settings: 'FILL' 0;
        }
    </style>
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