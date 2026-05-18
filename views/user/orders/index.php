<?php
/**
 * @var string $pageTitle
 * @var string $statusFilter
 * @var array $orders
 * @var int $totalPages
 * @var int $currentPage
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="user-page-header">
        <div>
            <h2 class="user-page-title"><?= app_e($pageTitle ?? 'Danh sách đơn hàng') ?></h2>
            <p class="user-page-subtitle">Quản lý và theo dõi tiến trình vận chuyển các đơn hàng của bạn.</p>
        </div>
        <a href="/user/orders/create" class="btn-create-order">
            <span class="material-symbols-outlined" style="font-size: 18px;">add</span> Tạo đơn mới
        </a>
    </div>

    <?php if ($message = app_flash('flash_success')): ?>
        <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 24px; border: 1px solid #bbf7d0;">
            <?= app_e($message) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Pills (Cuộn ngang trên Mobile) -->
    <div class="order-filters">
        <a href="/user/orders" class="filter-pill <?= empty($statusFilter) ? 'active' : '' ?>">Tất cả</a>
        <a href="/user/orders?status=picking_up" class="filter-pill <?= ($statusFilter === 'picking_up') ? 'active' : '' ?>">Đang lấy hàng</a>
        <a href="/user/orders?status=in_transit" class="filter-pill <?= ($statusFilter === 'in_transit' || $statusFilter === 'shipping') ? 'active' : '' ?>">Đang giao</a>
        <a href="/user/orders?status=completed" class="filter-pill <?= ($statusFilter === 'completed') ? 'active' : '' ?>">Đã giao</a>
        <a href="/user/orders?status=cancelled" class="filter-pill <?= ($statusFilter === 'cancelled') ? 'active' : '' ?>">Đã hủy</a>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid var(--border-color); border-radius: 4px;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;">inbox</span>
            <p style="color: var(--text-muted); font-size: 15px; margin: 0;">Bạn chưa có đơn hàng nào.</p>
        </div>
    <?php else: ?>
        <div class="order-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="order-card-id">
                            NUN-<?= app_e(str_replace('NUN', '', $order['tracking_code'])) ?>
                        </a>
                        <?php 
                            $s = $order['status'];
                            $badgeClass = 'status-pending';
                            if (in_array($s, ['searching_driver', 'accepted', 'picking_up', 'in_transit', 'shipping'])) $badgeClass = 'status-shipping';
                            elseif ($s === 'completed') $badgeClass = 'status-completed';
                            elseif ($s === 'cancelled') $badgeClass = 'status-cancelled';
                        ?>
                        <span class="card-badge <?= $badgeClass ?>">
                            <?= app_e(app_status_label($s)) ?>
                        </span>
                    </div>

                    <div class="order-card-info">
                        <div class="info-row">
                            <span class="material-symbols-outlined">schedule</span>
                            <div class="info-text">
                                <strong>Ngày hẹn lấy:</strong>
                                <span><?= date('H:i d/m/Y', strtotime($order['scheduled_at'])) ?></span>
                            </div>
                        </div>

                        <div class="info-row">
                            <span class="material-symbols-outlined">location_on</span>
                            <div class="info-text">
                                <strong>Giao đến:</strong>
                                <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= app_e(app_format_address($order['receiver_address'] ?? 'Chưa cập nhật')) ?></span>
                            </div>
                        </div>

                        <?php if (!empty($order['driver_name'])): ?>
                        <div class="info-row">
                            <span class="material-symbols-outlined">two_wheeler</span>
                            <div class="info-text">
                                <strong>Tài xế:</strong>
                                <span><?= app_e($order['driver_name']) ?> - <?= app_e($order['driver_phone']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-card-footer">
                        <div class="fee-block">
                            <strong>Cước phí</strong>
                            <?= app_money($order['shipping_fee'] ?? 0, ' đ') ?>
                        </div>
                        <div class="time-block">
                            <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-edit-primary" style="padding: 6px 12px; font-size: 13px;">
                                <?= in_array($order['status'], ['completed', 'cancelled']) ? 'Chi tiết' : 'Theo dõi' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination-container" style="margin-top: 30px;">
                <?php
                $queryParams = [];
                if (!empty($statusFilter)) $queryParams['status'] = $statusFilter;
                ?>

                <?php if ($currentPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">Trước</a>
                <?php else: ?>
                    <span class="pagination-link disabled">Trước</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">Tiếp</a>
                <?php else: ?>
                    <span class="pagination-link disabled">Tiếp</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>