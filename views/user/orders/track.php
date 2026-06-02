<?php
/**
 * @var string $pageTitle
 * @var array $order
 * @var array|bool|null $existingReview
 * @var array $history
 * @var array $ratingInfo
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="track-header-v2">
        <a href="/user/orders" class="btn-back-square"><span class="material-symbols-outlined">arrow_back</span></a>
        <div>
            <h2 class="track-title-v2">Chi tiết đơn hàng</h2>
            <div class="track-subtitle-v2">Cập nhật lần cuối: <?= app_datetime($order['updated_at'] ?? $order['scheduled_at'], 'H:i d/m/Y') ?></div>
        </div>
    </div>


    <div class="track-layout-grid">
        
        <div>
            <?php if (!empty($order['delivery_pin']) && in_array($order['status'], ['pending', 'awaiting_payment', 'searching_driver', 'accepted', 'picking_up', 'in_transit', 'shipping'])): ?>
                <div style="background: #fefce8; padding: 16px; border-radius: 4px; border: 2px dashed #facc15; margin-bottom: 24px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <span style="font-size: 13px; color: #a16207; font-weight: 700; display: block; margin-bottom: 6px; text-transform: uppercase;">Mã PIN cung cấp cho tài xế khi nhận hàng</span>
                    <strong style="font-size: 36px; letter-spacing: 12px; color: var(--primary); margin-left: 12px;"><?= app_e($order['delivery_pin']) ?></strong>
                    <div style="font-size: 12px; color: #854d0e; margin-top: 8px;">(Vui lòng báo Người nhận đọc mã này cho Tài xế)</div>
                </div>
            <?php endif; ?>

            <div class="t-card">
                <div class="t-order-header">
                    <div>
                        <span class="t-card-title">MÃ ĐƠN HÀNG</span>
                        <div class="t-order-code">#<?= app_e($order['tracking_code']) ?></div>
                    </div>
                    <?php 
                        $s = $order['status'];
                        $pillClass = 'pending';
                        if (in_array($s, ['searching_driver', 'accepted', 'picking_up'])) $pillClass = 'warning';
                        elseif (in_array($s, ['in_transit', 'shipping'])) $pillClass = 'shipping';
                        elseif ($s === 'completed') $pillClass = 'success';
                        elseif ($s === 'cancelled') $pillClass = 'danger';
                    ?>
                    <span class="pill-status <?= $pillClass ?>"><?= app_e(app_status_label($s)) ?></span>
                </div>
                
                <div class="t-info-row">
                    <span class="label">Dịch vụ</span>
                    <span class="value" style="color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? 'standard') ?>; font-weight: 700;">
                        <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                    </span>
                </div>
                <div class="t-info-row">
                    <span class="label">Trọng lượng</span>
                    <span class="value"><?= app_e($order['weight'] ?? '0') ?> kg</span>
                </div>
                <div class="t-info-row">
                    <span class="label">Ngày Hẹn Lấy Hàng</span>
                    <span class="value"><?= date('d/m/Y', strtotime($order['scheduled_at'])) ?></span>
                </div>
                <?php if (!empty($order['note'])): ?>
                <div class="t-info-row" style="flex-direction: column; align-items: flex-start; gap: 4px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border-color);">
                    <span class="label">Ghi chú cho tài xế:</span>
                    <span class="value" style="text-align: left; font-weight: normal; font-style: italic;">"<?= app_e($order['note']) ?>"</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($order['status'] === 'awaiting_payment'): ?>
                <a href="/user/orders/payment/<?= app_e($order['tracking_code']) ?>" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--primary); color: #fff; padding: 14px; border-radius: 4px; font-size: 14px; font-weight: 600; text-decoration: none; margin-bottom: 24px; width: 100%; box-shadow: 0 4px 6px rgba(37,99,235,0.15); transition: 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='var(--primary)'">
                    <span class="material-symbols-outlined" style="font-size: 20px;">qr_code_scanner</span> 
                    Tiếp tục thanh toán đơn hàng này
                </a>
            <?php endif; ?>

            <?php if (!empty($order['driver_id'])): ?>
                <div class="t-card">
                    <div class="t-card-title">THÔNG TIN TÀI XẾ</div>
                    <div class="t-driver-profile">
                        <?php
                            $dAvatarUrl = app_avatar_url($order['driver_avatar'] ?? '', $order['driver_name'] ?? 'D');
                            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            if (empty($dAvatarUrl) || strpos($dAvatarUrl, 'default-avatar.png') !== false) {
                                $dAvatarUrl = $basePath . '/assets/images/default-avatar.png';
                            }
                        ?>
                        <img src="<?= htmlspecialchars($dAvatarUrl) ?>" alt="Driver">
                        <div>
                            <div class="t-driver-name"><?= app_e($order['driver_name']) ?></div>
                            <div class="t-driver-meta">★ <?= app_e($ratingInfo['avg'] ?? '0.0') ?> (<?= app_e($ratingInfo['total'] ?? '0') ?> đánh giá) <br> Biển số: <?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?></div>
                        </div>
                    </div>                    
                    <div class="t-action-grid">
                        <a href="tel:<?= app_e($order['driver_phone']) ?>" class="btn-outline-action">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span> Gọi điện
                        </a>
                        <button onclick="toggleChat()" class="btn-outline-action" style="border: none; cursor: pointer;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">chat</span> Nhắn tin
                        </button>
                    </div>

                    <!-- Chỉ hiển thị nút khi đơn hàng đã xong VÀ chưa có review nào -->
                    <?php if ($order['status'] === 'completed' && empty($existingReview)): ?>
                        <a href="/user/orders/review/<?= $order['id'] ?>" class="btn-full-outline">Đánh giá tài xế</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'disputed'): ?>
                <div style="background: #fef2f2; padding: 16px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 16px; text-align: center;">
                    <div style="color: var(--danger); font-weight: bold; margin-bottom: 8px;">Đơn hàng đang bị khiếu nại</div>
                    <p style="font-size: 13px; color: #475569; margin-bottom: 12px;">Quản trị viên đang xem xét sự cố của đơn hàng này. Chúng tôi sẽ thông báo kết quả trong vòng 24h.</p>
                    <form action="/user/orders/withdraw-dispute/<?= app_e($order['tracking_code']) ?>" method="POST">
                        <button type="submit" class="btn-full-outline" style="margin-top: 0;">Rút lại khiếu nại</button>
                    </form>
                </div>
            <?php elseif (in_array($order['status'], ['completed', 'returning', 'returned', 'cancelled'])): ?>
                <div id="btn-show-dispute" style="color: #be123c; font-size: 14px; font-weight: 600; text-align: center; margin-top: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px; border: 1px dashed #fca5a5; border-radius: 6px; background: #fff1f2; transition: 0.2s;" onclick="document.getElementById('dispute-form').style.display = 'block'; this.style.display = 'none';" onmouseover="this.style.background='#ffe4e6'" onmouseout="this.style.background='#fff1f2'">
                    <span class="material-symbols-outlined" style="font-size: 18px;">warning</span> Báo cáo sự cố / Yêu cầu khiếu nại
                </div>
                <form id="dispute-form" action="/user/orders/dispute/<?= app_e($order['tracking_code']) ?>" method="POST" enctype="multipart/form-data" style="display: none; background: #fff1f2; padding: 16px; border: 1px solid #fecdd3; border-radius: 6px; margin-top: 16px;">
                    <div style="font-weight: 600; color: #be123c; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">gavel</span>
                        Hỗ trợ Khiếu nại
                    </div>
                    
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #881337; margin-bottom: 6px;">Chọn loại sự cố <span style="color: red;">*</span></label>
                    <select name="issue_type" required style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 13px; margin-bottom: 12px; outline: none; background: white;" oninvalid="this.setCustomValidity('Vui lòng chọn loại sự cố.')" onchange="this.setCustomValidity('')">
                        <option value="">-- Chọn vấn đề bạn gặp phải --</option>
                        <option value="Hàng hóa bị hư hỏng/bể vỡ">Hàng hóa bị hư hỏng/bể vỡ</option>
                        <option value="Thất lạc/Mất hàng">Thất lạc/Mất hàng</option>
                        <option value="Tài xế thu sai cước phí">Tài xế thu sai cước phí</option>
                        <option value="Thái độ tài xế không chuẩn mực">Thái độ tài xế không chuẩn mực</option>
                        <option value="Tài xế bấm hoàn thành nhưng chưa giao">Tài xế bấm hoàn thành nhưng chưa giao</option>
                        <option value="Sự cố khác">Sự cố khác</option>
                    </select>

                    <label style="display: block; font-size: 12px; font-weight: 600; color: #881337; margin-bottom: 6px;">Chi tiết sự cố <span style="color: red;">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Vui lòng mô tả chi tiết sự việc để Quản trị viên hỗ trợ bạn nhanh nhất..." style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 13px; margin-bottom: 12px; outline: none;" oninvalid="this.setCustomValidity('Vui lòng nhập chi tiết báo cáo.')" oninput="this.setCustomValidity('')"></textarea>
                    
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #881337; margin-bottom: 6px;">Hình ảnh minh chứng (Tùy chọn)</label>
                    <input type="file" name="proof_image" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #fca5a5; border-radius: 4px; font-size: 13px; margin-bottom: 16px; background: white; cursor: pointer;">

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn-full-outline" style="background: var(--danger); color: white; border: none; margin-top: 0; flex: 1;">Gửi khiếu nại</button>
                        <button type="button" onclick="document.getElementById('dispute-form').style.display = 'none'; document.getElementById('btn-show-dispute').style.display = 'flex';" class="btn-full-outline" style="background: white; color: #475569; border: 1px solid #cbd5e1; margin-top: 0; width: auto; padding: 0 15px;">Hủy</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (in_array($order['status'], ['awaiting_payment', 'searching_driver', 'accepted', 'picking_up'])): ?>
                <div class="text-danger-link" onclick="document.getElementById('cancel-form').style.display = 'block'; this.style.display = 'none';">
                    Hủy đơn hàng
                </div>
                <form id="cancel-form" action="/user/orders/cancel/<?= app_e($order['tracking_code']) ?>" method="POST" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 16px;">
                    <select name="cancel_reason" required style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 13px; margin-bottom: 10px; outline: none; background: white;" oninvalid="this.setCustomValidity('Vui lòng chọn lý do hủy đơn.')" onchange="this.setCustomValidity('')">
                        <option value="">-- Chọn lý do hủy --</option>
                        <option value="Thay đổi ý định">Thay đổi ý định</option>
                        <option value="Chờ quá lâu">Chờ quá lâu</option>
                    </select>
                    <button type="submit" class="btn-full-outline" style="background: var(--danger); color: white; border: none; margin-top: 0;">Xác nhận hủy đơn</button>
                </form>
            <?php endif; ?>

        </div>

        <div>
            <div class="t-top-grid">
                <div class="t-card" style="margin-bottom: 0;">
                    <div class="t-card-title">CHI PHÍ & THANH TOÁN</div>
                    <div class="t-fee-amount"><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></div>
                    <div class="t-payment-row">
                        <div class="t-payment-method">
                            <span class="material-symbols-outlined">account_balance</span> 
                            <?= ($order['payment_method'] ?? '') === 'transfer' ? 'Chuyển khoản' : 'Tiền mặt' ?>
                        </div>
                        <?php if (($order['payment_status'] ?? '') === 'paid'): ?>
                            <span class="pill-status success">Đã thanh toán</span>
                        <?php elseif (($order['payment_status'] ?? '') === 'refunded'): ?>
                            <span class="pill-status warning">Đã hoàn tiền</span>
                        <?php else: ?>
                            <span class="pill-status pending">Chưa thanh toán</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="t-card" style="margin-bottom: 0;">
                    <div class="t-card-title">ĐỊA CHỈ</div>
                    <div class="t-address-block">
                        <div class="t-address-icon pickup"><span class="material-symbols-outlined">storefront</span></div>
                        <div class="t-address-title">Điểm lấy hàng</div>
                        <div class="t-address-desc"><?= app_e($order['sender_address'] ?? '') ?> <br> Người gửi: <?= app_e($order['sender_name'] ?? '') ?> (<?= app_e($order['sender_phone'] ?? '') ?>)</div>
                    </div>
                    <div class="t-address-block">
                        <div class="t-address-icon dropoff"><span class="material-symbols-outlined">home</span></div>
                        <div class="t-address-title">Điểm giao hàng</div>
                        <div class="t-address-desc"><?= app_e($order['receiver_address'] ?? '') ?> <br> Người nhận: <?= app_e($order['receiver_name'] ?? '') ?> (<?= app_e($order['receiver_phone'] ?? '') ?>)</div>
                    </div>
                </div>
            </div>

            <div class="t-card" style="padding: 0; overflow: hidden; height: 350px; position: relative;">
                <div id="route-map" style="width: 100%; height: 100%; z-index: 1;"></div>
            </div>

            <div class="t-card">
                <div class="t-card-title">LỊCH SỬ TIẾN TRÌNH</div>
                
                <?php if (empty($history)): ?>
                    <p style="color: var(--text-muted); font-size: 13px;">Chưa có cập nhật.</p>
                <?php else: ?>
                    <div class="t-history-list">
                        <?php foreach (array_reverse($history) as $idx => $h): ?>
                            <div class="t-history-item <?= $idx === 0 ? 'active' : '' ?>">
                                <div class="t-h-dot"></div>
                                <div class="t-h-content">
                                    <div class="t-h-title" style="color: <?= $idx === 0 ? 'var(--primary)' : 'var(--text-main)' ?>;"><?= app_e(app_status_label($h['status'])) ?></div>
                                    <div class="t-h-desc"><?= $h['description'] ?? 'Hệ thống đã ghi nhận trạng thái mới.' ?></div>
                                </div>
                                <div class="t-h-time">
                                    <strong><?= date('H:i', strtotime($h['created_at'])) ?></strong>
                                    <?= date('d/m/Y', strtotime($h['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php if (!empty($order['driver_id'])): ?>
<?= app_component('chat_widget', [
    'orderId' => $order['id'],
    'receiverId' => $order['driver_id'],
    'receiverRole' => 'Tài xế'
]) ?>
<script>
    let myMapHelper = null;
    // Lắng nghe Pusher để tự động load tin nhắn
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
                            title.style.color = '#fde047'; // Đổi tiêu đề sang màu vàng
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

        // Bổ sung Real-time WebSockets (Pusher) để cập nhật vị trí mượt mà, thay thế dần cho AJAX Polling
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
