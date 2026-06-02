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

if (strpos($avatarUrl, 'default-avatar.png') !== false) {
    $avatarUrl = $basePath . '/assets/images/default-avatar.png';
}
if (empty($avatarUrl)) {
    $avatarUrl = $basePath . '/assets/images/default-avatar.png';
}

$roleLabel = $targetUser['role'] === 'admin' ? 'Quản trị viên' : ($targetUser['role'] === 'driver' ? 'Tài xế' : 'Khách hàng');
?>



<div class="profile-container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <h2 style="margin-top: 0; margin-bottom: 20px; color: #0f172a;">Hồ sơ cá nhân</h2>


    <div class="profile-layout">
        <!-- CỘT TRÁI: AVATAR & THÔNG TIN CƠ BẢN -->
        <div class="profile-left">
            <div class="profile-avatar-card" style="background: #fff; padding: 30px 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); text-align: center;">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" onerror="this.onerror=null; this.src='<?= htmlspecialchars($basePath . '/assets/images/default-avatar.png') ?>'" alt="Avatar" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; margin-bottom: 15px;">
                <h3 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;"><?= htmlspecialchars($targetUser['name']) ?></h3>
                <span style="display: inline-block; background: #e2e8f0; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 500; margin-bottom: 15px;">
                    <?= $roleLabel ?>
                </span>
                <p style="color: #64748b; font-size: 14px; margin: 0 0 20px 0;">Tham gia từ: <?= date('d/m/Y', strtotime($targetUser['created_at'])) ?></p>

                <?php if ($isOwnProfile): ?>
                    <form action="/profile/update-avatar" method="POST" enctype="multipart/form-data" style="border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: left;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #334155; font-size: 14px;">Thay đổi ảnh đại diện</label>
                        <input type="file" name="avatar" accept="image/*" required style="width: 100%; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; margin-bottom: 10px; font-size: 14px;" oninvalid="this.setCustomValidity('Vui lòng chọn một ảnh để tải lên.')" onchange="this.setCustomValidity('')">
                        <button type="submit" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                            Tải ảnh lên
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT PHẢI: CÁC FORM CẬP NHẬT -->
        <div class="profile-right">
            
            <!-- FORM THÔNG TIN CÁ NHÂN -->
            <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: #3b82f6;">manage_accounts</span> Thông tin liên hệ
                </h3>
                
                <form action="<?= $isOwnProfile ? '/profile/update-info' : '#' ?>" method="<?= $isOwnProfile ? 'POST' : 'GET' ?>">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Email (Tài khoản đăng nhập)</label>
                        <input type="email" value="<?= htmlspecialchars($targetUser['email']) ?>" readonly style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; color: #64748b; font-size: 15px; outline: none;">
                        <small style="color: #94a3b8; display: block; margin-top: 4px;">Email không thể thay đổi để đảm bảo bảo mật.</small>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Họ và Tên</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($targetUser['name']) ?>" required <?= !$isOwnProfile ? 'readonly' : '' ?> style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'" oninvalid="this.setCustomValidity('Vui lòng nhập họ và tên.')" oninput="this.setCustomValidity('')">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Số điện thoại</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($targetUser['phone']) ?>" required <?= !$isOwnProfile ? 'readonly' : '' ?> style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'" oninvalid="this.setCustomValidity('Vui lòng nhập số điện thoại.')" oninput="this.setCustomValidity('')">
                        </div>
                    </div>

                    <?php if ($targetUser['role'] === 'driver'): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Biển số xe</label>
                        <input type="text" value="<?= htmlspecialchars($targetUser['license_plate'] ?? 'Chưa cập nhật') ?>" readonly style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; color: #64748b; font-size: 15px; outline: none;">
                        <small style="color: #94a3b8; display: block; margin-top: 4px;">* Để thay đổi Biển số xe, vui lòng liên hệ Quản trị viên để xác minh lại giấy tờ.</small>
                    </div>
                    <?php endif; ?>

                    <?php if ($isOwnProfile): ?>
                        <button type="submit" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            Lưu thông tin
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($isOwnProfile): ?>
                <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: #f59e0b;">lock</span> Đổi mật khẩu
                    </h3>
                    
                    <form action="/profile/change-password" method="POST">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" required placeholder="Nhập mật khẩu cũ..." style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none;" oninvalid="this.setCustomValidity('Vui lòng nhập mật khẩu hiện tại.')" oninput="this.setCustomValidity('')">
                        </div>
                    <div class="profile-form-row">
                        <div class="profile-form-col">
                                <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Mật khẩu mới</label>
                                <input type="password" name="new_password" required placeholder="Nhập mật khẩu mới..." style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none;" oninvalid="this.setCustomValidity('Vui lòng nhập mật khẩu mới.')" oninput="this.setCustomValidity('')">
                            </div>
                        <div class="profile-form-col">
                                <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Xác nhận mật khẩu</label>
                                <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới..." style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none;" oninvalid="this.setCustomValidity('Vui lòng xác nhận mật khẩu mới.')" oninput="this.setCustomValidity('')">
                            </div>
                        </div>
                        <button type="submit" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                            Đổi mật khẩu
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- FORM ĐĂNG KÝ LÀM TÀI XẾ CHO KHÁCH HÀNG -->
            <?php if ($isOwnProfile && $targetUser['role'] === 'user'): ?>
                <?php if (isset($driverProfile)): ?>
                <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
                    <h3 style="margin-top: 0; margin-bottom: 10px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: #f59e0b;">hourglass_empty</span> Đang chờ duyệt hồ sơ
                    </h3>
                    <p style="color: #475569; margin: 0; line-height: 1.5;">Hồ sơ đăng ký tài xế của bạn (Biển số: <strong><?= htmlspecialchars($driverProfile['license_plate']) ?></strong>) đang được Quản trị viên xem xét. Bạn sẽ chính thức trở thành tài xế sau khi được Admin phê duyệt và chuyển đổi quyền.</p>
                </div>
                <?php else: ?>
                <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: #10b981;">local_shipping</span> Đăng ký làm tài xế
                    </h3>
                    
                    <form action="/profile/register-driver" method="POST" enctype="multipart/form-data">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Biển số xe <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="license_plate" required placeholder="VD: 59A-123.45" style="width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none;" oninvalid="this.setCustomValidity('Vui lòng nhập biển số xe.')" oninput="this.setCustomValidity('')">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #475569;">Ảnh Hồ sơ tổng hợp (CCCD, Bằng lái, Cà vẹt) <span style="color: #ef4444;">*</span></label>
                            <input type="file" name="vehicle_registration" accept="image/*" required style="width: 100%; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; font-size: 14px;" oninvalid="this.setCustomValidity('Vui lòng tải lên ảnh giấy tờ xe.')" onchange="this.setCustomValidity('')">
                            <small style="color: #94a3b8; display: block; margin-top: 4px;">* Mẹo: Bạn hãy xếp Căn cước công dân, Bằng lái xe và Cà vẹt xe cạnh nhau rồi chụp chung vào 1 bức ảnh duy nhất để hệ thống duyệt nhanh chóng.</small>
                        </div>
                        
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: bottom; color: #ef4444;">gavel</span> 
                                Quy tắc vận hành dành cho Tài xế
                            </label>
                            
                            <!-- Khung cuộn chứa nội quy (Scrollbar) -->
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px; height: 180px; overflow-y: auto; font-size: 13px; line-height: 1.6; color: #334155; margin-bottom: 12px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                <strong style="color: #2563eb;">1. Tuân thủ pháp luật:</strong> Cam kết KHÔNG nhận vận chuyển hàng cấm, chất gây nghiện, chất cháy nổ, vũ khí hoặc động vật hoang dã. Tài xế chịu hoàn toàn trách nhiệm trước pháp luật nếu cố tình vi phạm.<br><br>
                                
                                <strong style="color: #2563eb;">2. Đảm bảo thời gian (SLA):</strong> Nhận và giao hàng đúng thời gian cam kết. Không cố tình "ngâm đơn", bấm nhận chuyến nhưng không di chuyển đi lấy hàng gây ảnh hưởng đến hệ thống.<br><br>
                                
                                <strong style="color: #2563eb;">3. Thái độ phục vụ:</strong> Luôn giữ thái độ chuẩn mực, lịch sự với khách hàng. Nghiêm cấm mọi hành vi phát ngôn thiếu tôn trọng, quấy rối hoặc đe dọa khách hàng dưới mọi hình thức.<br><br>
                                
                                <strong style="color: #2563eb;">4. Minh bạch & Trung thực:</strong> Cung cấp hình ảnh minh chứng giao/nhận hàng rõ ràng tại hiện trường. Nghiêm cấm sử dụng phần mềm giả mạo định vị GPS (Fake GPS) hoặc bấm hoàn thành đơn khi chưa giao hàng.<br><br>
                                
                                <strong style="color: #2563eb;">5. Trách nhiệm tài chính:</strong> Có trách nhiệm đền bù nếu làm hư hỏng, thất thoát hàng hóa. Phải nộp lại tiền Thu hộ (COD) cho nền tảng đúng hạn và luôn duy trì số dư ví điện tử lớn hơn 0đ để hệ thống cấp đơn.
                            </div>
                            
                            <!-- Checkbox bắt buộc đồng ý -->
                            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; background: #eff6ff; padding: 12px 16px; border: 1px solid #bfdbfe; border-radius: 6px; transition: 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#bfdbfe'">
                                <input type="checkbox" name="accept_rules" value="1" required oninvalid="this.setCustomValidity('Vui lòng đánh dấu xác nhận cam kết tuân thủ quy tắc để tiếp tục.')" onchange="this.setCustomValidity('')" style="margin-top: 3px; width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; flex-shrink: 0;">
                                <span style="font-size: 13px; color: #1e3a8a; font-weight: 600; line-height: 1.5;">
                                    Tôi đã đọc, hiểu rõ và cam kết tuân thủ toàn bộ Quy tắc vận hành của nền tảng NUN Express. Tôi đồng ý chịu phạt tài chính hoặc bị khóa tài khoản vĩnh viễn nếu vi phạm.
                                </span>
                            </label>
                        </div>

                        <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            Gửi yêu cầu đăng ký
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- KHU VỰC ĐÁNH GIÁ (NẾU LÀ TÀI XẾ) -->
            <?php if ($targetUser['role'] === 'driver' && isset($ratingInfo)): ?>
                <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: #ea580c;">star</span> Đánh giá tài xế
                    </h3>
                    
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; background: #fff7ed; padding: 15px; border-radius: 8px; border: 1px solid #ffedd5;">
                        <div style="font-size: 48px; font-weight: 800; color: #ea580c; line-height: 1;">
                            <?= htmlspecialchars($ratingInfo['avg']) ?>
                        </div>
                        <div>
                            <div style="color: #ea580c; font-size: 24px; margin-bottom: 5px;">
                                <?php 
                                $avg = round((float)$ratingInfo['avg']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $avg ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div style="color: #9a3412; font-weight: 500;">Dựa trên <?= htmlspecialchars($ratingInfo['total']) ?> đánh giá</div>
                        </div>
                    </div>

                    <!-- Danh sách bình luận -->
                    <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                        <?php if (empty($reviews)): ?>
                            <p style="color: #64748b; font-style: italic; text-align: center; padding: 20px 0;">Chưa có đánh giá nào.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                        <strong style="color: #334155;"><?= htmlspecialchars($rev['customer_name'] ?? 'Khách hàng') ?></strong>
                                        <span style="color: #94a3b8; font-size: 12px;"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                    </div>
                                    <div style="color: #f59e0b; font-size: 14px; margin-bottom: 5px;">
                                        <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                    </div>
                                    <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                                        <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/user_footer.php'; ?>
