<?php
/**

 * @var int $currentPage
 * @var int $totalPages
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="admin-page-header" style="margin-bottom: 24px;">
        <h2 class="admin-page-title" style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            Lịch sử chuyến đi
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">
            Xem lại danh sách các đơn hàng bạn đã nhận, hoàn thành hoặc đã hủy.
        </p>
    </div>

    <div class="history-filter-card">
        <form action="/driver/history" method="GET" class="history-filter-form">
            <div class="filter-input-col">
                <label>Từ ngày</label>
                <div class="form-input-with-icon">
                    <span class="material-symbols-outlined icon-left" style="font-size: 18px;">calendar_today</span>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>" class="form-control">
                </div>
            </div>
            
            <div class="filter-input-col">
                <label>Đến ngày</label>
                <div class="form-input-with-icon">
                    <span class="material-symbols-outlined icon-left" style="font-size: 18px;">calendar_today</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate ?? '') ?>" class="form-control">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-submit" style="padding: 12px 24px; height: 44px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">filter_alt</span> Lọc
                </button>
                <?php if (!empty($startDate) || !empty($endDate)): ?>
                    <a href="/driver/history" class="btn-cancel" style="display: flex; align-items: center; height: 44px; padding: 0 16px;">Xóa lọc</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (!empty($orders)): ?>
        <div class="history-grid">
            <?php foreach ($orders as $order): ?>
                
                <div class="h-card">
                    <div class="h-card-header">
                        <div class="h-card-title">
                            <span class="material-symbols-outlined" style="color: #8b5cf6;">inventory_2</span>
                            Đơn #<?= app_e($order['tracking_code']) ?>
                        </div>
                        
                        <?php 
                            // Xử lý giao diện Badge Trạng thái
                            $status = $order['status'];
                            $badgeClass = 'warning';
                            $icon = 'schedule';
                            if ($status === 'completed') { $badgeClass = 'success'; $icon = 'check_circle'; }
                            elseif ($status === 'cancelled') { $badgeClass = 'danger'; $icon = 'cancel'; }
                        ?>
                        <div class="h-card-badge <?= $badgeClass ?>">
                            <span class="material-symbols-outlined"><?= $icon ?></span>
                            <?= app_e(app_status_label($status)) ?>
                        </div>
                    </div>
                    
                    <div class="h-card-time">
                        Cập nhật: <?= app_datetime($order['updated_at'], 'M d, H:i') ?>
                    </div>

                    <div class="h-timeline">
                        <div class="h-node">
                            <div class="h-node-icon"><span class="material-symbols-outlined">storefront</span></div>
                            <div class="h-node-label">ĐIỂM LẤY HÀNG</div>
                            <div class="h-node-text"><?= app_e(app_format_address($order['pickup_address'] ?? 'Đang cập nhật')) ?></div>
                        </div>
                        
                        <div class="h-node">
                            <div class="h-node-icon"><span class="material-symbols-outlined">location_on</span></div>
                            <div class="h-node-label">ĐIỂM GIAO HÀNG</div>
                            <div class="h-node-text"><?= app_e(app_format_address($order['delivery_address'] ?? 'Đang cập nhật')) ?></div>
                        </div>
                    </div>

                    <div class="h-card-footer">
                        <div class="h-card-fee">
                            Cước phí: <strong><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></strong>
                        </div>
                        <a href="/driver/orders/view/<?= $order['id'] ?>" class="btn-h-detail">Xem chi tiết</a>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination-container" style="margin-bottom: 30px;">
                <?php
                $queryParams = [];
                if (!empty($startDate)) $queryParams['start_date'] = $startDate;
                if (!empty($endDate)) $queryParams['end_date'] = $endDate;
                ?>

                <?php if ($currentPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">&laquo;</a>
                <?php else: ?>
                    <span class="pagination-link disabled">&laquo;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">&raquo;</a>
                <?php else: ?>
                    <span class="pagination-link disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="driver-empty-state" style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid var(--border-color); border-radius: 4px;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;">history</span>
            <h3 style="color: var(--text-main); margin-bottom: 10px; font-size: 18px;">Chưa có lịch sử chuyến đi</h3>
            <p style="color: var(--text-muted); font-size: 14px;">Bạn chưa nhận hoặc hoàn thành đơn hàng nào.</p>
            <a href="/driver/receive-orders" class="btn-submit" style="display: inline-flex; margin-top: 20px; text-decoration: none;">
                Đi tới Radar nhận đơn
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>