<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div style="max-width: 800px; margin: 0 auto 20px auto;">
        <a href="/admin/users" class="btn-back-text">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Quay lại danh sách
        </a>
        <h2 class="admin-page-title" style="font-size: 24px; font-weight: 700; color: var(--text-main); margin: 0;">
            <?= app_e($pageTitle ?? 'Sửa Thông Tin Người Dùng') ?>
        </h2>
    </div>

    <div class="create-form-card" style="max-width: 800px; margin: 0 auto;">
        
        <div class="edit-profile-header">
            <div class="edit-profile-info">
                <div class="edit-avatar-wrapper">
                    <?php
                        $rawAvatar = $user['avatar'] ?? '';
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                        if (!empty($rawAvatar) && strpos($rawAvatar, 'default-avatar.png') === false) {
                            $avatarUrl = (strpos($rawAvatar, 'http') === 0 || strpos($rawAvatar, '/') === 0) ? $rawAvatar : '/uploads/avatars/' . $rawAvatar;
                        } else {
                            $avatarUrl = $basePath . '/assets/images/default-avatar.png';
                        }
                    ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
                    <div class="btn-edit-avatar" title="Đổi ảnh đại diện">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit</span>
                    </div>
                </div>
                <div>
                    <div class="edit-profile-name"><?= app_e($user['name'] ?? 'Người dùng chưa có tên') ?></div>
                    <div class="edit-profile-id">
                        <span class="material-symbols-outlined" style="font-size: 16px;">badge</span> 
                        ID: USR-<?= str_pad($user['id'] ?? 0, 5, '0', STR_PAD_LEFT) ?>-VN
                    </div>
                </div>
            </div>
            
            <div>
                <?php if (!empty($user['is_blocked'])): ?>
                    <span class="status-dot locked" style="padding: 6px 12px; background: var(--danger-light); border-radius: 4px;">Đã khóa</span>
                <?php else: ?>
                    <span class="status-dot active" style="padding: 6px 12px; background: var(--success-light); border-radius: 4px;">Đang hoạt động</span>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Họ tên <span class="text-danger">*</span></label>
                    <div class="form-input-with-icon">
                        <span class="material-symbols-outlined icon-left">person</span>
                        <input type="text" name="name" class="form-control" value="<?= app_e($user['name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group" style="width: 100%;">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Ảnh Giấy chứng nhận đăng ký xe (Cà vẹt)</label>
                        <?php if (!empty($driverProfile['vehicle_registration_image'])): ?>
                            <div style="margin-bottom: 12px;">
                                <a href="<?= app_e($driverProfile['vehicle_registration_image']) ?>" target="_blank" style="display: inline-block;" title="Nhấn để xem ảnh gốc">
                                    <img src="<?= app_e($driverProfile['vehicle_registration_image']) ?>" style="max-height: 200px; max-width: 100%; border-radius: 6px; border: 1px solid var(--border-color); object-fit: contain; background: #f8fafc; padding: 4px;">
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="padding: 12px; background: var(--danger-light); color: var(--danger); border-radius: 4px; font-size: 13px; margin-bottom: 12px; border: 1px solid #fecaca;">
                                Tài xế này chưa tải lên ảnh giấy chứng nhận đăng ký xe.
                            </div>
                        <?php endif; ?>
                        <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Tải lên ảnh mới (nếu muốn thay đổi)</label>
                        <input type="file" name="vehicle_registration" accept="image/*" class="form-control">
                    </div>

                    <div class="form-group" style="width: 100%; margin-top: 15px; padding: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px;">
                        <label class="form-label" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #166534; margin: 0; cursor: pointer;">
                            <input type="checkbox" name="is_driver_verified" value="1" <?= !empty($driverProfile['is_verified']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #16a34a;">
                            Xác nhận tài xế đã cung cấp giấy tờ hợp lệ (Verified)
                        </label>
                        <p style="font-size: 12px; color: #15803d; margin: 6px 0 0 26px;">Tài khoản sẽ được gắn huy hiệu xác thực trên hệ thống.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Số điện thoại <span class="text-danger">*</span></label>
                    <div class="form-input-with-icon">
                        <span class="material-symbols-outlined icon-left">call</span>
                        <input type="text" name="phone" class="form-control" value="<?= app_e($user['phone'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Email</label>
                    <div class="form-input-with-icon">
                        <span class="material-symbols-outlined icon-left">mail</span>
                        <input type="email" name="email" class="form-control" value="<?= app_e($user['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Số lần bom hàng</label>
                    <div class="form-input-with-icon">
                        <span class="material-symbols-outlined icon-left">person_off</span>
                        <input type="number" name="no_show_count" class="form-control" value="<?= app_e($user['no_show_count'] ?? 0) ?>" min="0">
                    </div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px; line-height: 1.4;">Reset về 0 để xóa cảnh báo / mở khóa vi phạm.</p>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 12px;">Vai trò hệ thống <span class="text-danger">*</span></label>
                
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <div style="padding: 16px; background: var(--danger-light); color: var(--danger); border: 1px solid #fca5a5; border-radius: 4px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        Đây là tài khoản Quản trị viên (Không thể thay đổi vai trò).
                        <input type="hidden" name="role" value="admin">
                    </div>
                <?php else: ?>
                    <div class="role-radio-group">
                        <div>
                            <input type="radio" id="role_user" name="role" value="user" class="role-radio-input" <?= app_selected($user['role'] ?? '', 'user') ? 'checked' : '' ?>>
                            <label for="role_user" class="role-radio-label">
                                <span class="role-radio-custom"></span>
                                <div class="role-radio-text">
                                    <strong>Khách hàng</strong>
                                    <span>Người dùng tạo đơn và theo dõi hành trình bưu gửi.</span>
                                </div>
                            </label>
                        </div>

                        <div>
                            <input type="radio" id="role_driver" name="role" value="driver" class="role-radio-input" <?= app_selected($user['role'] ?? '', 'driver') ? 'checked' : '' ?>>
                            <label for="role_driver" class="role-radio-label">
                                <span class="role-radio-custom"></span>
                                <div class="role-radio-text">
                                    <strong>Tài xế</strong>
                                    <span>Nhân viên tiếp nhận, vận chuyển và giao hàng.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (($user['role'] ?? '') === 'driver'): ?>
                <div class="form-row" style="margin-top: 24px; padding-top: 24px; border-top: 1px dashed var(--border-color);">
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Số đơn ghép tối đa</label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">layers</span>
                            <input type="number" name="max_concurrent_orders" class="form-control" value="<?= app_e($driverProfile['max_concurrent_orders'] ?? 10) ?>" min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Giới hạn tải trọng (kg)</label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">weight</span>
                            <input type="number" name="max_total_weight" class="form-control" value="<?= app_e($driverProfile['max_total_weight'] ?? 100) ?>" min="1" step="0.1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Biển số xe <span class="text-danger">*</span></label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">directions_car</span>
                            <input type="text" name="license_plate" class="form-control" value="<?= app_e($driverProfile['license_plate'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Số dư ví (VNĐ)</label>
                        <div class="form-input-with-icon">
                            <span class="material-symbols-outlined icon-left">account_balance_wallet</span>
                            <input type="number" name="balance" class="form-control" value="<?= app_e($driverProfile['balance'] ?? 0) ?>" step="1000">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;">
                <a href="/admin/users" class="btn-cancel">Hủy bỏ</a>
                <button type="submit" class="btn-submit">
                    <span class="material-symbols-outlined" style="font-size: 18px;">save</span> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>