<?php
/**
 * @var string $pageTitle
 * @var array $order
 * @var array|bool|null $existingReview
 * @var array $history
 * @var array $ratingInfo
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<style>
    .proof-image-wrapper { margin-top: 10px; }
    .proof-image { max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid var(--border-color); object-fit: cover; }
</style>

<div class="admin-container">
    
    <div class="track-header-v2">
        <a href="/user/orders" class="btn-back-square"><span class="material-symbols-outlined">arrow_back</span></a>
        <div>
            <h2 class="track-title-v2">Chi tiết đơn hàng</h2>
            <div class="track-subtitle-v2">Cập nhật lần cuối: <?= app_datetime($order['updated_at'] ?? $order['scheduled_at'], 'H:i d/m/Y') ?></div>
        </div>
    </div>

    <?php if ($message = app_flash('flash_success')): ?>
        <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 24px; border: 1px solid #bbf7d0;">
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($message = app_flash('flash_error')): ?>
        <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 24px; border: 1px solid #fecaca;">
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>

    <div class="track-layout-grid">
        
        <div>
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
            </div>

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

            <?php if (in_array($order['status'], ['completed', 'returning', 'returned', 'cancelled'])): ?>
                <div class="text-danger-link" onclick="document.getElementById('dispute-form').style.display = 'block'; this.style.display = 'none';">
                    Báo cáo sự cố
                </div>
                <form id="dispute-form" action="/user/orders/dispute/<?= app_e($order['tracking_code']) ?>" method="POST" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 16px;">
                    <textarea name="reason" rows="2" required placeholder="Mô tả sự cố bạn gặp phải..." style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 13px; margin-bottom: 10px; outline: none;"></textarea>
                    <button type="submit" class="btn-full-outline" style="background: var(--danger); color: white; border: none; margin-top: 0;">Gửi báo cáo</button>
                </form>
            <?php endif; ?>

            <?php if (in_array($order['status'], ['awaiting_payment', 'searching_driver', 'accepted', 'picking_up'])): ?>
                <div class="text-danger-link" onclick="document.getElementById('cancel-form').style.display = 'block'; this.style.display = 'none';">
                    Hủy đơn hàng
                </div>
                <form id="cancel-form" action="/user/orders/cancel/<?= app_e($order['tracking_code']) ?>" method="POST" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 16px;">
                    <select name="cancel_reason" required style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; font-family: inherit; font-size: 13px; margin-bottom: 10px; outline: none; background: white;">
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
<div id="chat-widget" style="position: fixed; bottom: 20px; right: 20px; width: 320px; background: #fff; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); display: none;">
    <div style="background: var(--primary); color: #fff; padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleChat()">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined">chat</span>
            <strong style="font-size: 14px;">Chat với Tài xế</strong>
        </div>
        <span class="material-symbols-outlined" id="chat-toggle-icon">expand_more</span>
    </div>
    
    <div id="chat-body" style="display: flex; flex-direction: column; height: 350px;">
        <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
            </div>
        <div style="padding: 10px; border-top: 1px solid var(--border-color); background: #fff; display: flex; gap: 8px;">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; outline: none; font-size: 13px;" onkeypress="if(event.key === 'Enter') sendChatMessage()">
            <button onclick="sendChatMessage()" style="background: var(--primary); color: #fff; border: none; width: 38px; height: 38px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-outlined" style="font-size: 16px;">send</span></button>
        </div>
    </div>
</div>

<script>
    const chatOrderId = <?= $order['id'] ?>;
    const chatReceiverId = <?= $order['driver_id'] ?>;
    let chatInterval = null;

    function toggleChat() {
        const widget = document.getElementById('chat-widget');
        const body = document.getElementById('chat-body');
        
        if (widget.style.display === 'none' || widget.style.display === '') {
            widget.style.display = 'flex';
            loadChatMessages();
            chatInterval = setInterval(loadChatMessages, 3000);
        } else {
            widget.style.display = 'none';
            clearInterval(chatInterval);
        }
    }

    async function loadChatMessages() {
        try {
            const res = await fetch(`/api/chat/${chatOrderId}`);
            const data = await res.json();
            if (data.success) {
                const box = document.getElementById('chat-messages');
                const isAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 10;
                let html = '';
                data.messages.forEach(m => {
                    const isMe = Number(m.sender_id) === Number(data.current_user_id);
                    html += `<div style="max-width: 85%; padding: 8px 12px; border-radius: 4px; font-size: 13px; align-self: ${isMe ? 'flex-end' : 'flex-start'}; background: ${isMe ? 'var(--primary)' : '#e2e8f0'}; color: ${isMe ? '#fff' : 'var(--text-main)'}; border-bottom-${isMe ? 'right' : 'left'}-radius: 0;">${m.message}</div>`;
                });
                box.innerHTML = html || '<div style="text-align: center; color: var(--text-muted); font-size: 13px; margin: auto;">Chưa có tin nhắn nào.</div>';
                if (isAtBottom) box.scrollTop = box.scrollHeight;
            }
        } catch(e) { console.error(e); }
    }

    async function sendChatMessage() {
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';
        await fetch(`/api/chat/${chatOrderId}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({receiver_id: chatReceiverId, message: msg}) });
        loadChatMessages();
    }
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
        
        const myMapHelper = new MapHelper('route-map');
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
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>