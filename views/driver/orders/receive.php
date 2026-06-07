<?php
/**
 * @var string $pageTitle
 * @var array|null $batches
 * @var float $walletBalance
 * @var string $message
 * @var int $currentPage
 * @var int $totalPages
 * @var bool $isOnline
 */
require_once __DIR__ . '/../../layouts/user_header.php'; 
?>

<link rel="stylesheet" href="/assets/css/style.css">

<div class="admin-container">
    <div class="active-page-header">
        <h2 class="active-page-title"><?= app_e($pageTitle ?? 'Nhận đơn hàng mới') ?></h2>
        <p class="active-page-subtitle">Radar đang tìm kiếm các chuyến đi phù hợp gần bạn.</p>
    </div>

    <div class="top-cards-grid">
        <div class="radar-toggle-card" style="margin: 0;">
            <div class="radar-toggle-info">
                <strong>
                    Sẵn sàng nhận đơn
                    <?php if ($isOnline ?? false): ?>
                        <span class="live-indicator">Live</span>
                    <?php endif; ?>
                </strong>
                <span>Bật để Radar quét và gợi ý chuyến.</span>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="driver-online-toggle" <?= ($isOnline ?? false) ? 'checked' : '' ?> onchange="toggleDriverOnline(this)">
                <span class="toggle-slider"></span>
            </label>
        </div>

        <div class="wallet-summary-card" style="margin: 0;">
            <div class="wallet-summary-info">
                <span class="wallet-label">Số dư ví</span>
                <span class="wallet-balance-amount"><?= app_money($walletBalance ?? 0, 'đ') ?></span>
            </div>
            <div class="wallet-actions">
                <a href="/driver/wallet/topup" class="btn-topup-outline">Nạp tiền</a>
                <a href="/driver/wallet/withdraw" class="btn-withdraw-outline">Rút tiền</a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && !empty($batches)): ?>
        <div class="ai-warning-box">
            <div class="ai-warning-header">
                <span class="material-symbols-outlined">info</span> Lưu ý từ hệ thống
            </div>
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($batches)): ?>
        <div class="empty-state-box">
            <span class="material-symbols-outlined">explore_off</span>
            <p><?= app_e($message ?: 'Không có chuyến đi nào phù hợp trong khu vực của bạn hiện tại.') ?></p>
        </div>
    <?php else: ?>
        
        <div class="batch-grid">
            <?php foreach ($batches as $batch): ?>
                <div class="batch-card-v2" style="margin: 0;">
                    
                    <div class="batch-header-v2">
                        <div class="batch-title">
                            <?php if (strpos($batch['batch_id'], 'BATCH_') !== false): ?>
                                <span class="material-symbols-outlined" style="color: var(--primary);">lightbulb</span>
                                GỢI Ý CHUYẾN #<?= app_e(str_replace('BATCH_', '', $batch['batch_id'])) ?>
                            <?php else: ?>
                                <span class="material-symbols-outlined">route</span>
                                LỘ TRÌNH <?= app_e($batch['batch_id']) ?>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if (strpos($batch['batch_id'], 'BATCH_') !== false): ?>
                                <span class="badge-best-choice" title="Lộ trình ghép chuyến tối ưu giúp tiết kiệm thời gian"><span class="material-symbols-outlined" style="font-size: 14px;">recommend</span> Khuyên nhận</span>
                            <?php endif; ?>
                            <?php if (($batch['priority'] ?? 2) < 2): ?>
                                <?php if ($batch['priority'] === 0): ?>
                                    <span class="badge-batch-type" style="background-color: var(--danger); color: white;" title="Hoàn thành đơn này sẽ được giảm 1 lần vi phạm!">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: bottom;">card_giftcard</span> SIÊU TỐC
                                    </span>
                                <?php else: ?>
                                    <span class="badge-batch-type">GIAO NHANH</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="batch-metrics-grid">
                        <div class="metric-col">
                            <span class="metric-label">Tổng đơn</span>
                            <div class="metric-value"><?= $batch['total_orders'] ?></div>
                        </div>
                        <div class="metric-col">
                            <span class="metric-label">Khối lượng</span>
                            <div class="metric-value"><?= $batch['total_weight'] ?>kg</div>
                        </div>
                        <div class="metric-col">
                            <span class="metric-label">Thời gian</span>
                            <div class="metric-value"><?= $batch['total_trip_duration_minutes'] ?>p</div>
                        </div>
                        <div class="metric-col">
                            <span class="metric-label">Độ hiệu quả</span>
                            <div class="metric-value"><?= $batch['efficiency_score'] ?></div>
                        </div>
                    </div>

                    <div class="batch-orders-list">
                        <strong style="display: block; margin-bottom: 10px; font-size: 14px;">Chi tiết đơn hàng</strong>
                        <?php if (!empty($batch['order_details'])): ?>
                            <?php foreach ($batch['order_details'] as $order): ?>
                                <div class="order-simple-item">
                                    <div class="order-simple-header">
                                        <span class="order-simple-title">Đơn #<?= app_e($order['id']) ?></span>
                                        <span class="order-simple-type" style="color: <?= $order['shipping_method_color'] ?? 'var(--primary)' ?>;">
                                            <?= app_e($order['shipping_method_label'] ?? 'Giao tiêu chuẩn') ?>
                                        </span>
                                    </div>
                                    <div class="order-simple-location">
                                        <span class="material-symbols-outlined">inventory_2</span>
                                        Lấy: <?= app_e($order['pickup_address'] ?? '') ?>
                                    </div>
                                    <div class="order-simple-location">
                                        <span class="material-symbols-outlined">location_on</span>
                                        Giao: <?= app_e($order['delivery_address'] ?? '') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="route-toggle-bar" onclick="toggleRouteTimeline('timeline-<?= app_e($batch['batch_id']) ?>', this)">
                        <span class="rt-toggle-text">Xem chi tiết lộ trình</span>
                        <span class="material-symbols-outlined rt-toggle-icon">expand_more</span>
                    </div>

                    <div id="timeline-<?= app_e($batch['batch_id']) ?>" class="batch-route-timeline-v2 collapsible-timeline">
                        <div class="rt-node-v2">
                            <div class="rt-icon-v2 start"></div>
                            <div class="rt-content-v2">
                                <div class="rt-title-v2">Vị trí của bạn</div>
                                <div class="rt-address-v2">Bắt đầu lộ trình (<?= $batch['access_duration_minutes'] ?> phút)</div>
                            </div>
                        </div>

                        <?php foreach ($batch['route_details'] as $index => $step): ?>
                            <div class="rt-node-v2">
                                <div class="rt-icon-v2 <?= $step['type'] ?>">
                                    <span class="material-symbols-outlined">
                                        <?= $step['type'] === 'pickup' ? 'inventory_2' : 'local_shipping' ?>
                                    </span>
                                </div>
                                <div class="rt-content-v2">
                                    <div class="rt-title-v2">
                                        <?= $step['type'] === 'pickup' ? 'Lấy hàng' : 'Giao hàng' ?> (Điểm <?= $index + 1 ?>)
                                    </div>
                                    <div class="rt-address-v2">
                                        <?= app_e($step['address']) ?>
                                        <small>(Đơn #<?= app_e($step['order_id']) ?>)</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="rt-node-v2">
                            <div class="rt-icon-v2 end"></div>
                            <div class="rt-content-v2">
                                <div class="rt-title-v2">Kết thúc chuyến đi</div>
                            </div>
                        </div>
                    </div>

                    <div class="batch-footer-v2">
                        <div class="batch-total-fee">
                            Tổng thu dự kiến
                            <strong><?= app_money($batch['total_fee']) ?></strong>
                        </div>
                        <form action="/driver/receive-orders" method="POST" style="margin:0;">
                            <?php foreach ($batch['order_ids'] as $oid): ?>
                                <input type="hidden" name="order_ids[]" value="<?= app_e($oid) ?>">
                            <?php endforeach; ?>
                            <input type="hidden" name="batch_code" value="<?= app_e($batch['batch_id']) ?>">
                            <input type="hidden" name="route_details" value="<?= app_e(json_encode($batch['route_details'])) ?>">
                            <button type="submit" class="btn-accept-trip">NHẬN CHUYẾN</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= app_component('pagination', [
            'currentPage' => $currentPage ?? 1,
            'totalPages' => $totalPages ?? 1
        ]) ?>
    <?php endif; ?>
</div>

<script>
    function toggleDriverOnline(cb) {
        const isOnline = cb.checked ? 1 : 0;
        cb.disabled = true; 
        window.location.href = '/driver/receive-orders?toggle_online=' + isOnline;
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
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>