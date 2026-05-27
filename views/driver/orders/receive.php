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

<style>
    .toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
    .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .toggle-slider { background-color: var(--success); }
    input:checked + .toggle-slider:before { transform: translateX(22px); }
</style>

<div class="admin-container">
    <div class="active-page-header">
        <h2 class="active-page-title"><?= app_e($pageTitle ?? 'Nhận đơn hàng mới') ?></h2>
        <p class="active-page-subtitle">Hệ thống AI đang quét các đơn hàng phù hợp xung quanh bạn.</p>
    </div>

    <?php if ($errorMsg = app_flash('flash_error')): ?>
        <div class="alert-banner danger" style="margin-bottom: 24px;">
            <p><?= app_e($errorMsg) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($successMsg = app_flash('flash_success')): ?>
        <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 24px; border: 1px solid #bbf7d0;">
            <?= app_e($successMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Nút Bật/Tắt Nhận Đơn -->
    <div class="online-toggle-container" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 4px; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <strong style="display: block; font-size: 15px; color: var(--text-main); margin-bottom: 4px;">Sẵn sàng nhận đơn</strong>
            <span style="font-size: 13px; color: var(--text-muted);">Bật để AI tự động quét và gợi ý chuyến ghép.</span>
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

    <?php if (empty($batches)): ?>
        <div class="text-center text-muted py-5" style="background: #fff; border: 1px solid var(--border-color); border-radius: 4px; padding: 40px 20px;">
            <span class="material-symbols-outlined" style="font-size: 48px;">explore_off</span>
            <p class="mt-2"><?= app_e($message ?: 'Không có chuyến đi nào phù hợp trong khu vực của bạn.') ?></p>
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
                        <span class="metric-label">Điểm hiệu quả</span>
                        <div class="metric-value"><?= $batch['efficiency_score'] ?></div>
                    </div>
                </div>

                <div class="batch-orders-list" style="padding: 0 20px; margin-bottom: 10px;">
                    <?php if (!empty($batch['order_details'])): ?>
                        <?php foreach ($batch['order_details'] as $order): ?>
                            <div class="order-simple-item" style="border-bottom: 1px dashed var(--border-color); padding: 12px 0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <strong style="color: var(--text-main);">Đơn #<?= app_e($order['id']) ?></strong>
                                    <span style="font-size: 12px; font-weight: 600; color: <?= $order['shipping_method_color'] ?? 'var(--primary)' ?>;">
                                        <?= app_e($order['shipping_method_label'] ?? 'Giao tiêu chuẩn') ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">inventory_2</span> Lấy: <?= app_e($order['pickup_address'] ?? '') ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted);">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">location_on</span> Giao: <?= app_e($order['delivery_address'] ?? '') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- [NÂNG CẤP] Timeline Lộ trình chi tiết -->
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

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination-container" style="margin-top: 30px; margin-bottom: 30px;">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>" class="pagination-link">&laquo;</a>
                <?php else: ?>
                    <span class="pagination-link disabled">&laquo;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>" class="pagination-link">&raquo;</a>
                <?php else: ?>
                    <span class="pagination-link disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
