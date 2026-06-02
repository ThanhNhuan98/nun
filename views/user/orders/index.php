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

    <!-- Filter Pills (Cuộn ngang trên Mobile) -->
    <div class="order-filters">
        <a href="/user/orders" class="filter-pill <?= empty($statusFilter) ? 'active' : '' ?>">Tất cả</a>
        <a href="/user/orders?status=awaiting_payment" class="filter-pill <?= ($statusFilter === 'awaiting_payment') ? 'active' : '' ?>">Chờ thanh toán</a>
        <a href="/user/orders?status=searching_driver" class="filter-pill <?= ($statusFilter === 'searching_driver') ? 'active' : '' ?>">Tìm tài xế</a>
        <a href="/user/orders?status=accepted" class="filter-pill <?= ($statusFilter === 'accepted') ? 'active' : '' ?>">Đã nhận đơn</a>
        <a href="/user/orders?status=picking_up" class="filter-pill <?= ($statusFilter === 'picking_up') ? 'active' : '' ?>">Đang lấy hàng</a>
        <a href="/user/orders?status=in_transit" class="filter-pill <?= ($statusFilter === 'shipping' || $statusFilter === 'in_transit') ? 'active' : '' ?>">Đang giao</a>
        <a href="/user/orders?status=completed" class="filter-pill <?= ($statusFilter === 'completed') ? 'active' : '' ?>">Hoàn thành</a>
        <a href="/user/orders?status=returning" class="filter-pill <?= ($statusFilter === 'returning') ? 'active' : '' ?>">Đang chuyển hoàn</a>
        <a href="/user/orders?status=returned" class="filter-pill <?= ($statusFilter === 'returned') ? 'active' : '' ?>">Đã hoàn hàng</a>
        <a href="/user/orders?status=disputed" class="filter-pill <?= ($statusFilter === 'disputed') ? 'active' : '' ?>">Khiếu nại</a>
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
                            <span class="material-symbols-outlined">storefront</span>
                            <div class="info-text">
                                <strong>Lấy hàng:</strong>
                                <span class="clamped-address" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: all 0.3s ease;"><?= app_e($order['sender_address'] ?? 'Chưa cập nhật') ?></span>
                                <a href="javascript:void(0)" class="btn-read-more" onclick="toggleAddress(this)" style="display: none; font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none; margin-top: 2px;">Xem thêm</a>
                            </div>
                        </div>

                        <div class="info-row">
                            <span class="material-symbols-outlined">location_on</span>
                            <div class="info-text">
                                <strong>Giao đến:</strong>
                                <span class="clamped-address" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: all 0.3s ease;"><?= app_e($order['receiver_address'] ?? 'Chưa cập nhật') ?></span>
                                <a href="javascript:void(0)" class="btn-read-more" onclick="toggleAddress(this)" style="display: none; font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none; margin-top: 2px;">Xem thêm</a>
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

        <?= app_component('pagination', [
            'currentPage' => $currentPage ?? 1,
            'totalPages' => $totalPages ?? 1,
            'queryParams' => array_filter([
                'status' => $statusFilter ?? ''
            ])
        ]) ?>
    <?php endif; ?>

</div>

<script>
    // Kịch bản tự động phát hiện văn bản bị tràn để hiển thị nút Xem thêm
    document.addEventListener('DOMContentLoaded', function() {
        const addressElements = document.querySelectorAll('.clamped-address');
        addressElements.forEach(el => {
            // So sánh chiều cao thực tế (scrollHeight) với chiều cao hiển thị (clientHeight)
            if (el.scrollHeight > el.clientHeight) {
                const btn = el.nextElementSibling;
                if (btn && btn.classList.contains('btn-read-more')) {
                    btn.style.display = 'inline-block';
                }
            }
        });
    });

    // Hàm xử lý khi người dùng bấm vào nút Xem thêm / Thu gọn
    function toggleAddress(btn) {
        const textSpan = btn.previousElementSibling;
        if (textSpan.style.webkitLineClamp === '2') {
            textSpan.style.webkitLineClamp = 'unset';
            btn.innerText = 'Thu gọn';
        } else {
            textSpan.style.webkitLineClamp = '2';
            btn.innerText = 'Xem thêm';
        }
    }
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
