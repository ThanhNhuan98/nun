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
        <div class="active-route-card live-navigation-card" style="margin-bottom: 24px; border-color: var(--primary); overflow: hidden;">
            <div class="active-card-header" style="background: #eff6ff; padding: 12px 20px; border-bottom: 1px solid #bfdbfe;">
                <div class="header-left" style="color: var(--primary);">
                    <span class="material-symbols-outlined">navigation</span>
                    BẢN ĐỒ DẪN ĐƯỜNG TRỰC TIẾP
                </div>
                <div class="driver-nav-actions">
                    <a id="btn-google-batch-nav" class="driver-nav-open disabled" href="#" target="_blank" rel="noopener">
                        <span class="material-symbols-outlined" style="font-size: 16px;">near_me</span> Google Maps
                    </a>
                </div>
            </div>

            <!-- Bọc riêng khu vực Map để các bảng nổi không đè lên Header -->
            <div style="position: relative;">
            <div id="driver-nav-panel" class="driver-nav-panel" style="display:none;">
                <div class="driver-nav-panel-main">
                    <span class="material-symbols-outlined">flag</span>
                    <div>
                        <div class="driver-nav-label">Điểm dừng tiếp theo</div>
                        <div id="driver-next-stop" class="driver-next-stop">Đang cập nhật...</div>
                    </div>
                </div>
                <div id="driver-stop-count" class="driver-stop-count"></div>
            </div>

            <!-- Hộp tóm tắt lộ trình nổi trên map -->
            <div id="nav-summary-box" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; background: rgba(255,255,255,0.95); backdrop-filter: blur(5px); padding: 10px 24px; border-radius: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: none; align-items: center; gap: 16px; border: 1px solid var(--primary);">
                <span class="material-symbols-outlined" style="color: var(--primary); font-size: 32px;">directions_car</span>
                <div id="nav-summary" style="display: flex; gap: 16px; align-items: center;"></div>
            </div>

            <!-- Nút Định vị lại -->
            <button onclick="recenterMap()" id="btn-recenter" style="position: absolute; bottom: 30px; right: 10px; z-index: 1000; background: #fff; border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-main); box-shadow: 0 1px 5px rgba(0,0,0,0.65);" title="Định vị lại">
                <span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>
            </button>

            <div id="inline-route-map" style="width: 100%; height: 500px; z-index: 1;"></div>
            </div>
        </div>
        <script>
            let batchPointsForMap = [];
            let fallbackDriverLat = 0;
            let fallbackDriverLng = 0;
        </script>

        <div class="active-orders-list">
            <?php foreach ($groupedOrders as $batchCode => $batchData): ?>
                <?php
                    $group = $batchData['orders'];
                    $routeDetails = $batchData['route_details'];
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
                                    $pointsData = app_build_driver_route_points($group, $routeDetails);
                                    $pointsJson = htmlspecialchars(json_encode($pointsData), ENT_QUOTES, 'UTF-8');
                                ?>
                                <script>
                                    if (batchPointsForMap.length === 0) batchPointsForMap = <?= json_encode($pointsData) ?>;
                                    if (fallbackDriverLat === 0) fallbackDriverLat = <?= (float)($group[0]['driver_lat'] ?? 0) ?>;
                                    if (fallbackDriverLng === 0) fallbackDriverLng = <?= (float)($group[0]['driver_lng'] ?? 0) ?>;
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

                        <?php if (!empty($routeDetails)): ?>
                            <div class="batch-route-timeline" style="padding: 20px; border-bottom: 1px solid #bfdbfe;">
                                <div class="route-node">
                                    <div class="node-dot start"></div>
                                    <div class="node-title">Lộ trình tối ưu</div>
                                    <div class="node-address">Thứ tự các điểm dừng do AI đề xuất</div>
                                </div>

                                <?php foreach ($routeDetails as $step): ?>
                                    <div class="route-node">
                                        <div class="node-dot"></div>
                                        <div class="node-title">
                                            <?php if ($step['type'] === 'pickup'): ?>
                                                <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary); vertical-align: bottom;">inventory_2</span> Lấy hàng
                                            <?php else: ?>
                                                <span class="material-symbols-outlined" style="font-size: 16px; color: var(--success); vertical-align: bottom;">local_shipping</span> Giao hàng
                                            <?php endif; ?>
                                        </div>
                                        <div class="node-address">
                                            <?= app_e($step['address']) ?> (Đơn #<?= app_e($step['order_id']) ?>)
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

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
                                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['sender_lat'] ?? 0) ?>,<?= (float)($order['sender_lng'] ?? 0) ?>&travelmode=driving" target="_blank" style="margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; background: #fff; color: #ea4335; padding: 4px 8px; border-radius: 4px; text-decoration: none; border: 1px solid #fca5a5; font-weight: 600; transition: 0.2s;">
                                                        <span class="material-symbols-outlined" style="font-size: 14px;">near_me</span> Chỉ đường lấy hàng
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="r-node">
                                                <span class="material-symbols-outlined r-icon text-blue">location_on</span>
                                                <div>
                                                    <div class="r-label">Điểm giao (<strong><?= app_e($order['receiver_name'] ?? 'Khách hàng') ?></strong>)</div>
                                                    <div class="r-address"><?= app_e($order['delivery_address']) ?></div>
                                                    <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                                                        <div style="margin-top: 6px; color: var(--danger); font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 2px 6px; border-radius: 4px; border: 1px solid #fca5a5;">
                                                            <span class="material-symbols-outlined" style="font-size: 13px;">warning</span>
                                                    Vi phạm giao nhận: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                                                        </div>
                                                    <?php endif; ?>
                                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['receiver_lat'] ?? 0) ?>,<?= (float)($order['receiver_lng'] ?? 0) ?>&travelmode=driving" target="_blank" style="margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; background: #fff; color: #ea4335; padding: 4px 8px; border-radius: 4px; text-decoration: none; border: 1px solid #fca5a5; font-weight: 600; transition: 0.2s;">
                                                        <span class="material-symbols-outlined" style="font-size: 14px;">near_me</span> Chỉ đường giao hàng
                                                    </a>
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
                                            <?php if (!empty($order['note'])): ?>
                                            <div class="r-node" style="margin-top: -8px;">
                                                <span class="material-symbols-outlined r-icon" style="color: var(--text-muted); font-size: 16px !important;">edit_note</span>
                                                <div>
                                                    <div class="r-label" style="font-style: italic; color: var(--text-main);">"<?= app_e($order['note']) ?>"</div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
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
                                                <label style="font-size:11px; color:#d97706; font-weight:bold; margin-bottom: -4px;">* Mã PIN (Hỏi khách):</label>
                                                <input type="text" name="delivery_pin" placeholder="4 số" pattern="\d{4}" maxlength="4" style="width: 100%; padding: 8px; border: 1px solid #fde047; border-radius: 4px; font-size: 14px; text-align: center; letter-spacing: 2px; font-weight: bold; color: var(--primary); margin-top: 8px;" required>
                                                <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Chụp ảnh minh chứng:</label>
                                                <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 4px; margin-top: 8px;">
                                                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#3b82f6; padding:8px; border-radius:4px; border:1px dashed #3b82f6; cursor:pointer; font-size:11px; font-weight:600;">
                                                        <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span> Chụp ảnh
                                                        <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                    </label>
                                                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#10b981; padding:8px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:11px; font-weight:600;">
                                                        <span class="material-symbols-outlined" style="font-size:16px;">image</span> Chọn ảnh
                                                        <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                                    </label>
                                                </div>
                                                <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                                                <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 8px; text-align: center;">
                                                    <img src="" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                    <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0;"><span class="material-symbols-outlined" style="font-size: 14px;">close</span></button>
                                                </div>
                                                <button type="submit" class="btn-process-solid" style="background:var(--success); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Giao thành công</button>
                                            <?php elseif ($order['status'] === 'returning'): ?>
                                                <input type="hidden" name="status" value="returned">
                                                <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Ảnh minh chứng hoàn hàng:</label>
                                                <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 4px; margin-top: 8px;">
                                                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#ea580c; padding:8px; border-radius:4px; border:1px dashed #ea580c; cursor:pointer; font-size:11px; font-weight:600;">
                                                        <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span> Chụp ảnh
                                                        <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                    </label>
                                                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#10b981; padding:8px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:11px; font-weight:600;">
                                                        <span class="material-symbols-outlined" style="font-size:16px;">image</span> Chọn ảnh
                                                        <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                                    </label>
                                                </div>
                                                <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                                                <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 8px; text-align: center;">
                                                    <img src="" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                    <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0;"><span class="material-symbols-outlined" style="font-size: 14px;">close</span></button>
                                                </div>
                                                <button type="submit" class="btn-process-solid" style="background:var(--warning); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Hoàn hàng xong</button>
                                                <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-process-solid" style="background:#fff; color:var(--danger); border:1px solid var(--danger); padding:8px; border-radius:4px; font-weight:600; cursor:pointer; text-align:center; display:block; margin-top:4px; text-decoration:none;">Sự cố / Khách từ chối</a>
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
                                    $pointsData = app_build_driver_route_points([$order], $routeDetails);
                                    $pointsJson = htmlspecialchars(json_encode($pointsData), ENT_QUOTES, 'UTF-8');
                                ?>
                                <script>
                                    if (batchPointsForMap.length === 0) batchPointsForMap = <?= json_encode($pointsData) ?>;
                                    if (fallbackDriverLat === 0) fallbackDriverLat = <?= (float)($order['driver_lat'] ?? 0) ?>;
                                    if (fallbackDriverLng === 0) fallbackDriverLng = <?= (float)($order['driver_lng'] ?? 0) ?>;
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
                                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['sender_lat'] ?? 0) ?>,<?= (float)($order['sender_lng'] ?? 0) ?>&travelmode=driving" target="_blank" style="margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; background: #fff; color: #ea4335; padding: 4px 8px; border-radius: 4px; text-decoration: none; border: 1px solid #fca5a5; font-weight: 600; transition: 0.2s;">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">near_me</span> Chỉ đường lấy hàng
                                                </a>
                                            </div>
                                        </div>
                                        <div class="r-node">
                                            <span class="material-symbols-outlined r-icon text-blue">location_on</span>
                                            <div>
                                                <div class="r-label">Điểm giao (<strong><?= app_e($order['receiver_name'] ?? 'Khách hàng') ?></strong>)</div>
                                                <div class="r-address"><?= app_e($order['delivery_address']) ?></div>
                                                <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                                                    <div style="margin-top: 6px; color: var(--danger); font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 2px 6px; border-radius: 4px; border: 1px solid #fca5a5;">
                                                        <span class="material-symbols-outlined" style="font-size: 13px;">warning</span>
                                                        Vi phạm giao nhận: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                                                    </div>
                                                <?php endif; ?>
                                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($order['receiver_lat'] ?? 0) ?>,<?= (float)($order['receiver_lng'] ?? 0) ?>&travelmode=driving" target="_blank" style="margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; font-size: 11px; background: #fff; color: #ea4335; padding: 4px 8px; border-radius: 4px; text-decoration: none; border: 1px solid #fca5a5; font-weight: 600; transition: 0.2s;">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">near_me</span> Chỉ đường giao hàng
                                                </a>
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
                                            <?php if (!empty($order['note'])): ?>
                                            <div class="r-node" style="margin-top: -8px;">
                                                <span class="material-symbols-outlined r-icon" style="color: var(--text-muted); font-size: 16px !important;">edit_note</span>
                                                <div>
                                                    <div class="r-label" style="font-style: italic; color: var(--text-main);">"<?= app_e($order['note']) ?>"</div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
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
                                            <label style="font-size:11px; color:#d97706; font-weight:bold; margin-bottom: -4px;">* Mã PIN (Hỏi khách):</label>
                                            <input type="text" name="delivery_pin" placeholder="4 số" pattern="\d{4}" maxlength="4" style="width: 100%; padding: 8px; border: 1px solid #fde047; border-radius: 4px; font-size: 14px; text-align: center; letter-spacing: 2px; font-weight: bold; color: var(--primary); margin-top: 8px;" required>
                                            <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Chụp ảnh minh chứng:</label>
                                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 4px; margin-top: 8px;">
                                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#3b82f6; padding:8px; border-radius:4px; border:1px dashed #3b82f6; cursor:pointer; font-size:11px; font-weight:600;">
                                                    <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span> Chụp ảnh
                                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#10b981; padding:8px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:11px; font-weight:600;">
                                                    <span class="material-symbols-outlined" style="font-size:16px;">image</span> Chọn ảnh
                                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                            </div>
                                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                                            <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 8px; text-align: center;">
                                                <img src="" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0;"><span class="material-symbols-outlined" style="font-size: 14px;">close</span></button>
                                            </div>
                                            <button type="submit" class="btn-process-solid" style="background:var(--success); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Giao thành công</button>
                                        <?php elseif ($order['status'] === 'returning'): ?>
                                            <input type="hidden" name="status" value="returned">
                                            <label style="font-size:11px; color:var(--danger); font-weight:bold; margin-bottom: -4px;">* Ảnh minh chứng hoàn hàng:</label>
                                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 4px; margin-top: 8px;">
                                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#ea580c; padding:8px; border-radius:4px; border:1px dashed #ea580c; cursor:pointer; font-size:11px; font-weight:600;">
                                                    <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span> Chụp ảnh
                                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:4px; background:#fff; color:#10b981; padding:8px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:11px; font-weight:600;">
                                                    <span class="material-symbols-outlined" style="font-size:16px;">image</span> Chọn ảnh
                                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                                </label>
                                            </div>
                                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                                            <div class="active-image-preview" style="display: none; position: relative; margin-bottom: 8px; text-align: center;">
                                                <img src="" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0;"><span class="material-symbols-outlined" style="font-size: 14px;">close</span></button>
                                            </div>
                                            <button type="submit" class="btn-process-solid" style="background:var(--warning); color:#fff; border:none; padding:8px; border-radius:4px; font-weight:600; cursor:pointer;">Hoàn hàng xong</button>
                                            <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-process-solid" style="background:#fff; color:var(--danger); border:1px solid var(--danger); padding:8px; border-radius:4px; font-weight:600; cursor:pointer; text-align:center; display:block; margin-top:4px; text-decoration:none;">Sự cố / Khách từ chối</a>
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
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; background: #f8fafc;">
            <h3 style="margin:0; font-size:18px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--primary);">route</span>
                Bản đồ lộ trình đa điểm
            </h3>
            <div class="driver-nav-actions">
                <a id="btn-google-modal-nav" class="driver-nav-open disabled" href="#" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined" style="font-size: 16px;">near_me</span> Google Maps
                </a>
                <button onclick="closeRouteModal()" style="background:none; border:none; font-size:28px; cursor:pointer; color: var(--text-muted); line-height: 1;">&times;</button>
            </div>
        </div>
        <div id="multi-route-map" style="flex:1; width:100%;"></div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // Xử lý đồng bộ ảnh minh chứng (Preview) lên giao diện trước khi upload
    function handleProofSync(input) {
        if (input.files && input.files[0]) {
            const form = input.closest('form');
            const realInput = form.querySelector('.real-proof-input');
            const previewContainer = form.querySelector('.active-image-preview');
            const img = previewContainer.querySelector('img');
            const actionBtns = form.querySelector('.proof-action-btns');

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(input.files[0]);
            realInput.files = dataTransfer.files;

            img.src = URL.createObjectURL(input.files[0]);
            previewContainer.style.display = 'block';
            actionBtns.style.display = 'none';

            input.value = '';
        }
    }
    // Xóa ảnh minh chứng đã chọn khỏi giao diện
    function clearProofPreview(btn) {
        const form = btn.closest('form');
        const realInput = form.querySelector('.real-proof-input');
        const previewContainer = form.querySelector('.active-image-preview');
        const actionBtns = form.querySelector('.proof-action-btns');

        realInput.value = '';
        previewContainer.style.display = 'none';
        actionBtns.style.display = 'flex';
    }
    let multiMap = null;
    let multiRoutingControl = null;

    // Các biến cho chế độ Dẫn đường Inline
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
            body: JSON.stringify({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy
            })
        }).catch(() => {});
    }

    // Đưa bản đồ về lại vị trí trung tâm dựa trên GPS của thiết bị
    function recenterMap() {
        if (navigator.geolocation) {
            const btn = document.getElementById('btn-recenter');
            if(btn) btn.innerHTML = '<span class="material-symbols-outlined icon-spin" style="font-size: 20px;">sync</span>';

            navigator.geolocation.getCurrentPosition((pos) => {
                const currentLat = pos.coords.latitude;
                const currentLng = pos.coords.longitude;
                if (inlineMap) inlineMap.setView([currentLat, currentLng], 17);
                if (driverMarker) driverMarker.setLatLng([currentLat, currentLng]);
                pushDriverLocation(pos, true);
                if(btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>';
            }, (err) => {
                if(btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>';
                alert("Không thể lấy vị trí hiện tại. Vui lòng kiểm tra quyền GPS.");
            }, { enableHighAccuracy: true, timeout: 5000 });
        } else {
            alert("Trình duyệt của bạn không hỗ trợ định vị.");
        }
    }

    // Tạo Icon Marker tùy chỉnh cho bản đồ Leaflet
    function createCustomMarkerIcon(icon, color) {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color};width:36px;height:36px;border-radius:50%;border:3px solid #fff;box-shadow:0 4px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined" style="color:#fff;font-size:20px;">${icon}</span><div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-width:8px 6px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border-width:6px 4px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
            iconSize: [36, 44], iconAnchor: [18, 44], popupAnchor: [0, -44]
        });
    }

    // Tạo đường dẫn URL để mở ứng dụng Google Maps
    function buildGoogleMapsUrl(points) {
        const validPoints = (points || []).filter(p => Number(p.lat) !== 0 && Number(p.lng) !== 0);
        if (validPoints.length === 0) return '#';

        const hasDriverOrigin = validPoints[0].type === 'driver';
        const stops = hasDriverOrigin ? validPoints.slice(1) : validPoints;
        if (stops.length === 0) return '#';

        const destination = stops[stops.length - 1];
        const intermediateStops = stops.slice(0, -1).slice(0, 8);
        const params = new URLSearchParams({
            api: '1',
            travelmode: 'driving',
            destination: `${destination.lat},${destination.lng}`
        });

        if (hasDriverOrigin) {
            params.set('origin', `${validPoints[0].lat},${validPoints[0].lng}`);
        }
        if (intermediateStops.length > 0) {
            params.set('waypoints', intermediateStops.map(p => `${p.lat},${p.lng}`).join('|'));
        }

        return `https://www.google.com/maps/dir/?${params.toString()}`;
    }

    // Cập nhật Link mở Google Maps cho nút bấm trên giao diện
    function updateGoogleNavLink(elementId, points) {
        const link = document.getElementById(elementId);
        if (!link) return;

        const url = buildGoogleMapsUrl(points);
        link.href = url;
        link.classList.toggle('disabled', url === '#');
    }

    // Hiển thị thông tin điểm dừng tiếp theo trên thanh điều hướng
    function updateDriverNavPanel(routePoints, summary = null) {
        const panel = document.getElementById('driver-nav-panel');
        const nextStop = document.getElementById('driver-next-stop');
        const stopCount = document.getElementById('driver-stop-count');
        if (!panel || !nextStop || !stopCount) return;

        const validStops = (routePoints || []).filter(p => p.type !== 'driver' && Number(p.lat) !== 0 && Number(p.lng) !== 0);
        if (validStops.length === 0) {
            panel.style.display = 'none';
            return;
        }

        const firstStop = validStops[0];
        panel.style.display = 'flex';
        nextStop.textContent = firstStop.title || firstStop.address || 'Điểm dừng tiếp theo';
        stopCount.textContent = summary
            ? `${validStops.length} điểm dừng • ${(summary.totalDistance / 1000).toFixed(1)} km`
            : `${validStops.length} điểm dừng`;
    }

    // Hiển thị bản đồ lộ trình đa điểm (dành cho chuyến ghép)
    function showBatchRouteMap(points) {
        document.getElementById('routeModal').style.display = 'flex';

        if (!multiMap) {
            multiMap = L.map('multi-route-map').setView([16.4637, 107.5909], 13);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '&copy; Google Maps'
            }).addTo(multiMap);
        }

        if (multiRoutingControl) multiMap.removeControl(multiRoutingControl);

        const validPoints = points.filter(p => p.lat !== 0 && p.lng !== 0);
        if (validPoints.length === 0) return;
        updateGoogleNavLink('btn-google-modal-nav', validPoints);

        const waypoints = validPoints.map(p => L.latLng(p.lat, p.lng));
        const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
        const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');

        multiRoutingControl = L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
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

    // Đóng Modal bản đồ lộ trình
    function closeRouteModal() { document.getElementById('routeModal').style.display = 'none'; }

    // Khởi tạo bản đồ dẫn đường trực tiếp (Inline Map)
    document.addEventListener('DOMContentLoaded', function() {
        if (batchPointsForMap && batchPointsForMap.length > 0) {
            inlineMap = L.map('inline-route-map').setView([16.4637, 107.5909], 13);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '&copy; Google Maps'
            }).addTo(inlineMap);

            const validPoints = batchPointsForMap.filter(p => p.lat !== 0 && p.lng !== 0);
            if (validPoints.length > 0) {
                const pickupIcon = createCustomMarkerIcon('storefront', '#f59e0b');
                const deliveryIcon = createCustomMarkerIcon('location_on', '#10b981');
                const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');

                if (navigator.geolocation) {
                    watchPositionId = navigator.geolocation.watchPosition((pos) => {
                        const currentLat = pos.coords.latitude;
                        const currentLng = pos.coords.longitude;
                        pushDriverLocation(pos);

                        if (!inlineRoutingControl) {
                            // Vẽ lần đầu tiên
                            drawInlineRoute([{ lat: currentLat, lng: currentLng, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                        } else if (driverMarker) {
                            // Cập nhật vị trí marker
                            const newLatLng = L.latLng(currentLat, currentLng);
                            driverMarker.setLatLng(newLatLng);

                            // Cập nhật lại waypoint đầu tiên của lộ trình
                            let waypoints = inlineRoutingControl.getWaypoints();
                            waypoints[0].latLng = newLatLng;
                            inlineRoutingControl.setWaypoints(waypoints);
                            updateGoogleNavLink('btn-google-batch-nav', [{ lat: currentLat, lng: currentLng, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                            updateDriverNavPanel([{ lat: currentLat, lng: currentLng, title: 'Vị trí của bạn', type: 'driver' }, ...validPoints]);
                        }
                    }, (err) => {
                        console.warn("Không lấy được GPS trực tiếp, dùng vị trí lưu trữ:", err);
                        if (!inlineRoutingControl) {
                            if (fallbackDriverLat !== 0 && fallbackDriverLng !== 0) {
                                drawInlineRoute([{ lat: fallbackDriverLat, lng: fallbackDriverLng, title: 'Vị trí của bạn (Lưu trữ)', type: 'driver' }, ...validPoints]);
                            } else {
                                drawInlineRoute(validPoints);
                            }
                        }
                    }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 });
                } else {
                    if (fallbackDriverLat !== 0 && fallbackDriverLng !== 0) {
                        drawInlineRoute([{ lat: fallbackDriverLat, lng: fallbackDriverLng, title: 'Vị trí của bạn (Lưu trữ)', type: 'driver' }, ...validPoints]);
                    } else {
                        drawInlineRoute(validPoints);
                    }
                }

                // Vẽ tuyến đường chi tiết nối các điểm trên bản đồ
                function drawInlineRoute(routePoints) {
                    const waypoints = routePoints.map(p => L.latLng(p.lat, p.lng));
                    updateGoogleNavLink('btn-google-batch-nav', routePoints);
                    updateDriverNavPanel(routePoints);

                    inlineRoutingControl = L.Routing.control({
                        waypoints: waypoints,
                        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
                        routeWhileDragging: false, addWaypoints: false, fitSelectedRoutes: true, show: true, collapsible: true,
                        lineOptions: { styles: [{color: '#3b82f6', opacity: 0.9, weight: 6}] },
                        createMarker: function(i, wp) {
                            let icon = routePoints[i].type === 'pickup' ? pickupIcon : deliveryIcon;
                            if (routePoints[i].type === 'driver') {
                                icon = driverIcon;
                                driverMarker = L.marker(wp.latLng, {icon: icon});
                                return driverMarker.bindPopup(`<b>Vị trí của bạn</b>`);
                            }
                            return L.marker(wp.latLng, {icon: icon}).bindPopup(`<b>${routePoints[i].type === 'driver' ? '' : i + '. '}${routePoints[i].title}</b>`);
                        }
                    }).addTo(inlineMap);

                    inlineRoutingControl.on('routesfound', function(e) {
                        const routes = e.routes;
                        const summary = routes[0].summary;
                        updateDriverNavPanel(routePoints, summary);
                        document.getElementById('nav-summary-box').style.display = 'flex';
                        document.getElementById('nav-summary').innerHTML = `
                            <div style="font-size: 20px; font-weight: 800; color: var(--success);">${Math.round(summary.totalTime / 60)} phút</div>
                            <div style="font-size: 15px; color: var(--text-main); font-weight: 600;">${(summary.totalDistance / 1000).toFixed(1)} km</div>
                        `;
                    });
                }
            }
        } else {
            const mapContainer = document.getElementById('inline-route-map');
            if (mapContainer) mapContainer.parentElement.style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
