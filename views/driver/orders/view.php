<?php
/**
 * @var array $order
 * @var string $pageTitle
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>
<!-- Gắn link CSS driver-order-theme.css vào đây -->

<div class="driver-container">
    
    <!-- Header Điều Hướng -->
    <a href="/driver/receive-orders" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-main); font-weight: 700; font-size: 16px; text-decoration: none; margin-bottom: 24px;">
        <span class="material-symbols-outlined">arrow_back</span> NUN EXPRESS
    </a>

    <!-- GRID CHÍNH -->
    <div class="driver-grid">
        
        <!-- ==================== CỘT TRÁI ==================== -->
        <div class="left-col">
            
            <!-- Khối Header Mã Đơn -->
            <div class="d-header-box">
                <div class="d-header-title">Đơn Hàng #<?= app_e($order['tracking_code']) ?></div>
                <div class="d-header-time">Cập nhật lúc: <?= date('H:i - d/m', strtotime($order['updated_at'] ?? $order['created_at'] ?? 'now')) ?></div>
                <div class="d-status-badge">
                    <?= app_e(app_status_label($order['status'])) ?>
                </div>
            </div>

            <!-- Khối Bản Đồ -->
            <div class="d-map-wrapper">
                <div id="route-map"></div>
            </div>

            <!-- Khối Timeline Điểm Lấy / Giao -->
            <div class="d-card">
                <div class="d-card-body">
                    <div class="d-timeline">
                        
                        <!-- Node Lấy Hàng -->
                        <div class="d-tl-node">
                            <div class="d-tl-icon pickup"><span class="material-symbols-outlined" style="font-size: 16px;">storefront</span></div>
                            <div class="d-tl-header">
                                <span class="d-tl-label">Điểm Lấy Hàng</span>
                                <span class="d-tl-badge">Đã lấy</span>
                            </div>
                            <div class="d-tl-title"><?= app_e($order['sender_name']) ?></div>
                            <div class="d-tl-address"><?= app_e($order['sender_address']) ?></div>
                            <div class="d-tl-contact">
                                <div class="d-tl-contact-info">
                                    <span class="material-symbols-outlined" style="color: var(--text-muted);">person</span>
                                    <?= app_e($order['sender_phone']) ?>
                                </div>
                                <a href="tel:<?= app_e($order['sender_phone']) ?>" class="d-btn-phone">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">call</span>
                                </a>
                            </div>
                        </div>

                        <!-- Node Giao Hàng -->
                        <div class="d-tl-node">
                            <div class="d-tl-icon dropoff"><span class="material-symbols-outlined" style="font-size: 16px;">location_on</span></div>
                            <div class="d-tl-header">
                                <span class="d-tl-label">Điểm Giao Hàng</span>
                                <span class="d-tl-badge active">Sắp đến</span>
                            </div>
                            <div class="d-tl-title"><?= app_e($order['receiver_name']) ?></div>
                            <div class="d-tl-address"><?= app_e($order['receiver_address']) ?></div>
                            <div class="d-tl-contact">
                                <div class="d-tl-contact-info">
                                    <span class="material-symbols-outlined" style="color: var(--text-muted);">person</span>
                                    <?= app_e($order['receiver_phone']) ?>
                                </div>
                                <a href="tel:<?= app_e($order['receiver_phone']) ?>" class="d-btn-phone">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">call</span>
                                </a>
                            </div>
                            
                            <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                                <div style="margin-top: 12px; color: var(--status-red); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; padding: 6px 10px; border-radius: 4px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">warning</span>
                                    Khách Vi phạm giao nhận: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- ==================== CỘT PHẢI ==================== -->
        <div class="right-col">
            
            <!-- Khối Thông Tin Chuyến Đi -->
            <div class="d-card">
                <div class="d-card-body">
                    <h2 class="d-card-title">Thông tin chuyến đi</h2>
                    <div class="d-info-grid">
                        <div class="d-info-item">
                            <div class="label">Dịch Vụ</div>
                            <div class="value" style="color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? 'standard') ?>;">
                                <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                            </div>
                        </div>
                        <div class="d-info-item">
                            <div class="label">Dự Kiến Giao</div>
                            <div class="value"><?= !empty($order['scheduled_at']) ? date('H:i - d/m', strtotime($order['scheduled_at'])) : 'Hôm nay' ?></div>
                        </div>
                        <div class="d-info-item">
                            <div class="label">Khối Lượng</div>
                            <div class="value"><?= app_e($order['weight'] ?? '0') ?> kg</div>
                        </div>
                        <div class="d-info-item">
                            <div class="label">Phí Thu Hộ (COD/Cước)</div>
                            <div class="value highlight"><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></div>
                        </div>
                    </div>
                    <?php if (!empty($order['note'])): ?>
                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed var(--border-color); font-size: 13px;">
                            <strong style="color: var(--text-muted); display: block; margin-bottom: 4px;">Ghi chú từ khách hàng:</strong>
                            "<?= app_e($order['note']) ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Khối Hành Động -->
            <div class="d-card">
                <div class="d-card-body d-action-box">
                    <h2 class="d-card-title" style="text-align: left;">Hành động</h2>

                    <?php if ($order['status'] === 'accepted'): ?>
                        <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                            <input type="hidden" name="status" value="picking_up">
                            <button type="submit" class="d-btn-submit">Đã Đến Điểm Lấy Hàng</button>
                        </form>

                    <?php elseif ($order['status'] === 'picking_up'): ?>
                        <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                            <input type="hidden" name="status" value="in_transit">
                            <button type="submit" class="d-btn-submit">Bắt Đầu Giao Hàng</button>
                        </form>

                    <?php elseif ($order['status'] === 'in_transit' || $order['status'] === 'shipping'): ?>
                        <!-- Form Giao Hàng Thành Công -->
                        <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data">
                            <input type="hidden" name="status" value="completed">
                            
                            <!-- Box Upload Ảnh (Styling giống mockup) -->
                            <div class="d-upload-area" id="proof-container">
                                <label class="d-upload-label">
                                    <span class="material-symbols-outlined" style="font-size: 32px; color: var(--text-muted);">photo_camera</span>
                                    Chụp ảnh xác nhận giao hàng
                                    <input type="file" name="proof_image" class="real-proof-input" accept="image/*" capture="environment" required style="display: none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <!-- Preview Ảnh -->
                            <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 20px;">
                                <img src="" alt="Preview" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color);">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: var(--status-red); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; margin-top: 2px;">close</span>
                                </button>
                            </div>

                            <div style="text-align: left; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: var(--text-main);">Mã PIN xác nhận (Tùy chọn)</div>
                            <input type="text" name="delivery_pin" placeholder="Nhập mã PIN của người nhận" pattern="\d{4}" maxlength="4" class="d-pin-input">

                            <button type="submit" class="d-btn-submit">
                                Xác Nhận Đã Giao Xong <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                            </button>
                        </form>

                    <?php elseif ($order['status'] === 'returning'): ?>
                        <!-- Form Hoàn Hàng -->
                        <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data">
                            <input type="hidden" name="status" value="returned">
                            
                            <div class="d-upload-area" id="proof-container">
                                <label class="d-upload-label">
                                    <span class="material-symbols-outlined" style="font-size: 32px; color: var(--text-muted);">photo_camera</span>
                                    Chụp ảnh xác nhận hoàn hàng
                                    <input type="file" name="proof_image" class="real-proof-input" accept="image/*" capture="environment" required style="display: none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 20px;">
                                <img src="" alt="Preview" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color);">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: var(--status-red); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer;"><span class="material-symbols-outlined" style="font-size: 16px; margin-top: 2px;">close</span></button>
                            </div>

                            <button type="submit" class="d-btn-submit">Xác Nhận Đã Hoàn Hàng</button>
                        </form>

                        <button type="button" class="d-btn-outline" style="color: var(--status-red); border-color: var(--status-red);" onclick="document.getElementById('dispute-return-box').style.display='block'; this.style.display='none';">
                            Khách Từ Chối Nhận / Sự Cố
                        </button>

                        <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data" id="dispute-return-box" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fecaca; border-radius: 8px; margin-top: 16px; text-align: left;">
                            <input type="hidden" name="status" value="disputed">
                            <label style="font-size: 12px; font-weight: 700; color: var(--status-red); display: block; margin-bottom: 8px;">Nhập lý do sự cố:</label>
                            <input type="text" name="cancel_reason" placeholder="VD: Khách không nghe máy..." class="d-pin-input" style="border-color: #fca5a5;" required data-error="Vui lòng nhập lý do sự cố.">
                            
                            <label style="font-size: 12px; font-weight: 700; color: var(--status-red); display: block; margin-bottom: 8px;">Ảnh minh chứng (Bắt buộc):</label>
                            <input type="file" name="proof_image" accept="image/*" required style="width: 100%; margin-bottom: 16px; font-size: 14px;" data-error="Vui lòng chọn ảnh minh chứng.">
                            
                            <button type="submit" class="d-btn-submit danger">Báo Cáo Tranh Chấp</button>
                        </form>
                    <?php endif; ?>

                    <!-- Report Lấy/Giao Thất Bại (Chung cho các trạng thái Đang đi) -->
                    <?php if (in_array($order['status'], ['accepted', 'picking_up', 'in_transit', 'shipping'])): ?>
                        <?php
                            $isPickingUp = in_array($order['status'], ['accepted', 'picking_up']);
                            $btnLabel = $isPickingUp ? 'Báo Cáo Lấy Thất Bại' : 'Báo Cáo Giao Thất Bại';
                            $submitLabel = $isPickingUp ? 'Xác Nhận Hủy Đơn' : 'Xác Nhận Chuyển Hoàn';
                        ?>
                        <button type="button" class="d-btn-outline" style="color: var(--text-muted);" onclick="document.getElementById('noshow-form-box').style.display='block'; this.style.display='none';">
                            <?= $btnLabel ?>
                        </button>

                        <form method="POST" action="/driver/orders/report-noshow/<?= $order['id'] ?>" enctype="multipart/form-data" id="noshow-form-box" style="display: none; background: #fff1f2; padding: 16px; border: 1px solid #fda4af; border-radius: 8px; margin-top: 16px; text-align: left;">
                            <label style="font-size: 12px; font-weight: 700; color: #be123c; display: block; margin-bottom: 8px;">Lý do sự cố (Bắt buộc):</label>
                            <select name="reason" class="d-pin-input" style="border-color: #fda4af; margin-bottom: 12px;" required data-error="Vui lòng chọn lý do sự cố.">
                                <option value="">-- Chọn lý do --</option>
                                <?php if ($isPickingUp): ?>
                                    <option value="Người gửi không nghe máy">Người gửi không nghe máy / Không liên lạc được</option>
                                    <option value="Hàng hóa quá khổ / Sai mô tả">Hàng hóa quá khổ / Sai mô tả</option>
                                    <option value="Phát hiện Hàng cấm">🚨 Phát hiện Hàng cấm / Vi phạm pháp luật</option>
                                <?php else: ?>
                                    <option value="Người nhận không nghe máy">Người nhận không nghe máy / Không liên lạc được</option>
                                    <option value="Người nhận từ chối nhận hàng">Người nhận từ chối nhận hàng</option>
                                    <option value="Sai địa chỉ giao">Sai địa chỉ giao / Không tìm thấy địa chỉ</option>
                                <?php endif; ?>
                                <option value="Lý do khác">Lý do khác</option>
                            </select>

                            <label style="font-size: 12px; font-weight: 700; color: #be123c; display: block; margin-bottom: 8px;">Ảnh chụp minh chứng (Bắt buộc):</label>
                            <input type="file" name="proof_image" accept="image/*" required style="width: 100%; margin-bottom: 16px; font-size: 14px;" data-error="Vui lòng chọn ảnh minh chứng.">

                            <button type="submit" class="d-btn-submit danger"><?= $submitLabel ?></button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>

        </div>

    </div>
</div>

<!-- ==================== SCRIPT GIỮ NGUYÊN ==================== -->
<script>
    function handleProofSync(input) {
        if (input.files && input.files[0]) {
            const form = input.closest('form');
            const previewContainer = form.querySelector('.active-image-preview');
            const img = previewContainer.querySelector('img');
            const uploadArea = form.querySelector('.d-upload-area');

            img.src = URL.createObjectURL(input.files[0]);
            previewContainer.style.display = 'block';
            if(uploadArea) uploadArea.style.display = 'none';
        }
    }

    function clearProofPreview(btn) {
        const form = btn.closest('form');
        const realInput = form.querySelector('.real-proof-input');
        const previewContainer = form.querySelector('.active-image-preview');
        const uploadArea = form.querySelector('.d-upload-area');

        if(realInput) realInput.value = '';
        if(previewContainer) previewContainer.style.display = 'none';
        if(uploadArea) uploadArea.style.display = 'block';
    }
</script>

<?php if (!empty($order['customer_id'])): ?>
<?= app_component('chat_widget', [
    'orderId' => $order['id'],
    'receiverId' => $order['customer_id'],
    'receiverRole' => 'Khách Hàng'
]) ?>
<script>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderStatus = '<?= $order['status'] ?>';
        const driverLat = <?= (float)($order['driver_lat'] ?? 0) ?>;
        const driverLng = <?= (float)($order['driver_lng'] ?? 0) ?>;
        const senderLat = <?= (float)($order['sender_lat'] ?? 0) ?>;
        const senderLng = <?= (float)($order['sender_lng'] ?? 0) ?>;
        const receiverLat = <?= (float)($order['receiver_lat'] ?? 0) ?>;
        const receiverLng = <?= (float)($order['receiver_lng'] ?? 0) ?>;
        const senderName = <?= json_encode($order['sender_name'] ?? 'Người gửi', JSON_UNESCAPED_UNICODE) ?>;
        const receiverName = <?= json_encode($order['receiver_name'] ?? 'Khách hàng', JSON_UNESCAPED_UNICODE) ?>;

        let previousDriverLat = driverLat;
        let previousDriverLng = driverLng;
        let driverHeadingAngle = 0;
        
        function calculateBearing(lat1, lng1, lat2, lng2) {
            const toRad = Math.PI / 180;
            const toDeg = 180 / Math.PI;
            const dLng = (lng2 - lng1) * toRad;
            const y = Math.sin(dLng) * Math.cos(lat2 * toRad);
            const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                      Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLng);
            const brng = Math.atan2(y, x) * toDeg;
            return (brng + 360) % 360;
        }

        if (senderLat !== 0 && senderLng !== 0 && receiverLat !== 0 && receiverLng !== 0) {
            const map = L.map('route-map').setView([senderLat, senderLng], 14);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '&copy; Google Maps'
            }).addTo(map);

            let routingControl = null;
            let currentHasLoc = false;
            let driverMarker = null;

            function resolveDestinationInfo() {
                if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                    return { lat: senderLat, lng: senderLng, label: `Điểm lấy hàng: ${senderName}` };
                }
                if (orderStatus === 'returning') {
                    return { lat: senderLat, lng: senderLng, label: `Hoàn hàng: ${senderName}` };
                }
                return { lat: receiverLat, lng: receiverLng, label: `Điểm giao hàng: ${receiverName}` };
            }

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
                } else if (orderStatus === 'returning') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    } else {
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    }
                } else {
                    waypoints.push(L.latLng(senderLat, senderLng));
                    waypoints.push(L.latLng(receiverLat, receiverLng));
                }

                const createCustomMarkerIcon = (icon, color, angle = 0) => L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color:${color};width:36px;height:36px;border-radius:50%;border:3px solid #fff;box-shadow:0 4px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined marker-rotate-icon" style="color:#fff;font-size:20px;transition: transform 0.4s ease; transform: rotate(${angle}deg);">${icon}</span><div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-width:8px 6px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border-width:6px 4px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
                    iconSize: [36, 44], iconAnchor: [18, 44], popupAnchor: [0, -44]
                });

                if (routingControl) {
                    //  Không gọi setWaypoints để tránh re-render OSRM Router liên tục mỗi giây khi GPS cập nhật.
                    // Vị trí Marker tài xế sẽ được tự động dịch chuyển qua setLatLng ở hàm watchPosition phía dưới.
                    return;
                }

                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
                    routeWhileDragging: false,
                    addWaypoints: false,
                    fitSelectedRoutes: false,
                    show: false,
                    lineOptions: {
                        styles: [{color: '#2563eb', opacity: 0.8, weight: 6}]
                    },
                    createMarker: function(i, wp, nWps) {
                        const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb', driverHeadingAngle);
                        const senderIcon = createCustomMarkerIcon('storefront', '#f59e0b');
                        const receiverIcon = createCustomMarkerIcon('location_on', '#10b981');

                        let iconToUse;
                        if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : senderIcon; }
                            else { iconToUse = (i === 0) ? senderIcon : receiverIcon; }
                        } else if (orderStatus === 'in_transit' || orderStatus === 'shipping') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : receiverIcon; }
                            else { iconToUse = (i === 0) ? senderIcon : receiverIcon; }
                        } else if (orderStatus === 'returning') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : senderIcon; }
                            else { iconToUse = (i === 0) ? receiverIcon : senderIcon; }
                        } else {
                            iconToUse = (i === 0) ? senderIcon : receiverIcon;
                        }

                        let title = '';
                        if (iconToUse === driverIcon) title = 'Vị trí của bạn';
                        if (iconToUse === senderIcon) title = orderStatus === 'returning' ? 'Điểm hoàn hàng' : 'Điểm lấy hàng';
                        if (iconToUse === receiverIcon) title = 'Điểm giao hàng';

                        const marker = L.marker(wp.latLng, {icon: iconToUse}).bindPopup(`<b>${title}</b>`);
                        
                        if (iconToUse === driverIcon) driverMarker = marker;
                        return marker;
                    }
                }).addTo(map);

                routingControl.on('routesfound', function(e) {
                    if (!map._hasFittedRoute) {
                        const bounds = L.latLngBounds(e.routes[0].coordinates);
                        map.fitBounds(bounds, { padding: [40, 40] });
                        map._hasFittedRoute = true;
                    }
                });
            }

            drawRoute(driverLat, driverLng);

            let lastPushTime = 0;
            const pushLocationToServer = (lat, lng, accuracy = null) => {
                const now = Date.now();
                if (now - lastPushTime < 10000) return;
                lastPushTime = now;

                fetch('/api/driver/update-location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lat: lat, lng: lng, accuracy: accuracy })
                }).catch(() => {});
            };

            if (navigator.geolocation && ['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus)) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const newLat = position.coords.latitude;
                    const newLng = position.coords.longitude;
                    if (previousDriverLat !== newLat || previousDriverLng !== newLng) {
                        driverHeadingAngle = calculateBearing(previousDriverLat, previousDriverLng, newLat, newLng);
                        previousDriverLat = newLat;
                        previousDriverLng = newLng;
                        
                        if (driverMarker) {
                            driverMarker.setLatLng([newLat, newLng]);
                            if (driverMarker._icon) {
                                const iconSpan = driverMarker._icon.querySelector('.marker-rotate-icon');
                                if (iconSpan) iconSpan.style.transform = `rotate(${driverHeadingAngle}deg)`;
                            }
                        } else {
                            drawRoute(newLat, newLng);
                        }
                    }
                    pushLocationToServer(newLat, newLng, position.coords.accuracy);
                }, null, { enableHighAccuracy: true });

                navigator.geolocation.watchPosition(function(position) {
                    const newLat = position.coords.latitude;
                    const newLng = position.coords.longitude;
                    if (previousDriverLat !== newLat || previousDriverLng !== newLng) {
                        driverHeadingAngle = calculateBearing(previousDriverLat, previousDriverLng, newLat, newLng);
                        previousDriverLat = newLat;
                        previousDriverLng = newLng;
                        
                        if (driverMarker) {
                            driverMarker.setLatLng([newLat, newLng]);
                            if (driverMarker._icon) {
                                const iconSpan = driverMarker._icon.querySelector('.marker-rotate-icon');
                                if (iconSpan) iconSpan.style.transform = `rotate(${driverHeadingAngle}deg)`;
                            }
                        } else {
                            drawRoute(newLat, newLng);
                        }
                    }
                    pushLocationToServer(newLat, newLng, position.coords.accuracy);
                }, null, { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 });
            }
        } else {
            document.getElementById('route-map').innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--text-muted);">Không có dữ liệu tọa độ bản đồ.</div>';
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>