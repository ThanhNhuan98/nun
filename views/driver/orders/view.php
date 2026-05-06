<?php
/**
 * @var array $order
 * @var string $pageTitle
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <?php if ($message = app_flash('flash_success')): ?>
        <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #bbf7d0;">
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($message = app_flash('flash_error')): ?>
        <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #fecaca;">
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>

    <div class="driver-detail-layout">
        
        <div class="driver-info-col">
            
            <div class="detail-card-v2">
                <div class="tracking-label">Mã Vận Đơn</div>
                <div class="tracking-header">
                    <div class="tracking-code-large">#<?= app_e($order['tracking_code']) ?></div>
                    <span class="badge-status status-warning"><?= app_e(app_status_label($order['status'])) ?></span>
                </div>
            </div>

            <div class="detail-card-v2">
                <div class="timeline-v2">
                    
                    <div class="timeline-node-v2">
                        <div class="node-icon-v2 pickup">
                            <span class="material-symbols-outlined">storefront</span>
                        </div>
                        <div class="node-title-v2">ĐIỂM LẤY HÀNG</div>
                        <div class="node-desc-v2">
                            <strong><?= app_e($order['sender_name']) ?></strong><br>
                            <?= app_e($order['sender_address']) ?>
                        </div>
                        <a href="tel:<?= app_e($order['sender_phone']) ?>" class="node-phone-v2">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span> <?= app_e($order['sender_phone']) ?>
                        </a>
                    </div>

                    <div class="timeline-node-v2">
                        <div class="node-icon-v2 dropoff"></div>
                        <div class="node-title-v2">ĐIỂM GIAO HÀNG</div>
                        <div class="node-desc-v2">
                            <strong><?= app_e($order['receiver_name']) ?></strong><br>
                            <?= app_e($order['receiver_address']) ?>
                        </div>
                        <a href="tel:<?= app_e($order['receiver_phone']) ?>" class="node-phone-v2">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span> <?= app_e($order['receiver_phone']) ?>
                        </a>
                        <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                            <div style="margin-top: 8px; color: var(--danger); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 4px 8px; border-radius: 4px; border: 1px solid #fca5a5;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">warning</span>
                                Khách từng bom hàng: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="detail-card-v2">
                <div class="trip-info-header">Thông tin chuyến đi</div>
                <div class="trip-fee-row" style="margin-bottom: 8px;">
                    <span>Dịch vụ:</span>
                    <strong style="color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? 'standard') ?>;">
                        <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                    </strong>
                </div>
                <div class="trip-fee-row">
                    <span>Phí vận chuyển:</span>
                    <strong><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></strong>
                </div>
                
                <?php if (!empty($order['note'])): ?>
                    <div class="trip-note-box">
                        <strong style="display: block; font-size: 12px; margin-bottom: 4px;">Ghi chú từ khách hàng:</strong>
                        "<?= app_e($order['note']) ?>"
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 24px;">
                
                <?php if ($order['status'] === 'accepted'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                        <input type="hidden" name="status" value="picking_up">
                        <button type="submit" class="btn-driver-action orange">
                            Đã Đến Điểm Lấy Hàng
                        </button>
                    </form>
                
                <?php elseif ($order['status'] === 'picking_up'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                        <input type="hidden" name="status" value="in_transit">
                        <button type="submit" class="btn-driver-action blue">
                            Bắt Đầu Giao Hàng
                        </button>
                    </form>
                
                <?php elseif ($order['status'] === 'in_transit' || $order['status'] === 'shipping'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data">
                        <input type="hidden" name="status" value="completed">
                        <div style="background: #f8fafc; padding: 12px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 8px;">Ảnh minh chứng giao hàng (Bắt buộc):</label>
                            <input type="file" name="proof_image" accept="image/*" capture="environment" class="minimal-input" style="margin-bottom: 0;" required>
                        </div>
                        <button type="submit" class="btn-driver-action green">
                            Xác Nhận Đã Giao Xong
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (in_array($order['status'], ['accepted', 'picking_up', 'in_transit', 'shipping'])): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data" style="margin-top: 15px;">
                        <input type="hidden" name="status" value="cancelled">
                        
                        <button type="button" class="btn-driver-action danger-outline" onclick="document.getElementById('cancel-form-box').style.display='block'; this.style.display='none';">
                            <span class="material-symbols-outlined" style="font-size: 18px;">warning</span> Báo Cáo Thất Bại / Hủy Đơn
                        </button>
                        
                        <div id="cancel-form-box" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fecaca; border-radius: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: var(--danger); display: block; margin-bottom: 8px;">Nhập lý do sự cố:</label>
                            <input type="text" name="cancel_reason" placeholder="VD: Khách không nghe máy..." class="minimal-input" style="border-color: #fca5a5;">
                            
                            <label style="font-size: 12px; font-weight: 700; color: var(--danger); display: block; margin-bottom: 8px;">Ảnh minh chứng (nếu có):</label>
                            <input type="file" name="proof_image" accept="image/*" capture="environment" class="minimal-input" style="border-color: #fca5a5; margin-bottom: 16px;">
                            
                            <button type="submit" class="btn-driver-action" style="background: var(--danger); color: white; margin: 0;">
                                Xác Nhận Hủy Đơn
                            </button>
                        </div>
                    </form>

                    <!-- Form báo cáo Khách Bom Hàng chuyên biệt -->
                    <form method="POST" action="/driver/orders/report-noshow/<?= $order['id'] ?>" enctype="multipart/form-data" style="margin-top: 10px;">
                        <button type="button" class="btn-driver-action" style="background: #be123c; color: white; border: none;" onclick="document.getElementById('noshow-form-box').style.display='block'; this.style.display='none';">
                            <span class="material-symbols-outlined" style="font-size: 18px;">person_off</span> Báo Cáo Khách Bom Hàng
                        </button>
                        
                        <div id="noshow-form-box" style="display: none; background: #fff1f2; padding: 16px; border: 1px solid #fda4af; border-radius: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: #be123c; display: block; margin-bottom: 8px;">Ảnh chụp cửa nhà / nơi giao hàng (Bắt buộc):</label>
                            <input type="file" name="proof_image" accept="image/*" capture="environment" class="minimal-input" style="border-color: #fda4af; margin-bottom: 16px;" required>
                            
                            <input type="hidden" name="reason" value="Khách hàng không xuất hiện / Từ chối nhận hàng (Bom hàng)">
                            <button type="submit" class="btn-driver-action" style="background: #be123c; color: white; margin: 0;">
                                Gửi Báo Cáo Bom Hàng
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
        </div>

        <div class="driver-map-container">
            <div id="route-info" style="display: none; position: absolute; top: 10px; left: 10px; right: 10px; z-index: 1000; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); padding: 10px; border-radius: 4px; border: 1px solid var(--border-color); font-size: 13px; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: bottom; color: var(--primary);">route</span> 
                Khoảng cách: <strong id="route-distance" style="color: var(--primary);">0</strong> km &bull; Thời gian: <strong id="route-time" style="color: var(--primary);">0</strong> phút
            </div>
            <div id="route-map"></div>
        </div>

    </div>
</div>

<?php if (!empty($order['customer_id'])): ?>
<div id="chat-widget" style="position: fixed; bottom: 20px; right: 20px; width: 320px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color);">
    <div style="background: var(--primary); color: #fff; padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleChat()">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined">chat</span>
            <strong style="font-size: 14px;">Chat với Khách Hàng</strong>
        </div>
        <span class="material-symbols-outlined" id="chat-toggle-icon">expand_less</span>
    </div>
    
    <div id="chat-body" style="display: none; flex-direction: column; height: 350px;">
        <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
            </div>
        <div style="padding: 10px; border-top: 1px solid var(--border-color); background: #fff; display: flex; gap: 8px;">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; outline: none; font-size: 13px;" onkeypress="if(event.key === 'Enter') sendChatMessage()">
            <button onclick="sendChatMessage()" style="background: var(--primary); color: #fff; border: none; width: 38px; height: 38px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">send</span></button>
        </div>
    </div>
</div>

<script>
    const chatOrderId = <?= $order['id'] ?>;
    const chatReceiverId = <?= $order['customer_id'] ?>;
    let chatInterval = null;

    function toggleChat() {
        const body = document.getElementById('chat-body');
        const icon = document.getElementById('chat-toggle-icon');
        if (body.style.display === 'none') {
            body.style.display = 'flex';
            icon.textContent = 'expand_more';
            loadChatMessages();
            chatInterval = setInterval(loadChatMessages, 3000);
        } else {
            body.style.display = 'none';
            icon.textContent = 'expand_less';
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
                box.innerHTML = html || '<div style="text-align: center; color: var(--text-muted); font-size: 13px; margin-top: auto; margin-bottom: auto;">Chưa có tin nhắn nào.</div>';
                
                if (isAtBottom) box.scrollTop = box.scrollHeight;
            }
        } catch(e) { console.error("Lỗi tải tin nhắn:", e); }
    }

    async function sendChatMessage() {
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';
        await fetch(`/api/chat/${chatOrderId}`, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({receiver_id: chatReceiverId, message: msg})
        });
        loadChatMessages();
    }
</script>
<?php endif; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderStatus = '<?= $order['status'] ?>';
        const driverLat = <?= (float)($order['driver_lat'] ?? 0) ?>;
        const driverLng = <?= (float)($order['driver_lng'] ?? 0) ?>;
        const senderLat = <?= (float)($order['sender_lat'] ?? 0) ?>;
        const senderLng = <?= (float)($order['sender_lng'] ?? 0) ?>;
        const receiverLat = <?= (float)($order['receiver_lat'] ?? 0) ?>;
        const receiverLng = <?= (float)($order['receiver_lng'] ?? 0) ?>;

        if (senderLat !== 0 && senderLng !== 0 && receiverLat !== 0 && receiverLng !== 0) {
            const map = L.map('route-map').setView([senderLat, senderLng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            let routingControl = null;
            let currentHasLoc = false;

            function drawRoute(currentDriverLat, currentDriverLng) {
                const waypoints = [];
                currentHasLoc = (currentDriverLat !== 0 && currentDriverLng !== 0);
                
                if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    } else {
                        waypoints.push(L.latLng(senderLat, senderLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    }
                } else if (orderStatus === 'in_transit' || orderStatus === 'shipping') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    } else {
                        waypoints.push(L.latLng(senderLat, senderLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    }
                } else {
                    waypoints.push(L.latLng(senderLat, senderLng));
                    waypoints.push(L.latLng(receiverLat, receiverLng));
                }

                const customIcon = L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
                });

                if (routingControl) {
                    routingControl.setWaypoints(waypoints);
                    return;
                }

                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                    routeWhileDragging: false,
                    addWaypoints: false,
                    fitSelectedRoutes: true,
                    show: false,
                    lineOptions: {
                        styles: [{color: '#2563eb', opacity: 0.8, weight: 6}]
                    },
                    createMarker: function(i, wp, nWps) {
                        return L.marker(wp.latLng, {icon: customIcon});
                    }
                }).addTo(map);
                
                routingControl.on('routesfound', function(e) {
                    const summary = e.routes[0].summary;
                    document.getElementById('route-distance').textContent = (summary.totalDistance / 1000).toFixed(1);
                    document.getElementById('route-time').textContent = Math.round(summary.totalTime / 60);
                    document.getElementById('route-info').style.display = 'block';
                });
            }

            drawRoute(driverLat, driverLng);

            if (navigator.geolocation && ['accepted', 'picking_up', 'in_transit', 'shipping'].includes(orderStatus)) {
                let lastPushTime = 0; // Lưu vết thời gian
                const pushLocationToServer = (lat, lng) => {
                    const now = Date.now();
                    // Tối ưu: Nếu chưa qua 10 giây kể từ lần gửi cuối, không gửi lên Server
                    if (now - lastPushTime < 10000) return; 
                    lastPushTime = now;
                    
                    fetch('/api/driver/update-location', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ lat: lat, lng: lng })
                    });
                };

                navigator.geolocation.getCurrentPosition(function(position) {
                    drawRoute(position.coords.latitude, position.coords.longitude);
                    pushLocationToServer(position.coords.latitude, position.coords.longitude);
                }, null, { enableHighAccuracy: true });

                navigator.geolocation.watchPosition(function(position) {
                    drawRoute(position.coords.latitude, position.coords.longitude);
                    pushLocationToServer(position.coords.latitude, position.coords.longitude);
                }, null, { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 });
            }
        } else {
            document.getElementById('route-map').innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--text-muted);">Không có dữ liệu tọa độ bản đồ.</div>';
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>