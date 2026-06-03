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
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    <div class="active-page-header">
        <h2 class="active-page-title"><?= app_e($pageTitle ?? 'Nhận đơn hàng mới') ?></h2>
        <p class="active-page-subtitle">Hệ thống AI đang quét các đơn hàng phù hợp xung quanh bạn.</p>
    </div>

    <div class="radar-toggle-card">
        <div class="radar-toggle-info">
            <strong>
                Sẵn sàng nhận đơn
                <?php if ($isOnline ?? false): ?>
                    <span class="live-indicator">Live</span>
                <?php endif; ?>
            </strong>
            <span>Bật để AI tự động quét và gợi ý chuyến ghép.</span>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="driver-online-toggle" <?= ($isOnline ?? false) ? 'checked' : '' ?> onchange="toggleDriverOnline(this)">
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div class="wallet-summary-card">
        <div class="wallet-summary-info">
            <span class="wallet-label">Số dư ví</span>
            <span class="wallet-balance-amount"><?= app_money($walletBalance ?? 0, 'đ') ?></span>
        </div>
        <div class="wallet-actions">
            <a href="/driver/wallet/withdraw" class="btn-withdraw-outline">Rút tiền</a>
            <a href="/driver/wallet/topup" class="btn-topup-outline">Nạp tiền</a>
        </div>
    </div>

    <?php if (!empty($message) && !empty($batches)): ?>
        <div class="ai-warning-box">
            <div class="ai-warning-header">
                <span class="material-symbols-outlined">warning</span> Sự cố ghép chuyến AI
            </div>
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($batches)): ?>
        <div class="empty-state-box">
            <span class="material-symbols-outlined">explore_off</span>
            <p><?= app_e($message ?: 'Không có chuyến đi nào phù hợp trong khu vực của bạn.') ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($batches as $batch): ?>
            <div class="batch-card-v2">
                <div class="batch-header-v2">
                    <div class="batch-title">
                        <span class="material-symbols-outlined">route</span>
                        Lộ trình đề xuất #<?= app_e(str_replace('BATCH_', '', $batch['batch_id'])) ?>
                    </div>
                    <?php if (($batch['priority'] ?? 2) < 2): ?>
                        <span class="badge-batch-type"><?= $batch['priority'] === 0 ? 'SIÊU TỐC' : 'GIAO NHANH' ?></span>
                    <?php endif; ?>
                </div>

                <div class="batch-metrics-grid">
                    <div class="metric-col">
                        <span class="metric-label">Số đơn</span>
                        <div class="metric-value"><?= $batch['total_orders'] ?></div>
                    </div>
                    <div class="metric-col">
                        <span class="metric-label">Tổng KG</span>
                        <div class="metric-value"><?= $batch['total_weight'] ?></div>
                    </div>
                    <div class="metric-col">
                        <span class="metric-label">Thời gian</span>
                        <div class="metric-value"><?= $batch['total_trip_duration_minutes'] ?> phút</div>
                    </div>
                    <div class="metric-col">
                        <span class="metric-label">Điểm AI</span>
                        <div class="metric-value"><?= $batch['efficiency_score'] ?></div>
                    </div>
                </div>

                <div class="batch-orders-list">
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

                <div class="batch-route-timeline">
                    <div class="route-node">
                        <div class="node-dot start"></div>
                        <div class="node-title">Vị trí của bạn</div>
                        <div class="node-address">Bắt đầu lộ trình (<?= $batch['access_duration_minutes'] ?> phút)</div>
                    </div>

                    <?php foreach ($batch['route_details'] as $step): ?>
                        <div class="route-node">
                            <div class="node-dot"></div>
                            <div class="node-title">
                                <?php if ($step['type'] === 'pickup'): ?>
                                    <span class="material-symbols-outlined node-title-icon pickup">inventory_2</span> Lấy hàng
                                <?php else: ?>
                                    <span class="material-symbols-outlined node-title-icon dropoff">local_shipping</span> Giao hàng
                                <?php endif; ?>
                            </div>
                            <div class="node-address">
                                <?= app_e($step['address']) ?> (Đơn #<?= app_e($step['order_id']) ?>)
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="route-node">
                        <div class="node-dot end"></div>
                        <div class="node-title">Kết thúc chuyến đi</div>
                    </div>
                </div>

                <div class="batch-footer-v2">
                    <div class="batch-total-fee">
                        Tổng thu
                        <strong><?= app_money($batch['total_fee']) ?></strong>
                    </div>
                    <form action="/driver/receive-orders" method="POST" style="margin:0;">
                        <?php foreach ($batch['order_ids'] as $oid): ?>
                            <input type="hidden" name="order_ids[]" value="<?= app_e($oid) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="batch_code" value="<?= app_e($batch['batch_id']) ?>">
                        <input type="hidden" name="route_details" value="<?= app_e(json_encode($batch['route_details'])) ?>">
                        <button type="submit" class="btn-accept-trip">Nhận chuyến</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?= app_component('pagination', [
            'currentPage' => $currentPage ?? 1,
            'totalPages' => $totalPages ?? 1
        ]) ?>
    <?php endif; ?>
</div>

<script>
    function toggleDriverOnline(cb) {
        const isOnline = cb.checked ? 1 : 0;
        cb.disabled = true; // Khóa nút ngay lập tức để tránh click liên tục
        window.location.href = '/driver/receive-orders?toggle_online=' + isOnline;
    }
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>