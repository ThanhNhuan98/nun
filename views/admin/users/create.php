<?php
/**
 * View: Admin - Thêm người dùng mới
 * @var string $pageTitle
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="breadcrumb-container" style="margin-bottom: 25px;">
        <a href="/admin/users" class="breadcrumb-item">Quản lý người dùng</a>
        <span class="breadcrumb-item active">Thêm mới</span>
    </div>

    <div class="admin-page-header" style="margin-bottom: 30px;">
        <h2 class="admin-page-title" style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            <?= app_e($pageTitle ?? 'Thêm người dùng mới') ?>
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0; max-width: 600px; line-height: 1.5;">
            Tạo hồ sơ cho khách hàng hoặc tài xế mới trong hệ thống NUN Express. Đảm bảo thông tin liên lạc chính xác để quá trình vận hành diễn ra thông suốt.
        </p>
    </div>

    <div class="create-user-layout">
        
        <div class="role-guide-box">
            <div class="role-guide-header">
                <span class="material-symbols-outlined">policy</span> Hướng dẫn phân quyền
            </div>
            
            <div class="role-guide-item">
                <div class="role-guide-title customer">
                    <span class="material-symbols-outlined" style="font-size: 18px;">storefront</span> Khách hàng
                </div>
                <div class="role-guide-desc">
                    Quyền truy cập cổng thông tin đặt hàng, theo dõi lộ trình và quản lý hóa đơn thanh toán.
                </div>
            </div>

            <div class="role-guide-item">
                <div class="role-guide-title driver">
                    <span class="material-symbols-outlined" style="font-size: 18px;">two_wheeler</span> Tài xế
                </div>
                <div class="role-guide-desc">
                    Quyền truy cập ứng dụng di động để nhận đơn, cập nhật trạng thái giao hàng và quét mã vạch.
                </div>
            </div>
        </div>

        <div class="create-form-card">
            <form method="POST" action="/admin/users/create" enctype="multipart/form-data">
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 12px;">Vai trò hệ thống <span class="text-danger">*</span></label>
                    <div class="role-radio-group">
                        
                        <div>
                            <input type="radio" id="role_user" name="role" value="user" class="role-radio-input" checked>
                            <label for="role_user" class="role-radio-label">
                                <span class="role-radio-custom"></span>
                                <div class="role-radio-text">
                                    <strong>Khách hàng</strong>
                                    <span>Người gửi hoặc người nhận hàng hóa.</span>
                                </div>
                            </label>
                        </div>

                        <div>
                            <input type="radio" id="role_driver" name="role" value="driver" class="role-radio-input">
                            <label for="role_driver" class="role-radio-label">
                                <span class="role-radio-custom"></span>
                                <div class="role-radio-text">
                                    <strong>Tài xế</strong>
                                    <span>Đối tác giao nhận của NUN Express.</span>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Họ tên <span class="text-danger">*</span></label>
                    <div class="form-input-with-icon">
                        <span class="material-symbols-outlined icon-left">person</span>
                        <input type="text" name="name" class="form-control" placeholder="Nhập họ và tên đầy đủ" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Số điện thoại <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">call</span>
                            <input type="text" name="phone" class="form-control" placeholder="090 xxxx xxx" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Email <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">mail</span>
                            <input type="email" name="email" class="form-control" placeholder="example@domain.com">
                        </div>
                    </div>
                </div>

                <div id="driver_fields_wrapper" style="display: none; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Biển số xe <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">directions_car</span>
                            <input type="text" name="license_plate" id="license_plate" class="form-control" placeholder="VD: 59A-123.45" value="<?= app_e($old['license_plate'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Ảnh Giấy chứng nhận đăng ký xe (Cà vẹt)</label>
                        <input type="file" name="vehicle_registration" accept="image/*" class="form-control">
                        <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">Ảnh minh chứng để xét duyệt tài khoản tài xế.</small>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">lock</span>
                            <input type="password" name="password" class="form-control password-field" placeholder="Tối thiểu 8 ký tự" required>
                            <span class="material-symbols-outlined icon-right toggle-password" style="cursor: pointer;">visibility_off</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">password</span>
                            <input type="password" name="password_confirmation" class="form-control password-field" placeholder="Nhập lại mật khẩu" required>
                            <span class="material-symbols-outlined icon-right toggle-password" style="cursor: pointer;">visibility_off</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="/admin/users" class="btn-cancel">Hủy</a>
                    <button type="submit" class="btn-submit">
                        <span class="material-symbols-outlined" style="font-size: 18px;">person_add</span> Tạo người dùng
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const roleInputs = document.querySelectorAll('input[name="role"]');
            const driverFieldsWrapper = document.getElementById('driver_fields_wrapper');

            // Hiển thị hoặc ẩn các trường thông tin xe dựa trên Role (Tài xế/Khách) đang được chọn.
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

            // Đồng bộ xem mật khẩu
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
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
