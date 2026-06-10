<?php
/**
 * @var string $pageTitle
 * @var array $order
 * @var array|bool|null $existingReview
 * @var array $history
 * @var array $ratingInfo
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<link rel="stylesheet" href="/assets/css/style.css">

<div class="od-container">
    
    <a href="/user/orders" class="od-header-nav">
        <span class="material-symbols-outlined">arrow_back</span>
        Chi tiết đơn hàng
    </a>

    <div class="od-grid">
        
        <!-- ================= CỘT TRÁI ================= -->
        <div>
            <!-- THÔNG TIN MÃ ĐƠN & PIN -->
            <div class="od-card" style="padding-bottom: 32px;">
                <div class="od-order-header">
                    <div>
                        <div class="od-order-label">Mã vận đơn</div>
                        <div class="od-order-code">#<?= app_e($order['tracking_code']) ?></div>
                    </div>
                    <?php 
                        $s = $order['status'];
                        $color = \App\Models\Order::getStatusColor($s);
                        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
                        $bg = "rgba($r, $g, $b, 0.15)";
                    ?>
                    <div class="od-pill" style="background-color: <?= $bg ?>; color: <?= $color ?>; border-color: <?= $color ?>;">
                        <?= app_e(app_status_label($s)) ?>
                    </div>
                </div>

                <?php if (!empty($order['delivery_pin']) && in_array($order['status'], ['pending', 'awaiting_payment', 'searching_driver', 'accepted', 'picking_up', 'in_transit', 'shipping'])): ?>
                    <div class="od-pin-box">
                        <div class="od-pin-header">Mã PIN Nhận Hàng (Đưa cho tài xế)</div>
                        <div class="od-pin-number">
                            <?= app_e($order['delivery_pin']) ?>
                            <span class="material-symbols-outlined" style="font-size: 32px; opacity: 0.8;">lock</span>
                        </div>
                        <div class="od-pin-footer">Vui lòng cung cấp mã PIN này cho tài xế để nhận hàng. Không chia sẻ mã này cho người lạ.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BẢN ĐỒ -->
            <div class="od-card" style="padding: 0; border: none;">
                <div class="od-map-container">
                    <div class="od-map-badge">
                        Dự kiến đến / Live GPS
                    </div>
                    <div id="route-map" style="width: 100%; height: 100%; background: #f8fafc;"></div>
                </div>
            </div>

            <!-- NẾU CHỜ THANH TOÁN -->
            <?php if ($order['status'] === 'awaiting_payment'): ?>
                <a href="/user/orders/payment/<?= app_e($order['tracking_code']) ?>" class="od-btn od-btn-primary" style="margin-bottom: 24px; padding: 16px;">
                    <span class="material-symbols-outlined">qr_code_scanner</span> 
                    Tiếp tục thanh toán đơn hàng này
                </a>
            <?php endif; ?>

            <!-- LỊCH SỬ GIAO HÀNG -->
            <div class="od-card">
                <h2 class="od-card-title">Lịch Sử Giao Hàng</h2>
                <?php if (empty($history)): ?>
                    <p style="color: var(--text-muted); font-size: 13px;">Chưa có cập nhật.</p>
                <?php else: ?>
                    <div class="od-history-list">
                        <?php foreach (array_reverse($history) as $idx => $h): 
                            $hColor = \App\Models\Order::getStatusColor($h['status']);
                            list($r, $g, $b) = sscanf($hColor, "#%02x%02x%02x");
                            $shadow = "rgba($r, $g, $b, 0.2)";
                        ?>
                            <div class="od-history-item <?= $idx === 0 ? 'active' : '' ?>">
                                <div class="od-history-dot" <?= $idx === 0 ? "style=\"background-color: {$hColor}; box-shadow: 0 0 0 3px {$shadow}; border-color: #fff;\"" : "" ?>></div>
                                <div class="od-history-title" <?= $idx === 0 ? "style=\"color: {$hColor};\"" : "" ?>><?= app_e(app_status_label($h['status'])) ?></div>
                                <div class="od-history-desc"><?= $h['description'] ?? 'Hệ thống đã ghi nhận trạng thái mới.' ?></div>
                                <div class="od-history-time">
                                    <?= date('H:i d/m/Y', strtotime($h['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= CỘT PHẢI ================= -->
        <div>
            
            <!-- TÀI XẾ PHỤ TRÁCH -->
            <?php if (!empty($order['driver_id'])): ?>
            <div class="od-card">
                <h2 class="od-card-title">Tài Xế Phụ Trách</h2>
                <a href="/profile/<?= $order['driver_id'] ?>" class="od-driver-profile" style="text-decoration: none; color: inherit; display: flex; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1" title="Nhấn để xem hồ sơ và đánh giá chi tiết">
                    <?php
                        $dAvatarUrl = app_avatar_url($order['driver_avatar'] ?? '', $order['driver_name'] ?? 'D');
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                        if (empty($dAvatarUrl) || strpos($dAvatarUrl, 'default-avatar.png') !== false) {
                            $dAvatarUrl = $basePath . '/assets/images/default-avatar.png';
                        }
                    ?>
                    <img src="<?= htmlspecialchars($dAvatarUrl) ?>" alt="Driver">
                    <div>
                        <div class="od-driver-name" style="display: flex; align-items: center; gap: 4px;">
                            <?= app_e($order['driver_name']) ?>
                            <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary);">open_in_new</span>
                        </div>
                        <div class="od-driver-meta">
                            ★ <?= app_e($ratingInfo['avg'] ?? '0.0') ?> (<?= app_e($ratingInfo['total'] ?? '0') ?> đánh giá) <br> 
                            Biển số: <?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?>
                        </div>
                    </div>
                </a>
                <div class="od-btn-group">
                    <a href="tel:<?= app_e($order['driver_phone']) ?>" class="od-btn od-btn-outline">
                        <span class="material-symbols-outlined" style="font-size: 18px;">call</span> Gọi điện
                    </a>
                    <button onclick="toggleChat()" class="od-btn od-btn-outline">
                        <span class="material-symbols-outlined" style="font-size: 18px;">chat</span> Nhắn tin
                    </button>
                </div>

                <?php if ($order['status'] === 'completed' && empty($existingReview)): ?>
                    <a href="/user/orders/review/<?= $order['id'] ?>" class="od-btn od-btn-primary" style="margin-top: 12px; width: 100%; box-sizing: border-box;">Đánh giá tài xế</a>
                <?php elseif (!empty($existingReview)): ?>
                    <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">Đánh giá của bạn:</div>
                        <div style="color: var(--star-active); font-size: 16px; margin-bottom: 4px;">
                            <?php for($i = 0; $i < $existingReview['rating']; $i++) echo '★'; ?><?php for($i = 0; $i < 5 - $existingReview['rating']; $i++) echo '<span style="color: var(--star-inactive);">★</span>'; ?>
                        </div>
                        <?php if (!empty($existingReview['comment'])): ?>
                            <div style="font-size: 13px; color: var(--text-muted); font-style: italic;">"<?= app_e($existingReview['comment']) ?>"</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- THÔNG TIN VẬN CHUYỂN -->
            <div class="od-card">
                <h2 class="od-card-title">Thông Tin Vận Chuyển</h2>
                
                <div class="od-info-row">
                    <span class="od-info-label">Trọng lượng</span>
                    <span class="od-info-value"><?= app_e($order['weight'] ?? '0') ?> kg</span>
                </div>
                <div class="od-info-row">
                    <span class="od-info-label">Loại dịch vụ</span>
                    <span class="od-info-value" style="color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? 'standard') ?>;">
                        <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                    </span>
                </div>
                <div class="od-info-row">
                    <span class="od-info-label">Chi phí (<?= ($order['payment_method'] ?? '') === 'transfer' ? 'Chuyển khoản' : 'Tiền mặt' ?>)</span>
                    <span class="od-info-value text-primary"><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></span>
                </div>

                <!-- Địa chỉ (Hiển thị dạng dòng thời gian như mockup) -->
                <div class="od-address-timeline">
                    <div class="od-address-item">
                        <div class="od-address-icon inner-dot"></div>
                        <div class="od-address-content">
                            <div class="label">Điểm lấy hàng</div>
                            <div class="address"><?= app_e($order['sender_address'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="od-address-item">
                        <div class="od-address-icon inner-dot" style="border-color: var(--status-danger);"></div>
                        <div class="od-address-content">
                            <div class="label">Điểm giao hàng</div>
                            <div class="address"><?= app_e($order['receiver_address'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($order['note'])): ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--bg-body); border-radius: 4px; font-size: 13px; font-style: italic;">
                        <strong>Ghi chú:</strong> "<?= app_e($order['note']) ?>"
                    </div>
                <?php endif; ?>
            </div>

            <!-- VÙNG BÁO CÁO / KHIẾU NẠI (LUÔN HIỂN THỊ KHI CÓ THỂ) -->
            <?php if ($order['status'] === 'disputed'): ?>
                <div class="od-report-box" style="background: #fef2f2; text-align: center;">
                    <div style="color: var(--status-danger); font-weight: bold; margin-bottom: 8px;">Đơn hàng đang bị khiếu nại</div>
                    <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">Quản trị viên đang xem xét sự cố của đơn hàng này. Chúng tôi sẽ thông báo kết quả trong vòng 24h.</p>
                    <form action="/user/orders/withdraw-dispute/<?= app_e($order['tracking_code']) ?>" method="POST">
                        <button type="submit" class="od-btn od-btn-danger" style="width: 100%;">Rút lại khiếu nại</button>
                    </form>
                </div>
            <?php elseif (in_array($order['status'], ['completed', 'returning', 'returned', 'cancelled'])): ?>
                <!-- Bỏ display:none, form báo cáo sẽ luôn hiển thị -->
                <form id="dispute-form" class="od-report-box" action="/user/orders/dispute/<?= app_e($order['tracking_code']) ?>" method="POST" enctype="multipart/form-data">
                    <div style="font-size: 16px; font-weight: 700; color: #be123c; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">gavel</span> Yêu cầu hỗ trợ / Khiếu nại
                    </div>
                    
                    <label>Chọn loại sự cố <span style="color: red;">*</span></label>
                    <select name="issue_type" required data-error="Vui lòng chọn loại sự cố.">
                        <option value="">-- Chọn vấn đề bạn gặp phải --</option>
                        <option value="Hàng hóa bị hư hỏng/bể vỡ">Hàng hóa bị hư hỏng/bể vỡ</option>
                        <option value="Thất lạc/Mất hàng">Thất lạc/Mất hàng</option>
                        <option value="Tài xế thu sai cước phí">Tài xế thu sai cước phí</option>
                        <option value="Thái độ tài xế không chuẩn mực">Thái độ tài xế không chuẩn mực</option>
                        <option value="Tài xế bấm hoàn thành nhưng chưa giao">Tài xế bấm hoàn thành nhưng chưa giao</option>
                        <option value="Sự cố khác">Sự cố khác</option>
                    </select>

                    <label>Chi tiết sự cố <span style="color: red;">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Mô tả chi tiết sự việc để được hỗ trợ nhanh nhất..." data-error="Vui lòng nhập chi tiết báo cáo."></textarea>
                    
                    <label>Hình ảnh minh chứng (Tùy chọn)</label>
                    <input type="file" name="proof_image" accept="image/*" style="background: white; cursor: pointer;">

                    <button type="submit" class="od-btn od-btn-primary" style="background: #e11d48; margin-top: 16px; width: 100%;">Gửi khiếu nại</button>
                </form>
            <?php endif; ?>

            <!-- VÙNG HỦY ĐƠN HÀNG -->
            <?php if (in_array($order['status'], ['awaiting_payment', 'searching_driver', 'accepted', 'picking_up'])): ?>
                <div class="od-card" style="margin-top: 24px; border-color: #fecaca; background: #fef2f2;">
                    <h3 style="margin-top: 0; color: #b91c1c; font-size: 16px;">Hủy đơn hàng</h3>
                    <form id="cancel-form" action="/user/orders/cancel/<?= app_e($order['tracking_code']) ?>" method="POST">
                        <select name="cancel_reason" required style="width: 100%; padding: 12px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 14px; margin-bottom: 12px; outline: none;" data-error="Vui lòng chọn lý do hủy đơn.">
                            <option value="">-- Chọn lý do hủy --</option>
                            <option value="Thay đổi ý định">Thay đổi ý định</option>
                            <option value="Chờ quá lâu">Chờ quá lâu</option>
                        </select>
                        <button type="submit" class="od-btn od-btn-danger" style="width: 100%;">Xác nhận hủy đơn</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ================= SCRIPT & MAP GIỮ NGUYÊN ================= -->
<?php if (!empty($order['driver_id'])): ?>
<?= app_component('chat_widget', [
    'orderId' => $order['id'],
    'receiverId' => $order['driver_id'],
    'receiverRole' => 'Tài xế'
]) ?>
<script>
    let myMapHelper = null;
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof pusher !== 'undefined' && typeof currentUserId !== 'undefined') {
            const channel = pusher.channel('notify-user-' + currentUserId) || pusher.subscribe('notify-user-' + currentUserId);
            channel.bind('new_chat_message', function(data) {
                if (Number(data.order_id) === chatOrderId) {
                    const body = document.getElementById('chat-body');
                    if (body.style.display !== 'none' && body.style.display !== '') {
                        loadChatMessages();
                    } else {
                        const title = document.getElementById('chat-header-title');
                        if (title) {
                            title.style.color = '#fde047'; 
                            title.innerText = 'Chat (Có tin nhắn mới!)';
                        }
                    }
                }
            });
        }
    });
</script>
<?php endif; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="/assets/js/map-helper.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.getElementById('route-map');
        if (!mapContainer) return;
        
        myMapHelper = new MapHelper('route-map');
        myMapHelper.initTracking({
            orderStatus: '<?= $order['status'] ?>',
            trackingCode: '<?= $order['tracking_code'] ?>',
            driverLat: <?= (float)($order['driver_lat'] ?? 0) ?>,
            driverLng: <?= (float)($order['driver_lng'] ?? 0) ?>,
            senderLat: <?= (float)($order['sender_lat'] ?? 0) ?>,
            senderLng: <?= (float)($order['sender_lng'] ?? 0) ?>,
            receiverLat: <?= (float)($order['receiver_lat'] ?? 0) ?>,
            receiverLng: <?= (float)($order['receiver_lng'] ?? 0) ?>
        });

        if (typeof pusher !== 'undefined') {
            const trackingChannel = pusher.subscribe('tracking-<?= $order['tracking_code'] ?>');
            trackingChannel.bind('location_update', function(data) {
                if (myMapHelper && typeof myMapHelper.updateDriverMarker === 'function') {
                    myMapHelper.updateDriverMarker(data.lat, data.lng);
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>