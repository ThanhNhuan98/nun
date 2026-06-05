<?php
/**
 * View: Admin - Thêm người dùng mới
 * @var string $pageTitle
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="cu-container">
    
    <div class="cu-breadcrumb">
        <a href="/admin/users">Quản lý người dùng</a>
        <span class="material-symbols-outlined" style="font-size: 14px;">chevron_right</span>
        <span>Thêm mới</span>
    </div>

    <h2 class="cu-title"><?= app_e($pageTitle ?? 'Thêm người dùng mới') ?></h2>
    <p class="cu-subtitle">Tạo hồ sơ cho Khách hàng mới để bắt đầu gửi hàng, hoặc thiết lập tài khoản Tài xế để cấp quyền truy cập hệ thống giao nhận.</p>

    <div class="cu-layout">
        
        <div class="cu-main-form">
            <form method="POST" action="/admin/users/create" enctype="multipart/form-data" class="cu-card">
                
                <h3 class="cu-section-title">Vai trò hệ thống</h3>
                <div class="cu-role-grid">
                    <div class="cu-role-card">
                        <input type="radio" id="role_user" name="role" value="user" checked>
                        <label for="role_user">
                            <div class="cu-role-icon"><span class="material-symbols-outlined">storefront</span></div>
                            <div class="cu-role-text">
                                <strong>Khách hàng</strong>
                                <span>Người gửi / Nhận hàng</span>
                            </div>
                        </label>
                    </div>

                    <div class="cu-role-card">
                        <input type="radio" id="role_driver" name="role" value="driver">
                        <label for="role_driver">
                            <div class="cu-role-icon"><span class="material-symbols-outlined">two_wheeler</span></div>
                            <div class="cu-role-text">
                                <strong>Tài xế</strong>
                                <span>Nhân viên giao nhận</span>
                            </div>
                        </label>
                    </div>
                </div>

                <h3 class="cu-section-title">Thông tin cá nhân</h3>
                
                <div class="cu-form-group">
                    <label class="cu-form-label">Họ và tên <span class="text-danger">*</span></label>
                    <div class="cu-input-wrapper">
                        <input type="text" name="name" class="cu-input" placeholder="Nhập họ và tên đầy đủ" required data-error="Vui lòng nhập họ và tên.">
                    </div>
                </div>

                <div class="cu-form-row">
                    <div class="cu-form-group">
                        <label class="cu-form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <div class="cu-input-wrapper">
                            <span class="material-symbols-outlined cu-icon-left">call</span>
                            <input type="text" name="phone" class="cu-input has-icon-left" placeholder="090 123 4567" required data-error="Vui lòng nhập số điện thoại.">
                        </div>
                    </div>
                    <div class="cu-form-group">
                        <label class="cu-form-label">Email</label>
                        <div class="cu-input-wrapper">
                            <span class="material-symbols-outlined cu-icon-left">mail</span>
                            <input type="email" name="email" class="cu-input has-icon-left" placeholder="example@email.com">
                        </div>
                    </div>
                </div>

                <div id="driver_fields_wrapper" class="cu-driver-box" style="display: none;">
                    <h3 class="cu-section-title" style="color: var(--primary-blue);">
                        <span class="material-symbols-outlined">info</span> Thông tin Tài xế
                    </h3>
                    
                    <div class="cu-form-row" style="margin-bottom: 0;">
                        <div class="cu-form-group" style="margin-bottom: 0;">
                            <label class="cu-form-label">Biển số xe <span class="text-danger">*</span></label>
                            <div class="cu-input-wrapper">
                                <input type="text" name="license_plate" id="license_plate" class="cu-input" placeholder="VD: 59-A1 123.45" value="<?= app_e($old['license_plate'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="cu-form-group" style="margin-bottom: 0;">
                            <label class="cu-form-label">Giấy đăng ký xe (Cà vẹt) <span class="text-danger">*</span></label>
                            <div class="cu-file-upload">
                                <span class="material-symbols-outlined">upload_file</span>
                                <span>Tải lên tệp đính kèm</span>
                                <input type="file" name="vehicle_registration" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="cu-section-title">Bảo mật</h3>
                <div class="cu-form-row" style="margin-bottom: 0;">
                    <div class="cu-form-group">
                        <label class="cu-form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="cu-input-wrapper">
                            <input type="password" name="password" class="cu-input has-icon-right password-field" placeholder="••••••••" required data-error="Vui lòng nhập mật khẩu.">
                            <span class="material-symbols-outlined cu-icon-right toggle-password">visibility_off</span>
                        </div>
                    </div>
                    <div class="cu-form-group">
                        <label class="cu-form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="cu-input-wrapper">
                            <input type="password" name="password_confirmation" class="cu-input has-icon-right password-field" placeholder="••••••••" required data-error="Vui lòng xác nhận mật khẩu.">
                            <span class="material-symbols-outlined cu-icon-right toggle-password">visibility_off</span>
                        </div>
                    </div>
                </div>

                <div class="cu-actions">
                    <a href="/admin/users" class="cu-btn cu-btn-outline">Hủy</a>
                    <button type="submit" class="cu-btn cu-btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span> Tạo người dùng
                    </button>
                </div>

            </form>
        </div>

        <div class="cu-sidebar">
            <div class="cu-guide-box">
                <div class="cu-guide-title">
                    <span class="material-symbols-outlined">help_outline</span> Hướng dẫn vai trò
                </div>
                
                <div class="cu-guide-item">
                    <div class="cu-guide-icon">
                        <span class="material-symbols-outlined">storefront</span>
                    </div>
                    <div class="cu-guide-text">
                        <h4>Khách hàng</h4>
                        <p>Tài khoản dành cho cá nhân hoặc doanh nghiệp cần gửi hàng. Có quyền tạo đơn, tra cứu lịch sử và quản lý địa chỉ nhận/gửi.</p>
                    </div>
                </div>

                <div class="cu-guide-item">
                    <div class="cu-guide-icon">
                        <span class="material-symbols-outlined">two_wheeler</span>
                    </div>
                    <div class="cu-guide-text">
                        <h4>Tài xế</h4>
                        <p>Tài khoản nội bộ cấp cho nhân viên giao nhận. Có quyền tiếp nhận đơn, cập nhật trạng thái giao hàng và quét mã vạch.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const driverFieldsWrapper = document.getElementById('driver_fields_wrapper');

        // Toggle hiển thị form thông tin tài xế
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

        // Logic ẩn / hiện mật khẩu
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function (e) {
                const wrapper = e.target.closest('.cu-input-wrapper');
                const passwordInput = wrapper.querySelector('.password-field');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                e.target.textContent = type === 'password' ? 'visibility_off' : 'visibility';
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>