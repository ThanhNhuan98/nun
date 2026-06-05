<?php require_once __DIR__ . '/../../layouts/user_header.php'; 

// Lấy thông tin hồ sơ tài xế
if (!isset($driverProfile) && isset($user['id'])) {
    $userModel = new \App\Models\User();
    $driverProfile = $userModel->getDriverProfile($user['id']) ?: [];
}
?>

<!-- Link file CSS admin-user-edit.css vào đây -->

<div class="eu-container">
    
    <!-- HEADER -->
    <div class="eu-header">
        <a href="/admin/users" class="eu-back-btn">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Quay lại danh sách
        </a>
        <h2 class="eu-title"><?= app_e($pageTitle ?? 'Chỉnh sửa Hồ sơ') ?></h2>
    </div>

    <!-- FORM BAO BỌC TOÀN BỘ GRID -->
    <form method="POST" action="" enctype="multipart/form-data" id="edit-user-form">
        
        <div class="eu-grid">
            
            <!-- ================= CỘT TRÁI ================= -->
            <div class="eu-col-left">
                
                <!-- YÊU CẦU NÂNG CẤP TÀI XẾ (Chuyển sang màu Xanh) -->
                <?php if (($user['role'] ?? '') === 'user' && !empty($driverProfile['license_plate']) && empty($driverProfile['is_verified'])): ?>
                    <div class="eu-upgrade-alert">
                        <div class="eu-upgrade-title">
                            <span class="material-symbols-outlined" style="font-size: 20px;">verified</span> Yêu cầu nâng cấp Tài xế
                        </div>
                        <div class="eu-upgrade-text">
                            Người dùng này đã nộp hồ sơ đăng ký làm tài xế. Vui lòng kiểm tra thông tin xe và bằng lái trước khi duyệt.
                        </div>
                        <div style="background: #fff; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
                            <strong>Biển số:</strong> <?= app_e($driverProfile['license_plate']) ?>
                            <?php if (!empty($driverProfile['vehicle_registration_image'])): ?>
                                <div style="margin-top: 8px;">
                                    <a href="<?= app_e($driverProfile['vehicle_registration_image']) ?>" target="_blank">
                                        <img src="<?= app_e($driverProfile['vehicle_registration_image']) ?>" alt="Cà vẹt" style="width: 100%; height: auto; border-radius: 4px; border: 1px solid var(--border-color);">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="autoApproveDriver()" class="eu-btn eu-btn-primary" style="flex: 1;">Phê duyệt</button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- CARD: HỒ SƠ NGẮN GỌN -->
                <div class="eu-card eu-profile-box">
                    <div class="eu-avatar-wrapper">
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
                        <div class="eu-btn-edit-avatar" title="Đổi ảnh đại diện">
                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                        </div>
                    </div>
                    
                    <div class="eu-profile-name"><?= app_e($user['name'] ?? 'Chưa cập nhật') ?></div>
                    <div class="eu-profile-id">ID: USR-<?= str_pad($user['id'] ?? 0, 5, '0', STR_PAD_LEFT) ?>-VN</div>
                    
                    <div>
                        <?php if (!empty($user['is_blocked'])): ?>
                            <span class="eu-status-badge locked">Đã khóa</span>
                        <?php else: ?>
                            <span class="eu-status-badge active">Đang hoạt động</span>
                        <?php endif; ?>
                    </div>

                    <!-- Vai trò (Role) - Dropdown Select giống hệt Mockup -->
                    <div class="eu-left-group">
                        <label class="eu-left-label">Phân quyền</label>
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <div class="eu-input" style="background: #f1f5f9; color: var(--danger); font-weight: 600;">Quản trị viên (Admin)</div>
                            <input type="hidden" name="role" value="admin">
                        <?php else: ?>
                            <!-- Thay radio bằng thẻ select để giống mockup Dropdown -->
                            <select name="role" id="role-select" class="eu-input">
                                <option value="user" <?= app_selected($user['role'] ?? '', 'user') ?>>Người dùng (Khách hàng)</option>
                                <option value="driver" <?= app_selected($user['role'] ?? '', 'driver') ?>>Tài xế (Đối tác)</option>
                            </select>
                        <?php endif; ?>
                    </div>

                    <!-- Số dư ví (Chuyển input balance sang Cột Trái) -->
                    <div class="eu-left-group" id="wallet-group" style="display: <?= ($user['role'] ?? '') === 'driver' || !empty($driverProfile['license_plate']) ? 'block' : 'none' ?>;">
                        <label class="eu-left-label">Ví tài khoản (VNĐ)</label>
                        <div class="eu-input-wrapper">
                            <span class="material-symbols-outlined eu-input-icon">account_balance_wallet</span>
                            <input type="number" name="balance" class="eu-input with-icon" value="<?= app_e($driverProfile['balance'] ?? 0) ?>" step="1000" style="font-weight: bold; color: var(--primary-blue);">
                        </div>
                    </div>

                </div>

            </div>

            <!-- ================= CỘT PHẢI ================= -->
            <div class="eu-col-right">
                
                <!-- THÔNG TIN CƠ BẢN -->
                <div class="eu-card" style="margin-bottom: 24px;">
                    <h3 class="eu-card-title">Thông tin Cơ bản</h3>
                    <div class="eu-form-grid">
                        <div class="eu-form-group">
                            <label>Họ và tên *</label>
                            <input type="text" name="name" class="eu-input" value="<?= app_e($user['name'] ?? '') ?>" required data-error="Vui lòng nhập họ và tên.">
                        </div>
                        <div class="eu-form-group">
                            <label>Số điện thoại *</label>
                            <input type="text" name="phone" class="eu-input" value="<?= app_e($user['phone'] ?? '') ?>" required data-error="Vui lòng nhập số điện thoại.">
                        </div>
                        <div class="eu-form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="eu-input" value="<?= app_e($user['email'] ?? '') ?>">
                        </div>
                        <div class="eu-form-group">
                            <!-- Đặt no_show_count vào vị trí CCCD trong mockup để bảo toàn nghiệp vụ PHP cũ -->
                            <label>Số lần vi phạm giao nhận</label>
                            <input type="number" name="no_show_count" class="eu-input" value="<?= app_e($user['no_show_count'] ?? 0) ?>" min="0">
                        </div>
                    </div>
                </div>

                <!-- THÔNG TIN TÀI XẾ (Chỉ hiện khi role = driver) -->
                <div class="eu-card" id="driver-fields" style="margin-bottom: 24px; display: <?= ($user['role'] ?? '') === 'driver' || !empty($driverProfile['license_plate']) ? 'block' : 'none' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <h3 class="eu-card-title">Thông tin Tài xế</h3>
                        <span style="font-size: 12px; color: var(--text-muted);">(Chỉ áp dụng khi là Tài xế)</span>
                    </div>

                    <div class="eu-form-group" style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--primary-blue);">
                            <input type="checkbox" name="is_driver_verified" value="1" <?= !empty($driverProfile['is_verified']) ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: var(--primary-blue);">
                            Xác nhận tài xế đã cung cấp giấy tờ hợp lệ (Verified)
                        </label>
                    </div>

                    <div class="eu-form-grid">
                        <div class="eu-form-group">
                            <label>Biển số xe *</label>
                            <input type="text" name="license_plate" class="eu-input" value="<?= app_e($driverProfile['license_plate'] ?? '') ?>" placeholder="VD: 59-X1 123.45">
                        </div>
                        <div class="eu-form-group">
                            <label>Tải trọng tối đa (kg)</label>
                            <input type="number" name="max_total_weight" class="eu-input" value="<?= app_e($driverProfile['max_total_weight'] ?? 100) ?>" min="1" step="0.1">
                        </div>
                        <div class="eu-form-group">
                            <label>Số đơn ghép tối đa</label>
                            <input type="number" name="max_concurrent_orders" class="eu-input" value="<?= app_e($driverProfile['max_concurrent_orders'] ?? 10) ?>" min="1">
                        </div>
                    </div>

                    <div class="eu-form-group" style="margin-top: 16px;">
                        <label>Ảnh Giấy đăng ký xe (Cà vẹt)</label>
                        <?php if (!empty($driverProfile['vehicle_registration_image'])): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?= app_e($driverProfile['vehicle_registration_image']) ?>" style="height: 120px; border-radius: 4px; border: 1px solid var(--border-color); object-fit: contain; padding: 4px; background: #f8fafc;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="vehicle_registration" accept="image/*" class="eu-input">
                    </div>
                </div>

                <!-- LỊCH SỬ VI PHẠM -->
                <?php if (($user['role'] ?? '') === 'driver'): ?>
                <div class="eu-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; padding-bottom: 12px;">
                        <h3 class="eu-card-title" style="border: none; padding: 0; margin: 0;">Lịch sử Vi phạm</h3>
                        <?php if ((int)($user['violation_count'] ?? 0) > 0): ?>
                            <button type="button" onclick="confirmClearViolations()" class="eu-btn eu-btn-danger" style="padding: 6px 12px; font-size: 12px;">Xóa án tích</button>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 16px; font-size: 14px;">
                        Vi phạm 3 tháng qua: <strong style="color: var(--danger); font-size: 16px;"><?= (int)($user['violation_count'] ?? 0) ?></strong> / 5 lần.
                    </div>
                    
                    <?php if (empty($violations)): ?>
                        <div style="text-align: center; padding: 32px 0; color: var(--text-muted); font-size: 14px;">
                            Người dùng chưa có ghi nhận vi phạm nào.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="eu-table">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Lý do phạt</th>
                                        <th>Tiền phạt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($violations as $v): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                                            <td><?= app_e($v['reason']) ?></td>
                                            <td style="color: var(--danger); font-weight: bold;">-<?= app_money($v['amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- BỘ NÚT ACTIONS GÓC DƯỚI -->
        <div class="eu-actions">
            <a href="/admin/users" class="eu-btn eu-btn-outline">Hủy</a>
            <button type="submit" class="eu-btn eu-btn-primary">Lưu thay đổi</button>
        </div>
    </form>

    <!-- Custom Confirm Modal (Giữ nguyên logic của bạn) -->
    <div id="custom-confirm-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter: blur(2px);">
        <div style="background:#fff; padding:24px; border-radius:8px; width:100%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.1); text-align: center;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: var(--primary-blue); margin-bottom: 16px;">help</span>
            <h3 style="margin-top:0; color:var(--text-main); font-size:18px; margin-bottom: 12px;">Xác nhận thao tác</h3>
            <p id="custom-confirm-message" style="color:var(--text-muted); font-size:14px; margin-bottom:24px; line-height:1.5;"></p>
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" onclick="closeCustomConfirm()" class="eu-btn eu-btn-outline">Hủy bỏ</button>
                <button type="button" id="custom-confirm-btn" class="eu-btn eu-btn-primary">Đồng ý</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentConfirmCallback = null;

function showCustomConfirm(message, callback, btnClass = 'eu-btn-primary') {
    document.getElementById('custom-confirm-message').innerHTML = message;
    
    // Cập nhật class cho nút xác nhận dựa trên hành động
    const btn = document.getElementById('custom-confirm-btn');
    btn.className = 'eu-btn ' + btnClass;
    
    currentConfirmCallback = callback;
    document.getElementById('custom-confirm-modal').style.display = 'flex';
}

function closeCustomConfirm() {
    document.getElementById('custom-confirm-modal').style.display = 'none';
    currentConfirmCallback = null;
}

document.getElementById('custom-confirm-btn').addEventListener('click', function() {
    if (currentConfirmCallback) currentConfirmCallback();
    closeCustomConfirm();
});

document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role-select');
    const driverFields = document.getElementById('driver-fields');
    const walletGroup = document.getElementById('wallet-group');
    
    // Xử lý ẩn/hiện fields tài xế khi thay đổi Dropdown
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'driver') {
                driverFields.style.display = 'block';
                walletGroup.style.display = 'block';
            } else {
                driverFields.style.display = 'none';
                walletGroup.style.display = 'none';
            }
        });
    }
    
    // Phê duyệt nhanh Tài xế
    window.autoApproveDriver = function() {
        showCustomConfirm('Bạn có chắc chắn muốn phê duyệt và nâng cấp khách hàng này thành Tài xế?', function() {
            if (roleSelect) {
                roleSelect.value = 'driver'; // Set Dropdown sang Driver
            }
            
            const verifyCheckbox = document.querySelector('input[name="is_driver_verified"]');
            if (verifyCheckbox) verifyCheckbox.checked = true;
            
            // Bypass Validation Required cho License Plate (nếu nó trống) để ép submit
            const licenseInput = document.querySelector('input[name="license_plate"]');
            if(licenseInput && !licenseInput.value) {
                licenseInput.value = 'Chưa cấp'; 
            }
            
            document.getElementById('edit-user-form').submit();
        }, 'eu-btn-primary');
    };
});

function confirmClearViolations() {
    showCustomConfirm('Bạn có chắc chắn muốn <b>XÓA TOÀN BỘ</b> án tích của tài xế này không?', function() {
        const form = document.getElementById('edit-user-form');
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'action'; input.value = 'clear_violations';
        form.appendChild(input);
        
        // Bỏ qua validate HTML5 required khi xóa án tích
        form.noValidate = true;
        form.submit();
    }, 'eu-btn-danger'); // Dùng nút đỏ cho Xóa án tích
}
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>