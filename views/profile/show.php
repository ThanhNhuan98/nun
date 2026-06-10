<?php
/**
 * @var array $targetUser
 * @var array|null $ratingInfo
 * @var array $reviews
 * @var string $pageTitle
 * @var array|null $driverProfile
 * @var string $currentUserRole
 */
require_once __DIR__ . '/../layouts/user_header.php';
$isOwnProfile = isset($_SESSION['user']['id']) && $_SESSION['user']['id'] === $targetUser['id'];

// Xử lý link Avatar
$avatarUrl = app_avatar_url($targetUser['avatar'] ?? '', $targetUser['name'] ?? 'User');
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptName === '/' || $scriptName === '\\') ? '' : $scriptName;

if (strpos($avatarUrl, 'default-avatar.png') !== false || empty(trim($avatarUrl))) {
    $avatarUrl = $basePath . '/assets/images/default-avatar.png';
}

$roleLabel = $targetUser['role'] === 'admin' ? 'Quản trị viên' : ($targetUser['role'] === 'driver' ? 'Tài xế' : 'Khách hàng');
?>

<div class="profile-page-wrapper">

<!-- HEADER: AVATAR & THÔNG TIN CƠ BẢN -->
    <div class="profile-header-top">
        <div class="profile-avatar-wrapper">
            <img src="<?= htmlspecialchars($avatarUrl) ?>" onerror="this.src='<?= htmlspecialchars($basePath . '/assets/images/default-avatar.png') ?>'" alt="Avatar" class="profile-avatar-img">
            
            <?php if ($isOwnProfile): ?>
            <!-- Nút Upload ảnh đại diện tinh gọn -->
            <form action="/profile/update-avatar" method="POST" enctype="multipart/form-data" id="avatarUploadForm">
                <?= function_exists('app_csrf_field') ? app_csrf_field() : '' ?>
                <label for="avatar-input" class="profile-avatar-edit-btn" title="Thay đổi ảnh đại diện">
                    <span class="material-symbols-outlined" style="font-size: 16px;">photo_camera</span>
                </label>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;" onchange="document.getElementById('avatarUploadForm').submit();">
            </form>
            <?php endif; ?>
        </div>
        
        <div class="profile-header-info">
            <h2><?= htmlspecialchars($targetUser['name']) ?></h2>
            <div>
                <span class="profile-badge"><?= $roleLabel ?></span>
                <span class="profile-join-date">Tham gia từ <?= date('d/m/Y', strtotime($targetUser['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="profile-grid">
        
        <!-- CỘT TRÁI: THÔNG TIN LIÊN HỆ & ĐỔI MẬT KHẨU -->
        <div class="profile-col-left">
            
            <!-- SECTION 1: THÔNG TIN LIÊN HỆ -->
            <div class="profile-section">
                <h3 class="profile-section-title" style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined">manage_accounts</span> Thông tin liên hệ
                </h3>
                
                <form action="<?= $isOwnProfile ? '/profile/update-info' : '#' ?>" method="<?= $isOwnProfile ? 'POST' : 'GET' ?>">
                    <?php if ($isOwnProfile && function_exists('app_csrf_field')): ?>
                        <?= app_csrf_field() ?>
                    <?php endif; ?>
                    
                    <div class="profile-form-group">
                        <label>Email (Tài khoản đăng nhập)</label>
                        <input type="email" value="<?= htmlspecialchars($targetUser['email']) ?>" readonly>
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;">Email không thể thay đổi để đảm bảo bảo mật.</small>
                    </div>
                    
                    <div class="profile-form-row">
                        <div class="profile-form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($targetUser['name']) ?>" required <?= !$isOwnProfile ? 'readonly' : '' ?> data-error="Vui lòng nhập họ và tên.">
                        </div>
                        <div class="profile-form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($targetUser['phone']) ?>" required <?= !$isOwnProfile ? 'readonly' : '' ?> data-error="Vui lòng nhập số điện thoại.">
                        </div>
                    </div>

                    <?php if ($targetUser['role'] === 'driver'): ?>
                    <div class="profile-form-group">
                        <label>Biển số xe</label>
                        <input type="text" name="license_plate" value="<?= htmlspecialchars($targetUser['license_plate'] ?? '') ?>" readonly placeholder="Chưa cập nhật">
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;">* Để thay đổi Biển số xe, vui lòng liên hệ Quản trị viên để xác minh lại giấy tờ.</small>
                    </div>
                    <?php endif; ?>

                    <?php if ($isOwnProfile): ?>
                        <button type="submit" class="profile-btn-primary" style="background: #2563eb; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Cập nhật thông tin</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- SECTION 2: ĐỔI MẬT KHẨU -->
            <?php if ($isOwnProfile): ?>
            <div class="profile-section">
                <h3 class="profile-section-title" style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined">lock</span> Đổi mật khẩu
                </h3>
                
                <form action="/profile/change-password" method="POST">
                    <?= function_exists('app_csrf_field') ? app_csrf_field() : '' ?>
                    <div class="profile-form-group">
                        <label>Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" required placeholder="Nhập mật khẩu hiện tại..." data-error="Vui lòng nhập mật khẩu hiện tại.">
                    </div>
                    
                    <div class="profile-form-row">
                        <div class="profile-form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password" name="new_password" required placeholder="Nhập mật khẩu mới..." data-error="Vui lòng nhập mật khẩu mới.">
                        </div>
                        <div class="profile-form-group">
                            <label>Xác nhận mật khẩu</label>
                            <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới..." data-error="Vui lòng xác nhận mật khẩu mới.">
                        </div>
                    </div>

                    <button type="submit" class="profile-btn-secondary" style="background: #64748b; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; margin-top: 10px;">Thay đổi mật khẩu</button>
                </form>
            </div>
            <?php endif; ?>

        </div> <!-- End Cột Trái -->


        <!-- CỘT PHẢI: TÁC VỤ MỞ RỘNG (ĐĂNG KÝ TÀI XẾ / ĐÁNH GIÁ) -->
        <div class="profile-col-right">
            
            <!-- ĐĂNG KÝ LÀM TÀI XẾ CHO KHÁCH HÀNG -->
            <?php if ($isOwnProfile && $targetUser['role'] === 'user'): ?>
                <?php if (isset($driverProfile)): ?>
                    <div class="driver-reg-box" style="border-top: 4px solid var(--primary);">
                        <h3 class="driver-reg-title"><span class="material-symbols-outlined" style="color: var(--primary);">hourglass_empty</span> Đang chờ duyệt hồ sơ</h3>
                        <p style="font-size: 14px; color: var(--text-muted);">Hồ sơ đăng ký tài xế của bạn (Biển số: <strong><?= htmlspecialchars($driverProfile['license_plate']) ?></strong>) đang được Quản trị viên xem xét. Bạn sẽ chính thức trở thành tài xế sau khi được phê duyệt.</p>
                    </div>
                <?php else: ?>
                    <div class="driver-reg-box">
                        <h3 class="driver-reg-title">
                            <span class="material-symbols-outlined" style="color: var(--primary);">local_shipping</span> Đăng ký làm tài xế
                        </h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Gia nhập đội ngũ NUN Express để tăng thu nhập và làm chủ thời gian của bạn.</p>
                        
                        <form action="/profile/register-driver" method="POST" enctype="multipart/form-data">
                            <?= function_exists('app_csrf_field') ? app_csrf_field() : '' ?>
                            <div class="profile-form-group">
                                <label>Biển số xe <span style="color: var(--danger);">*</span></label>
                                <input type="text" name="license_plate" required placeholder="VD: 59A-123.45" data-error="Vui lòng nhập biển số xe.">
                            </div>
                            
                            <div class="profile-form-group">
                                <label>Ảnh Hồ sơ tổng hợp (CCCD, Bằng lái, Cà vẹt) <span style="color: var(--danger);">*</span></label>
                                <label class="upload-dashed-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-align: center;">
                                    <span class="material-symbols-outlined" style="color: var(--primary); font-size: 28px;">cloud_upload</span>
                                    <div style="font-size: 13px; font-weight: 600; margin-top: 5px;">Tải lên hình ảnh</div>
                                    <input type="file" name="vehicle_registration" accept="image/*" required data-error="Vui lòng tải lên ảnh giấy tờ xe." style="margin-top: 10px; font-size: 13px; width: 100%;" onchange="previewVehicleImage(this)">
                                </label>
                                <div id="vehicle-image-preview" style="display: none; margin-top: 12px; position: relative;">
                                    <img src="" alt="Preview" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color);">
                                    <button type="button" onclick="clearVehiclePreview()" style="position: absolute; top: -10px; right: -10px; background: var(--status-red); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-outlined" style="font-size: 16px;">close</span></button>
                                </div>
                                <small style="color: var(--text-muted); display: block; margin-top: 4px;">* Mẹo: Bạn hãy xếp Căn cước công dân, Bằng lái xe và Cà vẹt xe cạnh nhau rồi chụp chung vào 1 bức ảnh duy nhất để hệ thống duyệt nhanh chóng.</small>
                            </div>
                            
                            <div class="profile-form-group">
                                <label style="text-transform: uppercase; color: var(--primary-blue); display: flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">gavel</span> 
                                    Quy tắc vận hành dành cho Tài xế
                                </label>
                                <div class="rules-box">
                                    <strong style="color: var(--primary-blue);">1. Tuân thủ pháp luật:</strong> Cam kết KHÔNG nhận vận chuyển hàng cấm, chất gây nghiện, chất cháy nổ, vũ khí hoặc động vật hoang dã. Tài xế chịu hoàn toàn trách nhiệm trước pháp luật nếu cố tình vi phạm.<br><br>
                                    
                                    <strong style="color: var(--primary-blue);">2. Đảm bảo thời gian (SLA):</strong> Nhận và giao hàng đúng thời gian cam kết. Không cố tình "ngâm đơn", bấm nhận chuyến nhưng không di chuyển đi lấy hàng gây ảnh hưởng đến hệ thống.<br><br>
                                    
                                    <strong style="color: var(--primary-blue);">3. Thái độ phục vụ:</strong> Luôn giữ thái độ chuẩn mực, lịch sự với khách hàng. Nghiêm cấm mọi hành vi phát ngôn thiếu tôn trọng, quấy rối hoặc đe dọa khách hàng dưới mọi hình thức.<br><br>
                                    
                                    <strong style="color: var(--primary-blue);">4. Minh bạch & Trung thực:</strong> Cung cấp hình ảnh minh chứng giao/nhận hàng rõ ràng tại hiện trường. Nghiêm cấm sử dụng phần mềm giả mạo định vị GPS (Fake GPS) hoặc bấm hoàn thành đơn khi chưa giao hàng.<br><br>
                                    
                                    <strong style="color: var(--primary-blue);">5. Trách nhiệm tài chính:</strong> Có trách nhiệm đền bù nếu làm hư hỏng, thất thoát hàng hóa. Phải nộp lại tiền Thu hộ (COD) cho nền tảng đúng hạn và luôn duy trì số dư ví điện tử lớn hơn 0đ để hệ thống cấp đơn.
                                </div>
                                
                                <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer; background: var(--light-blue); padding: 12px; border: 1px solid #bfdbfe; border-radius: 4px; margin-top: 10px;">
                                    <input type="checkbox" name="accept_rules" value="1" required data-error="Vui lòng đánh dấu xác nhận cam kết tuân thủ quy tắc để tiếp tục." style="margin-top:2px; width:16px; height:16px; flex-shrink: 0;">
                                    <span style="font-size: 13px; font-weight: 600; color: var(--primary-blue); line-height: 1.4;">
                                        Tôi đã đọc, hiểu rõ và cam kết tuân thủ toàn bộ Quy tắc vận hành của nền tảng NUN Express. Tôi đồng ý chịu phạt tài chính hoặc bị khóa tài khoản vĩnh viễn nếu vi phạm.
                                    </span>
                                </label>
                            </div>

                            <button type="submit" class="profile-btn-primary" style="background: #2563eb; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 15px;">Gửi yêu cầu đăng ký</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ĐÁNH GIÁ (NẾU LÀ TÀI XẾ) -->
            <?php if ($targetUser['role'] === 'driver' && isset($ratingInfo)): ?>
                <div class="driver-reg-box" style="background: var(--bg-white);">
                    <h3 class="driver-reg-title"><span class="material-symbols-outlined" style="color: #f59e0b;">star</span> Đánh giá tài xế</h3>
                    
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <div style="font-size: 40px; font-weight: 800; color: #f59e0b;"><?= htmlspecialchars($ratingInfo['avg']) ?></div>
                        <div>
                            <div style="color: #f59e0b; font-size: 20px;">
                                <?php 
                                $avg = round((float)$ratingInfo['avg']);
                                for ($i = 1; $i <= 5; $i++) echo $i <= $avg ? '★' : '☆';
                                ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-muted);">Dựa trên <?= htmlspecialchars($ratingInfo['total']) ?> đánh giá</div>
                        </div>
                    </div>

                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($reviews)): ?>
                            <p style="color: var(--text-muted); font-size: 13px; text-align: center;">Chưa có đánh giá nào.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-item">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <strong style="font-size: 14px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Khách hàng') ?></strong>
                                        <span style="color: var(--text-muted); font-size: 12px;"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                    </div>
                                    <div style="color: #f59e0b; font-size: 12px; margin-bottom: 5px;">
                                        <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                    </div>
                                    <p style="margin: 0; color: var(--text-main); font-size: 13px;">
                                        <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div> <!-- End Cột Phải -->
    </div> <!-- End Grid -->
</div>

<script>
    function previewVehicleImage(input) {
        const previewContainer = document.getElementById('vehicle-image-preview');
        const img = previewContainer.querySelector('img');
        const uploadBox = input.closest('.upload-dashed-box');
        
        if (input.files && input.files[0]) {
            img.src = URL.createObjectURL(input.files[0]);
            previewContainer.style.display = 'block';
            if(uploadBox) uploadBox.style.display = 'none';
        }
    }

    function clearVehiclePreview() {
        const input = document.querySelector('input[name="vehicle_registration"]');
        const previewContainer = document.getElementById('vehicle-image-preview');
        const uploadBox = document.querySelector('.upload-dashed-box');
        
        if(input) input.value = '';
        if(previewContainer) previewContainer.style.display = 'none';
        if(uploadBox) uploadBox.style.display = 'flex';
    }
</script>

<?php require_once __DIR__ . '/../layouts/user_footer.php'; ?>
