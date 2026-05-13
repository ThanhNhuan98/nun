<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="active-page-header">
        <h2 class="active-page-title">Lộ trình công việc</h2>
        <p class="active-page-subtitle"><?= $totalActive ?? 0 ?> đơn đang chạy</p>
    </div>

    <?php if (empty($groupedOrders)): ?>
        <div style="text-align: center; padding: 60px 20px; border: 1px solid var(--border-color); border-radius: 4px; background: #fff;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;">work_history</span>
            <h3 style="font-size: 16px; color: var(--text-main); margin-bottom: 8px;">Không có đơn hàng nào đang chạy</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Tất cả các đơn hàng của bạn đã hoàn thành. Hãy vào Radar để nhận chuyến mới!</p>
            <a href="/driver/receive-orders" class="btn-process-solid" style="display: inline-flex; width: auto; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 18px;">radar</span> Tới Radar nhận đơn
            </a>
        </div>
    <?php else: ?>
        
        <!-- Bản đồ dẫn đường chuyến ghép -->
        <div class="active-route-card" style="margin-bottom: 24px; border-color: var(--primary);">
            <div class="active-card-header" style="background: #eff6ff; padding: 12px 20px; border-bottom: 1px solid #bfdbfe;">
                <div class="header-left" style="color: var(--primary);">
                    <span class="material-symbols-outlined">navigation</span>
                    BẢN ĐỒ DẪN ĐƯỜNG TRỰC TIẾP
                </div>
            </div>
            <div id="inline-route-map" style="width: 100%; height: 400px; z-index: 1;"></div>
        </div>
        <script>
            let batchPointsForMap = [];
        </script>

        <div class="active-orders-list">
            <?php foreach ($groupedOrders as $timeKey => $group): ?>
                <?php 
                    $isBatch = count($group) > 1; 
                    $totalFee = array_sum(array_column($group, 'shipping_fee'));
                    $totalWeight = array_sum(array_column($group, 'weight'));
                ?>
                
                <?php if ($isBatch): ?>
                    <div class="active-route-card batch-mode">
                        <div class="active-card-header batch">
                            <div class="header-left">
                                <span class="material-symbols-outlined">layers</span>
                                CHUYẾN GHÉP (<?= count($group) ?> ĐƠN)
                                <?php 
                                    $pickupPoints = [];
                                    $deliveryPoints = [];
                                    foreach ($group as $o) {
                                        if (in_array($o['status'], ['accepted', 'picking_up'])) {
                                            if (!empty($o['sender_lat']) && !empty($o['sender_lng'])) {
                                                $pickupPoints[] = ['lat' => (float)$o['sender_lat'], 'lng' => (float)$o['sender_lng'], 'title' => 'Lấy hàng: ' . ($o['sender_name'] ?? ''), 'type' => 'pickup'];
                                            }
                                        }
                                        if (in_array($o['status'], ['accepted', 'picking_up', 'in_transit', 'shipping'])) {
                                            if (!empty($o['receiver_lat']) && !empty($o['receiver_lng'])) {
                                                $deliveryPoints[] = ['lat' => (float)$o['receiver_lat'], 'lng' => (float)$o['receiver_lng'], 'title' => 'Giao hàng: ' . ($o['receiver_name'] ?? 'Khách'), 'type' => 'delivery'];
                                            }
                                        }
                                    }
                                    $pointsData = array_merge($pickupPoints, $deliveryPoints);
                                    $pointsJson = htmlspecialchars(json_encode($pointsData), ENT_QUOTES, 'UTF-8');
                                ?>
                                <script>
                                    if (batchPointsForMap.length === 0) batchPointsForMap = <?= json_encode($pointsData) ?>;
                                </script>
                                <?php if (!empty($pointsData)): ?>
                                    <button type="button" onclick="showBatchRouteMap(<?= $pointsJson ?>)" class="btn-process-solid" style="padding: 4px 10px; font-size: 11px; margin-left: 10px; width: auto; background: #fff; color: var(--primary); border: 1px solid var(--primary);">Xem lộ trình</button>
                                <?php endif; ?>
                            </div>
                            <div class="header-right">
                                <div><span class="label">Tổng cước</span><strong class="text-primary"><?= app_money($totalFee, 'đ') ?></strong></div>
                                <div><span class="label">Tổng TL</span><strong><?= number_format($totalWeight, 1) ?>kg</strong></div>
                            </div>
                        </div>
                        
                        <div class="active-card-body">
                            <?php foreach ($group as $order): ?>
                                <?php 
                                    $sClass = 'gray';
                                    if (in_array($order['status'], ['picking_up', 'in_transit', 'shipping'])) $sClass = 'blue';
                                ?>
                                <div class="active-sub-order">
                                    <div class="sub-order-content">
                                        <div class="sub-header-row">
                                            <span class="tracking-code-pill">#<?= app_e($order['tracking_code']) ?></span>
                                            <span style="background-color: #f8fafc; color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? null) ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; border: 1px solid <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? null) ?>;">
                                                <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? null) ?>
                                            </span>
                                            <span class="status-badge-flat <?= $sClass ?>"><?= app_e(App\Models\Order::getStatusLabel($order['status'])) ?></span>
                                        </div>
                                        
                                        <div class="route-timeline-minimal">
                                            <div class="r-node">
                                                <span class="material-symbols-outlined r-icon">storefront</span>
                                                <div>
                                                    <div class="r-label">Điểm lấy (<strong><?= app_e($order['sender_name'] ?? 'Kho/Cửa hàng') ?></strong>)</div>
                                                    <div class="r-address"><?= app_e($order['pickup_address']) ?></div>
                                                </div>
                                            </div>
                                            <div class="r-node">
                                                <span class="material-symbols-outlined r-icon text-blue">location_on</span>
                                                <div>
                                                    <div class="r-label">Điểm giao (<strong>Khách hàng</strong>)</div>
                                                    <div class="r-address"><?= app_e($order['delivery_address']) ?></div>
                                                    <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                                                        <div style="margin-top: 6px; color: var(--danger); font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 2px 6px; border-radius: 4px; border: 1px solid #fca5a5;">
                                                            <span class="material-symbols-outlined" style="font-size: 13px;">warning</span>
                                                            Từng bom hàng: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="r-node">
                                                <span class="material-symbols-outlined r-icon" style="color: var(--warning);">assignment_late</span>
                                                <div>
                                                    <div class="r-label">Quản lý (<strong>Sự cố / Hủy đơn</strong>)</div>
                                                    <div class="r-address">
                                                        <a href="/driver/orders/view/<?= $order['id'] ?>" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                            <span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span> Xem chi tiết & Báo cáo
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-order-actions" style="width: auto; min-width: 180px;">
                                        <div class="s-weight" style="text-align: right; margin-bottom: 8px;">TL: <?= number_format((float)($order['weight'] ?? 0), 1) ?>kg</div>
                                        <form action="/driver/orders/update-status/<?= $order['id'] ?>" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px;">
                                            <input type="hidden" name="redirect_to" value="active">
                                            <?php if ($order['status'] === 'accepted'): ?>
                                                <input type="hidden" name="status" value="picking_up">
                                                <button type="submit" class="btn-process-solid" style="background:var(--warning); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Bắt đầu đi lấy</button>
                                            <?php elseif ($order['status'] === 'picking_up'): ?>
                                                <input type="hidden" name="status" value="in_transit">
                                                <button type="submit" class="btn-process-solid" style="background:var(--primary); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Đã lấy hàng & Đi giao</button>
                                            <?php elseif (in_array($order['status'], ['in_transit', 'shipping'])): ?>
                                                <input type="hidden" name="status" value="completed">
                                                <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Chụp ảnh minh chứng:</label>
                                                <input type="file" name="proof_image" accept="image/*" capture="environment" required style="font-size:11px; width: 100%; border: 1px solid var(--border-color); padding: 4px; border-radius: 4px;">
                                                <button type="submit" class="btn-process-solid" style="background:var(--success); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Giao thành công</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                
                <?php else: ?>
                    <?php $order = $group[0]; ?>
                    <div class="active-route-card">
                        <div class="active-card-header">
                            <div class="header-left" style="color: var(--text-main);">
                                <span class="material-symbols-outlined" style="color: var(--text-muted);">local_shipping</span>
                                ĐƠN LẺ 
                                <span style="color: var(--text-muted); font-weight: normal; margin-left: 6px; font-size: 13px;">#<?= app_e($order['tracking_code']) ?></span>
                                <?php 
                                    $pickupPoints = [];
                                    $deliveryPoints = [];
                                    if (in_array($order['status'], ['accepted', 'picking_up'])) {
                                        if (!empty($order['sender_lat']) && !empty($order['sender_lng'])) {
                                            $pickupPoints[] = ['lat' => (float)$order['sender_lat'], 'lng' => (float)$order['sender_lng'], 'title' => 'Lấy hàng: ' . ($order['sender_name'] ?? ''), 'type' => 'pickup'];
                                        }
                                    }
                                    if (in_array($order['status'], ['accepted', 'picking_up', 'in_transit', 'shipping'])) {
                                        if (!empty($order['receiver_lat']) && !empty($order['receiver_lng'])) {
                                            $deliveryPoints[] = ['lat' => (float)$order['receiver_lat'], 'lng' => (float)$order['receiver_lng'], 'title' => 'Giao hàng: ' . ($order['receiver_name'] ?? 'Khách'), 'type' => 'delivery'];
                                        }
                                    }
                                    $pointsData = array_merge($pickupPoints, $deliveryPoints);
                                    $pointsJson = htmlspecialchars(json_encode($pointsData), ENT_QUOTES, 'UTF-8');
                                ?>
                                <script>
                                    if (batchPointsForMap.length === 0) batchPointsForMap = <?= json_encode($pointsData) ?>;
                                </script>
                                <?php if (!empty($pointsData)): ?>
                                    <button type="button" onclick="showBatchRouteMap(<?= $pointsJson ?>)" class="btn-process-solid" style="padding: 4px 10px; font-size: 11px; margin-left: 10px; width: auto; background: #fff; color: var(--text-main); border: 1px solid var(--border-color);">Xem lộ trình</button>
                                <?php endif; ?>
                            </div>
                            <div class="header-right">
                                <div><span class="label">Cước phí</span><strong class="text-primary"><?= app_money($order['shipping_fee'], 'đ') ?></strong></div>
                                <div><span class="label">Trọng lượng</span><strong><?= number_format((float)($order['weight'] ?? 0), 1) ?>kg</strong></div>
                            </div>
                        </div>
                        
                        <div class="active-card-body">
                            <div class="active-sub-order">
                                <div class="sub-order-content">
                                    <div class="sub-header-row">
                                        <?php 
                                            $sClass = 'gray';
                                            if (in_array($order['status'], ['picking_up', 'in_transit', 'shipping'])) $sClass = 'blue';
                                        ?>
                                        <span style="background-color: #f8fafc; color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? null) ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; border: 1px solid <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? null) ?>;">
                                            <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? null) ?>
                                        </span>
                                        <span class="status-badge-flat <?= $sClass ?>"><?= app_e(App\Models\Order::getStatusLabel($order['status'])) ?></span>
                                    </div>
                                    
                                    <div class="route-timeline-minimal">
                                        <div class="r-node">
                                            <span class="material-symbols-outlined r-icon">storefront</span>
                                            <div>
                                                <div class="r-label">Điểm lấy (<strong><?= app_e($order['sender_name'] ?? 'Kho/Cửa hàng') ?></strong>)</div>
                                                <div class="r-address"><?= app_e($order['pickup_address']) ?></div>
                                            </div>
                                        </div>
                                        <div class="r-node">
                                            <span class="material-symbols-outlined r-icon text-blue">location_on</span>
                                            <div>
                                                <div class="r-label">Điểm giao (<strong>Khách hàng</strong>)</div>
                                                <div class="r-address"><?= app_e($order['delivery_address']) ?></div>
                                                <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                                                    <div style="margin-top: 6px; color: var(--danger); font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 2px 6px; border-radius: 4px; border: 1px solid #fca5a5;">
                                                        <span class="material-symbols-outlined" style="font-size: 13px;">warning</span>
                                                        Từng bom hàng: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="r-node">
                                            <span class="material-symbols-outlined r-icon" style="color: var(--warning);">assignment_late</span>
                                            <div>
                                                <div class="r-label">Quản lý (<strong>Sự cố / Hủy đơn</strong>)</div>
                                                <div class="r-address">
                                                    <a href="/driver/orders/view/<?= $order['id'] ?>" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span> Xem chi tiết & Báo cáo
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="sub-order-actions" style="width: auto; min-width: 180px;">
                                    <form action="/driver/orders/update-status/<?= $order['id'] ?>" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px;">
                                        <input type="hidden" name="redirect_to" value="active">
                                        <?php if ($order['status'] === 'accepted'): ?>
                                            <input type="hidden" name="status" value="picking_up">
                                            <button type="submit" class="btn-process-solid" style="background:var(--warning); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Bắt đầu đi lấy</button>
                                        <?php elseif ($order['status'] === 'picking_up'): ?>
                                            <input type="hidden" name="status" value="in_transit">
                                            <button type="submit" class="btn-process-solid" style="background:var(--primary); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Đã lấy hàng & Đi giao</button>
                                        <?php elseif (in_array($order['status'], ['in_transit', 'shipping'])): ?>
                                            <input type="hidden" name="status" value="completed">
                                            <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Chụp ảnh minh chứng:</label>
                                            <input type="file" name="proof_image" accept="image/*" capture="environment" required style="font-size:11px; width: 100%; border: 1px solid var(--border-color); padding: 4px; border-radius: 4px;">
                                            <button type="submit" class="btn-process-solid" style="background:var(--success); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Giao thành công</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="routeModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; padding:20px; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div style="background:#fff; width:100%; max-width:900px; height:85vh; border-radius:4px; display:flex; flex-direction:column; overflow:hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background: #f8fafc;">
            <h3 style="margin:0; font-size:18px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--primary);">route</span> 
                Bản đồ lộ trình đa điểm
            </h3>
            <button onclick="closeRouteModal()" style="background:none; border:none; font-size:28px; cursor:pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>
        <div id="multi-route-map" style="flex:1; width:100%;"></div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    let multiMap = null;
    let multiRoutingControl = null;

    function createCustomMarkerIcon(icon, color) {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color};width:36px;height:36px;border-radius:50%;border:3px solid #fff;box-shadow:0 4px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined" style="color:#fff;font-size:20px;">${icon}</span><div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-width:8px 6px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border-width:6px 4px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
            iconSize: [36, 44], iconAnchor: [18, 44], popupAnchor: [0, -44]
        });
    }

    function showBatchRouteMap(points) {
        document.getElementById('routeModal').style.display = 'flex';
        
        if (!multiMap) {
            multiMap = L.map('multi-route-map').setView([16.4637, 107.5909], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(multiMap);
        }

        if (multiRoutingControl) multiMap.removeControl(multiRoutingControl);

        const validPoints = points.filter(p => p.lat !== 0 && p.lng !== 0);
        if (validPoints.length === 0) return;

        const waypoints = validPoints.map(p => L.latLng(p.lat, p.lng));
        const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
        const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');

        multiRoutingControl = L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
            routeWhileDragging: false, addWaypoints: false, fitSelectedRoutes: true, show: true,
            lineOptions: { styles: [{color: '#3b82f6', opacity: 0.9, weight: 6}] },
            createMarker: function(i, wp, nWps) {
                const pointInfo = validPoints[i];
                const icon = pointInfo.type === 'pickup' ? pickupIcon : deliveryIcon;
                return L.marker(wp.latLng, {icon: icon}).bindPopup(`<b>${i+1}. ${pointInfo.title}</b>`);
            }
        }).addTo(multiMap);
        
        setTimeout(() => { multiMap.invalidateSize(); }, 300);
    }

    function closeRouteModal() { document.getElementById('routeModal').style.display = 'none'; }

    // Khởi tạo bản đồ dẫn đường trực tiếp (Inline Map)
    document.addEventListener('DOMContentLoaded', function() {
        if (batchPointsForMap && batchPointsForMap.length > 0) {
            const inlineMap = L.map('inline-route-map').setView([16.4637, 107.5909], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(inlineMap);

            const validPoints = batchPointsForMap.filter(p => p.lat !== 0 && p.lng !== 0);
            if (validPoints.length > 0) {
                const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
                const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');
                const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((pos) => {
                        drawInlineRoute([{ lat: pos.coords.latitude, lng: pos.coords.longitude, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                    }, () => drawInlineRoute(validPoints), { enableHighAccuracy: true, timeout: 5000 });
                } else {
                    drawInlineRoute(validPoints);
                }

                function drawInlineRoute(routePoints) {
                    const waypoints = routePoints.map(p => L.latLng(p.lat, p.lng));
                    L.Routing.control({
                        waypoints: waypoints,
                        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                        routeWhileDragging: false, addWaypoints: false, fitSelectedRoutes: true, show: false,
                        lineOptions: { styles: [{color: '#3b82f6', opacity: 0.9, weight: 6}] },
                        createMarker: function(i, wp) {
                            let icon = routePoints[i].type === 'pickup' ? pickupIcon : deliveryIcon;
                            if (routePoints[i].type === 'driver') icon = driverIcon;
                            return L.marker(wp.latLng, {icon: icon}).bindPopup(`<b>${routePoints[i].type === 'driver' ? '' : i + '. '}${routePoints[i].title}</b>`);
                        }
                    }).addTo(inlineMap);
                }
            }
        } else {
            const mapContainer = document.getElementById('inline-route-map');
            if (mapContainer) mapContainer.parentElement.style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>