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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-order-actions">
                                        <div class="s-weight">TL: <?= number_format((float)($order['weight'] ?? 0), 1) ?>kg</div>
                                        <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-process-solid">Xử lý đơn này</a>
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="sub-order-actions" style="justify-content: flex-end;">
                                    <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-process-solid">Xử lý đơn này</a>
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
        const pickupIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34] });
        const deliveryIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34] });

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
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>