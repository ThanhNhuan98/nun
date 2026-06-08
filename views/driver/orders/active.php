<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<link rel="stylesheet" href="/assets/css/style.css">

<div class="admin-container">

    <div class="active-page-header">
        <h2 class="active-page-title">Lộ trình công việc</h2>
        <p class="active-page-subtitle"><?= $totalActive ?? 0 ?> đơn đang chạy</p>
    </div>

    <?php if (empty($groupedOrders)): ?>
        <div class="empty-state-box">
            <span class="material-symbols-outlined">work_history</span>
            <h3>Không có đơn hàng nào đang chạy</h3>
            <p>Tất cả các đơn hàng của bạn đã hoàn thành. Hãy vào Radar để nhận chuyến mới!</p>
            <a href="/driver/receive-orders" class="btn-process-solid w-auto">
                <span class="material-symbols-outlined">radar</span> Tới Radar nhận đơn
            </a>
        </div>
    <?php else: ?>

        <div class="active-layout-v3">
            
            <div class="map-col-v3">
                <div class="map-wrapper-v3">
                    <div class="map-target-card" style="border: 2px solid #3b82f6; background: #eff6ff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);">
                        <div class="mt-label" style="color: #2563eb; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 18px; animation: pulse 2s infinite;">my_location</span> ĐIỂM ĐẾN TIẾP THEO
                        </div>
                        <div class="mt-address" id="nav-next-stop" style="font-size: 15px; font-weight: 700; color: #1e293b;">Đang xác định vị trí...</div>
                        <a id="btn-google-batch-nav" class="btn-mt-nav disabled" href="#" target="_blank" rel="noopener" style="background: #2563eb; color: #fff; border: none; font-weight: 600; padding: 10px 16px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">near_me</span> Dẫn đường Google Maps
                        </a>
                    </div>
                    <style>
                        @keyframes pulse {
                            0% { transform: scale(1); opacity: 1; }
                            50% { transform: scale(1.2); opacity: 0.7; }
                            100% { transform: scale(1); opacity: 1; }
                        }
                        
                        /* Thêm animation chuyển động cho chặng lộ trình tiếp theo */
                        .route-active-segment {
                            animation: dash 15s linear infinite;
                        }
                        @keyframes dash {
                            to { stroke-dashoffset: -1000; }
                        }
                    </style>
                    
                    <button onclick="recenterMap()" id="btn-recenter" class="btn-recenter-map" title="Định vị lại">
                        <span class="material-symbols-outlined">my_location</span>
                    </button>

                    <div id="inline-route-map"></div>
                </div>
            </div>
            
            <script>
                let batchPointsForMap = [];
                let fallbackDriverLat = 0;
                let fallbackDriverLng = 0;
            </script>

            <div class="orders-col-v3">
                <?php foreach ($groupedOrders as $batchCode => $batchData): ?>
                    <?php
                        $group = $batchData['orders'];
                        $routeDetails = $batchData['route_details'];
                        $isBatch = count($group) > 1;
                        $totalFee = array_sum(array_column($group, 'shipping_fee'));
                        $totalWeight = array_sum(array_column($group, 'weight'));
                        
                        // Lấy data cho map
                        $pointsData = app_build_driver_route_points($group, $routeDetails);
                    ?>

                    <script>
                        if (batchPointsForMap.length === 0) batchPointsForMap = <?= json_encode($pointsData) ?>;
                        if (fallbackDriverLat === 0) fallbackDriverLat = <?= (float)($group[0]['driver_lat'] ?? 0) ?>;
                        if (fallbackDriverLng === 0) fallbackDriverLng = <?= (float)($group[0]['driver_lng'] ?? 0) ?>;
                    </script>

                    <?php if ($isBatch): ?>
                        <div class="batch-banner-v3">
                            <div class="bs-left">
                                <div class="bs-title">GỘP CHUYẾN</div>
                                <div class="bs-meta"><?= count($group) ?> đơn hàng • Tổng: <?= number_format($totalWeight, 1) ?>kg</div>
                            </div>
                            <div class="bs-right"><?= app_money($totalFee, 'đ') ?></div>
                        </div>

                        <?php if (!empty($pointsData)): ?>
                            <div class="route-toggle-bar route-toggle-bar-v2" onclick="toggleRouteTimeline('timeline-<?= app_e($batchCode) ?>', this)">
                                <span class="rt-toggle-text">Xem chi tiết lộ trình AI (<?= count($pointsData) ?> điểm dừng)</span>
                                <span class="material-symbols-outlined rt-toggle-icon">expand_more</span>
                            </div>

                            <div id="timeline-<?= app_e($batchCode) ?>" class="batch-route-timeline-v2 collapsible-timeline">
                                <div class="rt-timeline-title">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">route</span> Thứ tự điểm dừng AI đề xuất
                                </div>
                                <?php foreach ($pointsData as $index => $step): ?>
                                    <div class="rt-node-v2" <?= $index === 0 ? 'style="background: #eff6ff; padding: 12px; border-radius: 8px; border: 1px solid #bfdbfe; margin-left: -12px; width: calc(100% + 24px); position: relative;"' : '' ?>>
                                        <div class="rt-icon-v2 <?= $step['type'] ?>" <?= $index === 0 ? 'style="box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); animation: pulse 2s infinite;"' : '' ?>>
                                            <span class="material-symbols-outlined">
                                                <?= $step['type'] === 'pickup' ? 'inventory_2' : 'local_shipping' ?>
                                            </span>
                                        </div>
                                        <div class="rt-content-v2">
                                            <div class="rt-title-v2 fs-13" <?= $index === 0 ? 'style="color: #1d4ed8; font-weight: 700;"' : '' ?>>
                                                Bước <?= $index + 1 ?>: <?= app_e($step['title']) ?> 
                                                <?php if ($index === 0): ?>
                                                    <span style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; margin-left: 6px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Điểm đến tiếp theo</span>
                                                <?php endif; ?>
                                                <small class="text-muted font-normal" <?= $index === 0 ? 'style="color: #60a5fa;"' : '' ?>>#<?= app_e($step['tracking_code']) ?></small>
                                            </div>
                                            <div class="rt-address-v2 fs-12" <?= $index === 0 ? 'style="color: #3b82f6; font-weight: 500;"' : '' ?>>
                                                <?= app_e($step['address']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php foreach ($group as $index => $order): ?>
                        <?php
                            $status = $order['status'];
                            // Logic trạng thái Node Timeline
                            $isPickupDone = in_array($status, ['in_transit', 'shipping', 'completed', 'returning', 'returned']);
                            $isDeliveryDone = in_array($status, ['completed', 'returned']);
                            
                            // Class badge trạng thái
                            $badgeStatusClass = $status === 'accepted' ? 'status-yellow' : 'status-blue';
                            $statusLabel = App\Models\Order::getStatusLabel($status);
                            $expressLabel = \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? null);
                            $expressClass = ($order['shipping_method'] ?? '') === 'express' ? 'express' : 'fast';
                            
                            // Highlight đơn hàng cần xử lý tiếp theo theo AI
                            $isNextAction = ($isBatch && $index === 0);
                        ?>
                        
                        <div class="order-card-v3 <?= $isNextAction ? 'order-card-next' : '' ?>" <?= $isNextAction ? 'style="border: 2px solid #3b82f6; box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15); transform: translateY(-2px); transition: all 0.3s ease;"' : '' ?>>
                            <?php if ($isNextAction): ?>
                                <div class="next-stop-badge" style="background: #2563eb; color: white; font-weight: 700; padding: 6px 12px; font-size: 12px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-top-left-radius: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; animation: pulse 2s infinite;">near_me</span> ĐIỂM ĐẾN TIẾP THEO (AI ĐỀ XUẤT)
                                </div>
                            <?php endif; ?>
                            <div class="oc-header-v3 <?= $isNextAction ? 'oc-header-next' : '' ?>">
                                <div class="oc-id-row">#<?= app_e($order['tracking_code']) ?></div>
                                <div class="oc-badges">
                                    <span class="oc-badge <?= $expressClass ?>"><?= app_e($expressLabel) ?></span>
                                    <span class="oc-badge <?= $badgeStatusClass ?>"><?= app_e($statusLabel) ?></span>
                                    <span class="oc-badge" style="background: #f1f5f9; color: #475569;"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: bottom;">weight</span> <?= app_e($order['weight'] ?? '0') ?> kg</span>
                                </div>
                            </div>

                            <div class="oc-body-v3">
                                <div class="route-timeline-v3">
                                    
                                    <div class="rt-node <?= $isPickupDone ? 'done' : 'active' ?>">
                                        <div class="rt-icon"></div>
                                        <div class="rt-content">
                                            <div class="rt-title">Lấy hàng: <strong><?= app_e($order['sender_name'] ?? 'Kho/Cửa hàng') ?></strong></div>
                                            <div class="rt-address"><?= app_e($order['pickup_address']) ?></div>
                                            <?php if (!$isPickupDone): ?>
                                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['sender_lat'] ?? 0) ?>,<?= (float)($order['sender_lng'] ?? 0) ?>&travelmode=driving" target="_blank" class="rt-link">
                                                    <span class="material-symbols-outlined">directions</span> Dẫn đường
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="rt-node <?= $isDeliveryDone ? 'done' : ($isPickupDone ? 'active' : 'pending') ?>">
                                        <div class="rt-icon"></div>
                                        <div class="rt-content">
                                            <div class="rt-title">Giao hàng: <strong><?= app_e($order['receiver_name'] ?? 'Khách hàng') ?></strong></div>
                                            <div class="rt-address"><?= app_e($order['delivery_address']) ?></div>
                                            <?php if ($isPickupDone && !$isDeliveryDone): ?>
                                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['receiver_lat'] ?? 0) ?>,<?= (float)($order['receiver_lng'] ?? 0) ?>&travelmode=driving" target="_blank" class="rt-link">
                                                    <span class="material-symbols-outlined">directions</span> Dẫn đường
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="rt-node done <?= !empty($order['note']) ? 'mb-24' : 'mb-0' ?>">
                                        <div class="rt-icon rt-icon-warning">
                                            <span class="material-symbols-outlined">priority_high</span>
                                        </div>
                                        <div class="rt-content rt-content-opaque">
                                            <div class="rt-title rt-title-main">Quản lý / Báo cáo sự cố</div>
                                            <a href="/driver/orders/view/<?= $order['id'] ?>" class="rt-link rt-link-primary">
                                                <span class="material-symbols-outlined">open_in_new</span> Xem chi tiết đơn hàng
                                            </a>
                                        </div>
                                    </div>

                                    <?php if (!empty($order['note'])): ?>
                                    <div class="rt-node done mb-0">
                                        <div class="rt-icon rt-icon-transparent">
                                            <span class="material-symbols-outlined">edit_note</span>
                                        </div>
                                        <div class="rt-content rt-content-opaque">
                                            <div class="rt-address rt-address-italic">"<?= app_e($order['note']) ?>"</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <form action="/driver/orders/update-status/<?= $order['id'] ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="redirect_to" value="active">
                                    
                                    <?php if ($status === 'accepted'): ?>
                                        <input type="hidden" name="status" value="picking_up">
                                        <button type="submit" class="btn-blue-full">BẮT ĐẦU ĐI LẤY</button>
                                    
                                    <?php elseif ($status === 'picking_up'): ?>
                                        <input type="hidden" name="status" value="in_transit">
                                        <button type="submit" class="btn-blue-full">ĐÃ LẤY HÀNG</button>
                                    
                                    <?php elseif (in_array($status, ['in_transit', 'shipping'])): ?>
                                        <input type="hidden" name="status" value="completed">
                                        
                                        <div class="auth-box-v3">
                                            <div class="auth-label">XÁC THỰC GIAO HÀNG</div>
                                            <div class="auth-input-group">
                                                <input type="text" name="delivery_pin" placeholder="Nhập mã PIN" pattern="\d{4}" maxlength="4" required>
                                                <label class="btn-cam-upload" title="Chụp ảnh minh chứng">
                                                    <span class="material-symbols-outlined">photo_camera</span>
                                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="display:none;">
                                        
                                        <div class="active-image-preview" style="display: none;">
                                            <img src="" class="preview-img-box">
                                            <button type="button" onclick="clearProofPreview(this)" class="btn-remove-img">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                            </button>
                                        </div>
                                        
                                        <button type="submit" class="btn-blue-full">GIAO THÀNH CÔNG</button>
                                    
                                    <?php elseif ($status === 'returning'): ?>
                                        <input type="hidden" name="status" value="returned">
                                        <div class="auth-box-v3">
                                            <div class="auth-label">ẢNH HOÀN HÀNG CHO KHO</div>
                                            <div class="auth-input-group">
                                                <label class="btn-cam-upload-w100">
                                                    <span class="material-symbols-outlined">photo_camera</span> Chụp ảnh minh chứng
                                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                            </div>
                                        </div>
                                        <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="display:none;">
                                        <div class="active-image-preview" style="display: none;">
                                            <img src="" class="preview-img-box">
                                            <button type="button" onclick="clearProofPreview(this)" class="btn-remove-img"><span class="material-symbols-outlined">close</span></button>
                                        </div>
                                        <button type="submit" class="btn-blue-full">HOÀN TRẢ XONG</button>
                                        
                                        <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-report-danger">BÁO CÁO SỰ CỐ / TỪ CHỐI</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // JS Xử lý hình ảnh 
    function handleProofSync(input) {
        if (input.files && input.files[0]) {
            const form = input.closest('form');
            const realInput = form.querySelector('.real-proof-input');
            const previewContainer = form.querySelector('.active-image-preview');
            const img = previewContainer.querySelector('img');
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(input.files[0]);
            realInput.files = dataTransfer.files;

            img.src = URL.createObjectURL(input.files[0]);
            previewContainer.style.display = 'block';
            input.value = '';
        }
    }
    function clearProofPreview(btn) {
        const form = btn.closest('form');
        const realInput = form.querySelector('.real-proof-input');
        const previewContainer = form.querySelector('.active-image-preview');
        realInput.value = '';
        previewContainer.style.display = 'none';
    }

    // Hàm điều khiển thu gọn / mở rộng Lộ trình AI
    function toggleRouteTimeline(timelineId, toggleBtn) {
        const timeline = document.getElementById(timelineId);
        const icon = toggleBtn.querySelector('.rt-toggle-icon');
        
        if (timeline.style.display === 'block') {
            timeline.style.display = 'none';
            icon.classList.remove('expanded');
        } else {
            timeline.style.display = 'block';
            icon.classList.add('expanded');
        }
    }

    // Các biến cho Map
    let inlineMap = null;
    let inlineRoutingControl = null;
    let driverMarker = null;
    let watchPositionId = null;
    let lastLocationPushTime = 0;

    function pushDriverLocation(position, force = false) {
        if (!position || !position.coords) return;
        const now = Date.now();
        if (!force && now - lastLocationPushTime < 10000) return;
        lastLocationPushTime = now;
        fetch('/api/driver/update-location', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lat: position.coords.latitude, lng: position.coords.longitude, accuracy: position.coords.accuracy })
        }).catch(() => {});
    }

    function recenterMap() {
        if (navigator.geolocation) {
            const btn = document.getElementById('btn-recenter');
            if(btn) btn.innerHTML = '<span class="material-symbols-outlined icon-spin">sync</span>';
            navigator.geolocation.getCurrentPosition((pos) => {
                const currentLat = pos.coords.latitude;
                const currentLng = pos.coords.longitude;
                if (inlineMap) inlineMap.setView([currentLat, currentLng], 16);
                if (driverMarker) driverMarker.setLatLng([currentLat, currentLng]);
                pushDriverLocation(pos, true);
                if(btn) btn.innerHTML = '<span class="material-symbols-outlined">my_location</span>';
            }, (err) => {
                if(btn) btn.innerHTML = '<span class="material-symbols-outlined">my_location</span>';
            }, { enableHighAccuracy: true, timeout: 5000 });
        }
    }

    // Hàm tính toán góc quay (Heading/Bearing) dựa trên 2 điểm tọa độ
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

    function createCustomMarkerIcon(icon, color) {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color};width:32px;height:32px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined marker-rotate-icon" style="color:#fff;font-size:18px;transition: transform 0.4s ease;">${icon}</span><div style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);border-width:6px 5px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-4px;left:50%;transform:translateX(-50%);border-width:5px 3px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
            iconSize: [32, 38], iconAnchor: [16, 38], popupAnchor: [0, -38]
        });
    }

    function buildGoogleMapsUrl(points) {
        const validPoints = (points || []).filter(p => Number(p.lat) !== 0 && Number(p.lng) !== 0);
        if (validPoints.length === 0) return '#';
        const hasDriverOrigin = validPoints[0].type === 'driver';
        const stops = hasDriverOrigin ? validPoints.slice(1) : validPoints;
        if (stops.length === 0) return '#';
        const destination = stops[stops.length - 1];
        const intermediateStops = stops.slice(0, -1).slice(0, 8);
        const params = new URLSearchParams({ api: '1', travelmode: 'driving', destination: `${destination.lat},${destination.lng}` });
        if (hasDriverOrigin) params.set('origin', `${validPoints[0].lat},${validPoints[0].lng}`);
        if (intermediateStops.length > 0) params.set('waypoints', intermediateStops.map(p => `${p.lat},${p.lng}`).join('|'));
        return `https://www.google.com/maps/dir/?${params.toString()}`;
    }

    function updateGoogleNavLink(elementId, points) {
        const link = document.getElementById(elementId);
        if (!link) return;
        const url = buildGoogleMapsUrl(points);
        link.href = url;
        link.classList.toggle('disabled', url === '#');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (batchPointsForMap && batchPointsForMap.length > 0) {
            // Tắt giao diện kéo/chạm mặc định của leaflet trên mobile để mượt hơn
            inlineMap = L.map('inline-route-map', { zoomControl: false }).setView([16.4637, 107.5909], 14);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            }).addTo(inlineMap);

            const validPoints = batchPointsForMap.filter(p => p.lat !== 0 && p.lng !== 0);
            if (validPoints.length > 0) {
                const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
                const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');
                const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');

                // Hàm cập nhật điểm đến tiếp theo lên UI
                function updateNextStopUI(stops) {
                    if(stops.length > 1) {
                        document.getElementById('nav-next-stop').innerText = stops[1].address || stops[1].title || "Đang xử lý...";
                    } else {
                        document.getElementById('nav-next-stop').innerText = "Đã tới điểm cuối";
                    }
                }

                if (navigator.geolocation) {
                    watchPositionId = navigator.geolocation.watchPosition((pos) => {
                        const currentLat = pos.coords.latitude;
                        const currentLng = pos.coords.longitude;
                        pushDriverLocation(pos);

                        if (!inlineRoutingControl) {
                            drawInlineRoute([{ lat: currentLat, lng: currentLng, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                        } else if (driverMarker) {
                            const prevLatLng = driverMarker.getLatLng();
                            const newLatLng = L.latLng(currentLat, currentLng);
                            
                            // Xoay đầu xe theo hướng di chuyển
                            if (prevLatLng.lat !== currentLat || prevLatLng.lng !== currentLng) {
                                const angle = calculateBearing(prevLatLng.lat, prevLatLng.lng, currentLat, currentLng);
                                if (driverMarker._icon) {
                                    const iconSpan = driverMarker._icon.querySelector('.marker-rotate-icon');
                                    if (iconSpan) iconSpan.style.transform = `rotate(${angle}deg)`;
                                }
                            }

                            driverMarker.setLatLng(newLatLng);
                            // Bỏ gọi setWaypoints liên tục mỗi giây để tránh DDoSing máy chủ OSRM API.
                            // Việc setLatLng của Marker ở trên đã đủ để trực quan hóa vị trí tài xế.
                            
                            updateGoogleNavLink('btn-google-batch-nav', [{ lat: currentLat, lng: currentLng, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                        }
                    }, (err) => {
                        if (!inlineRoutingControl) {
                            if (fallbackDriverLat !== 0 && fallbackDriverLng !== 0) {
                                drawInlineRoute([{ lat: fallbackDriverLat, lng: fallbackDriverLng, title: 'Vị trí của bạn (Lưu)', type: 'driver' }, ...validPoints]);
                            } else {
                                drawInlineRoute(validPoints);
                            }
                        }
                    }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 });
                } else {
                    drawInlineRoute(validPoints);
                }

                function drawInlineRoute(routePoints) {
                    const waypoints = routePoints.map(p => L.latLng(p.lat, p.lng));
                    updateGoogleNavLink('btn-google-batch-nav', routePoints);
                    updateNextStopUI(routePoints);

                    inlineRoutingControl = L.Routing.control({
                        waypoints: waypoints,
                        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
                        routeWhileDragging: false, addWaypoints: false, fitSelectedRoutes: false, show: false,
                        routeLine: function(route, options) {
                            var indices = route.waypointIndices;
                            var group = L.featureGroup();
                            
                            if (!indices || indices.length < 2) {
                                return L.polyline(route.coordinates, {color: '#ef4444', weight: 6, opacity: 0.9});
                            }
                            
                            // Phân tách chặng đường đầu tiên (Tài xế -> Điểm tiếp theo)
                            var firstSegment = route.coordinates.slice(indices[0], indices[1] + 1);
                            var activeLine = L.polyline(firstSegment, {
                                color: '#ef4444', // Đỏ nổi bật
                                weight: 6,
                                opacity: 1,
                                dashArray: '12, 12',
                                className: 'route-active-segment'
                            });
                            group.addLayer(activeLine);
                            
                            // Các chặng còn lại (Điểm tiếp theo -> Các điểm sau) làm mờ đi
                            if (indices.length > 2) {
                                var remainingSegment = route.coordinates.slice(indices[1]);
                                var dimLine = L.polyline(remainingSegment, {
                                    color: '#94a3b8', // Xám nhạt
                                    weight: 4,
                                    opacity: 0.6,
                                    dashArray: '5, 8'
                                });
                                group.addLayer(dimLine);
                            }
                            
                            return group;
                        },
                        createMarker: function(i, waypoint, n) {
                            const p = routePoints[i];
                            let icon = deliveryIcon;
                            if (p.type === 'driver') icon = driverIcon;
                            else if (p.type === 'pickup') icon = pickupIcon;

                            const marker = L.marker(waypoint.latLng, { icon: icon });
                            if (p.type === 'driver') driverMarker = marker;
                            
                            if (p.title) {
                                marker.bindPopup(`<b>${p.title}</b><br>${p.address || ''}`);
                            }
                            return marker;
                        }
                    }).addTo(inlineMap);
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
 