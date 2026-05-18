<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>
        
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
        <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fecaca;">
        <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<div class="wallet-summary-card">
    <div class="wallet-summary-info">
        <span class="wallet-label">Số dư ví</span>
        <span class="wallet-balance-amount"><?= app_money($walletBalance ?? 2450000, ' đ') ?></span>
    </div>
    <a href="/driver/wallet/topup" class="btn-topup-outline">
        <span class="material-symbols-outlined" style="font-size: 18px;">payments</span> Nạp tiền vào ví
    </a>
</div>

<div id="radar-loading" class="radar-scan-wrapper">
    <div class="radar-animation-box">
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-core">
            <span class="material-symbols-outlined">radar</span>
        </div>
    </div>
    <div class="radar-status-text">Đang quét khu vực...</div>
</div>

<div id="batches-wrapper" style="display: none;">
    <div class="section-divider">Chuyến Mới Khu Vực</div>
    <div id="batches-container"></div>
</div>

<div id="empty-state" style="display: none; text-align: center; padding: 40px; border: 1px solid var(--border-color); border-radius: 4px; background: #fff;">
    <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;">location_off</span>
    <h3 style="font-size: 16px; color: var(--text-main); margin-bottom: 8px;">Không tìm thấy chuyến đi mới</h3>
    <p id="empty-state-message" style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Hiện tại không có đơn hàng nào chờ ghép tài xế trong khu vực của bạn.</p>
    <button onclick="window.location.reload();" class="btn-topup-outline" style="margin: 0 auto;">
        Tải lại radar
    </button>
</div>

<!-- Route Modal -->
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
    document.addEventListener('DOMContentLoaded', async function() {
        const loadingEl = document.getElementById('radar-loading');
        const wrapperEl = document.getElementById('batches-wrapper');
        const containerEl = document.getElementById('batches-container');
        const emptyEl = document.getElementById('empty-state');
        
        try {
            // Gọi API tính toán AI ở Background
            const res = await fetch('/driver/receive-orders?ajax=1');
            const data = await res.json();
            
            loadingEl.style.display = 'none';
            
            if (data.success && data.batches && data.batches.length > 0) {
                wrapperEl.style.display = 'block';
                renderBatches(data.batches);
            } else {
                emptyEl.style.display = 'block';
                if (data.message) {
                    document.getElementById('empty-state-message').innerText = data.message;
                }
            }
        } catch (e) {
            console.error('Radar scan failed:', e);
            loadingEl.innerHTML = '<div style="color: #ef4444; text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px;">error</span><h4>Lỗi kết nối máy chủ</h4></div>';
        }
        
        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        }

        function renderBatches(batches) {
            let html = '';
            batches.forEach((batch, index) => {
                const isBest = index === 0 && batch.efficiency_score > 0;
                const efficiencyFmt = batch.efficiency_score ? formatMoney(batch.efficiency_score) + '/phút' : 'N/A';
                const accessMin = batch.access_duration_minutes != null ? batch.access_duration_minutes + ' phút' : 'N/A';
                const totalMin = batch.total_trip_duration_minutes != null ? batch.total_trip_duration_minutes + ' phút' : 'N/A';
                const totalFee = formatMoney(batch.total_fee || 0);
                const displayBatchId = batch.batch_id ? batch.batch_id.replace('BATCH_', 'CHUYẾN ') : 'N/A';

                // Gom mảng các điểm dừng (Stops) để vẽ Timeline
                let stops = [];
                let pickupPoints = [];
                let deliveryPoints = [];
                let rawPickups = [];
                let rawDeliveries = [];

                if (batch.order_details) {
                    batch.order_details.forEach((order, idx) => {
                        rawPickups.push({ title: `Đơn #${order.tracking_code}`, address: order.pickup_address });
                        pickupPoints.push({ lat: order.sender_lat, lng: order.sender_lng, title: `Lấy hàng: ${order.sender_name || ''}`, type: 'pickup' });
                        
                        rawDeliveries.push({ title: `Đơn #${order.tracking_code}`, address: order.delivery_address });
                        deliveryPoints.push({ lat: order.receiver_lat, lng: order.receiver_lng, title: `Giao hàng: ${order.receiver_name || ''}`, type: 'delivery' });
                    });
                }

                // Gom nhóm các địa chỉ lấy hàng trùng nhau
                let pickupGroups = {};
                rawPickups.forEach(p => {
                    if (!pickupGroups[p.address]) pickupGroups[p.address] = [];
                    pickupGroups[p.address].push(p.title);
                });
                for (let addr in pickupGroups) stops.push({ title: `Lấy hàng (${pickupGroups[addr].join(', ')})`, address: addr });

                // Gom nhóm các địa chỉ giao hàng trùng nhau
                let deliveryGroups = {};
                rawDeliveries.forEach(d => {
                    if (!deliveryGroups[d.address]) deliveryGroups[d.address] = [];
                    deliveryGroups[d.address].push(d.title);
                });
                for (let addr in deliveryGroups) stops.push({ title: `Giao hàng (${deliveryGroups[addr].join(', ')})`, address: addr });
                
                // Sắp xếp Lấy hàng trước, Giao hàng sau
                const mapPoints = [...pickupPoints, ...deliveryPoints];
                const mapPointsJson = JSON.stringify(mapPoints).replace(/"/g, '&quot;');

                let timelineHtml = '<div class="batch-route-timeline">';
                stops.forEach((stop, idx) => {
                    let dotClass = '';
                    if (idx === 0) dotClass = 'start';
                    else if (idx === stops.length - 1) dotClass = 'end';
                    
                    timelineHtml += `
                        <div class="route-node">
                            <div class="node-dot ${dotClass}"></div>
                            <div class="node-title">${stop.title}</div>
                            <div class="node-address">${stop.address || 'Đang cập nhật...'}</div>
                        </div>
                    `;
                });
                timelineHtml += '</div>';

                // Hiển thị danh sách chi tiết các đơn hàng trong chuyến
                let ordersListHtml = '';
                if (batch.order_details && batch.total_orders > 0) {
                    ordersListHtml = '<div style="padding: 15px 20px 5px 20px; border-bottom: 1px dashed var(--border-color); background: #f8fafc;">';
                    ordersListHtml += `<div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">Chi tiết ${batch.total_orders} đơn hàng:</div>`;
                    ordersListHtml += '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    batch.order_details.forEach(order => {
                        
                        // Tính toán thời gian và đổi màu đỏ nếu còn dưới 15 phút (900000ms)
                        let timeStyle = 'color: var(--text-muted);';
                        if (order.scheduled_at) {
                            const scheduledTime = new Date(order.scheduled_at.replace(/-/g, '/')).getTime();
                            const now = new Date().getTime();
                            if (scheduledTime - now <= 900000) {
                                timeStyle = 'color: var(--danger); font-weight: 600;';
                            }
                        }

                        ordersListHtml += `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; background: #fff; padding: 12px 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                                <div style="display: flex; flex-direction: column; gap: 4px; overflow: hidden; padding-right: 10px;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="material-symbols-outlined" style="color: var(--primary); font-size: 16px;">inventory_2</span>
                                        <strong style="font-size: 13px; color: var(--text-main);">#${order.tracking_code}</strong>
                                        ${order.shipping_method_label ? `<span style="background-color: #f8fafc; color: ${order.shipping_method_color}; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; border: 1px solid ${order.shipping_method_color};">${order.shipping_method_label}</span>` : ''}
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); padding-left: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${order.pickup_address || ''}">
                                        <strong>Lấy:</strong> ${order.pickup_address || 'Đang cập nhật'}
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); padding-left: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${order.delivery_address || ''}">
                                        <strong>Giao:</strong> ${order.delivery_address || 'Đang cập nhật'}
                                    </div>
                                    <div style="font-size: 12px; padding-left: 22px; margin-top: 2px; ${timeStyle}">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">schedule</span>
                                        <strong>Hẹn lấy:</strong> ${order.formatted_scheduled_at || 'Càng sớm càng tốt'}
                                    </div>
                                </div>
                                <strong style="font-size: 14px; color: var(--primary); flex-shrink: 0; margin-top: 2px;">${formatMoney(order.shipping_fee || 0)}</strong>
                            </div>
                        `;
                    });
                    ordersListHtml += '</div></div>';
                }

                // Lấy order_ids ẩn cho Form Submit
                let inputsHtml = '';
                if (batch.optimized_route) {
                    batch.optimized_route.forEach(oid => {
                        inputsHtml += `<input type="hidden" name="order_ids[]" value="${oid}">`;
                    });
                }

                html += `
                <div class="batch-card-v2">
                    <div class="batch-header-v2">
                        <div class="batch-title">
                             ${displayBatchId} (${batch.total_orders} đơn)
                            ${isBest ? '<span class="badge-best-choice"><span class="material-symbols-outlined" style="font-size:14px;">workspace_premium</span> Lựa chọn tốt nhất</span>' : ''}
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button type="button" onclick="showBatchRouteMap(${mapPointsJson})" style="padding: 4px 8px; font-size: 11px; background: #fff; color: var(--primary); border: 1px solid var(--primary); border-radius: 4px; cursor: pointer; font-weight: 600;">Xem Lộ Trình</button>
                            <span class="badge-batch-type">${batch.total_orders > 1 ? 'GHÉP CHUYẾN' : 'ĐƠN LẺ'}</span>
                        </div>
                    </div>

                    <div class="batch-metrics-grid">
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">trending_up</span>
                            <div class="metric-label">Hiệu quả</div>
                            <div class="metric-value" style="color: #059669;">${efficiencyFmt}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">directions_run</span>
                            <div class="metric-label">Điểm lấy</div>
                            <div class="metric-value">${accessMin}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">timer</span>
                            <div class="metric-label">Tổng T.Gian</div>
                            <div class="metric-value">${totalMin}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">weight</span>
                            <div class="metric-label">Khối Lượng</div>
                            <div class="metric-value">${batch.total_weight ? parseFloat(batch.total_weight).toFixed(1) + ' kg' : 'N/A'}</div>
                        </div>
                    </div>

                    ${ordersListHtml}

                    ${timelineHtml}

                    <div class="batch-footer-v2">
                        <div class="batch-total-fee">
                            Tổng phí
                            <strong>${totalFee}</strong>
                        </div>
                        <form method="POST" action="/driver/receive-orders" style="margin: 0;">
                            ${inputsHtml}
                            <button type="submit" class="btn-accept-trip">Nhận Chuyến Ngay</button>
                        </form>
                    </div>
                </div>
                `;
            });
            containerEl.innerHTML = html;
        }
    });

    // Hiển thị Bản đồ lộ trình đa điểm
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

        const validPoints = points.filter(p => p.lat && p.lng);
        if (validPoints.length === 0) return;

        const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
        const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');
        const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                drawMultiRoute([{ lat: pos.coords.latitude, lng: pos.coords.longitude, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
            }, () => drawMultiRoute(validPoints), { enableHighAccuracy: true, timeout: 5000 });
        } else {
            drawMultiRoute(validPoints);
        }

        function drawMultiRoute(routePoints) {
            const waypoints = routePoints.map(p => L.latLng(p.lat, p.lng));
            multiRoutingControl = L.Routing.control({
                waypoints: waypoints,
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                routeWhileDragging: false, addWaypoints: false, fitSelectedRoutes: true, show: false,
                lineOptions: { styles: [{color: '#3b82f6', opacity: 0.9, weight: 6}] },
                createMarker: function(i, wp) {
                    let icon = routePoints[i].type === 'pickup' ? pickupIcon : deliveryIcon;
                    if (routePoints[i].type === 'driver') icon = driverIcon;
                    return L.marker(wp.latLng, {icon: icon}).bindPopup(`<b>${routePoints[i].type === 'driver' ? '' : i + '. '}${routePoints[i].title}</b>`);
                }
            }).addTo(multiMap);
            setTimeout(() => multiMap.invalidateSize(), 300);
        }
    }

    function closeRouteModal() { document.getElementById('routeModal').style.display = 'none'; }
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>