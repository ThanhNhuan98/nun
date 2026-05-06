<?php
/**
 * @var string $pageTitle
 * @var array $orders
 * @var string $statusFilter
 * @var string $search
 * @var int $currentPage
 * @var int $totalPages
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<link rel="stylesheet" href="/assets/css/style.css">

<div class="admin-container">
    
    <div class="admin-page-header">
        <h2 class="admin-page-title"><?= htmlspecialchars($pageTitle ?? 'Quản lý đơn hàng') ?></h2>
    </div>
    
    <form method="GET" action="/admin/orders" class="order-search-form">
        <span class="material-symbols-outlined search-icon">search</span>
        <input type="text" name="search" value="<?= app_e($search ?? '') ?>" placeholder="Tìm mã vận đơn, SĐT khách/tài xế...">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?= app_e($statusFilter) ?>">
        <?php endif; ?>
        <button type="submit" style="display: none;"></button> </form>

    <div class="order-filters">
        <a href="/admin/orders<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
           class="filter-pill <?= empty($statusFilter) ? 'active' : '' ?>">
           Tất cả
        </a>
        
        <?php 
        // Danh sách trạng thái cần hiển thị trên thanh filter
        $filterOptions = [
            'pending' => 'Chờ xử lý',
            'searching_driver' => 'Đang tìm tài xế',
            'in_transit' => 'Đang giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];
        foreach ($filterOptions as $val => $label): 
            $isActive = ($statusFilter === $val);
            // Ghép URL giữ nguyên từ khóa tìm kiếm nếu có
            $url = "/admin/orders?status=" . $val;
            if (!empty($search)) $url .= "&search=" . urlencode($search);
        ?>
            <a href="<?= $url ?>" class="filter-pill <?= $isActive ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="order-grid">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                
                <div class="order-card">
                    
                    <div class="order-card-header">
                        <a href="/admin/orders/view/<?= app_e($order['id']) ?>" class="order-card-id">
                            #<?= app_e($order['tracking_code']) ?>
                        </a>
                        
                        <?php 
                            $rawStatusClass = app_status_class($order['status']); 
                            $cardBadgeClass = 'status-pending'; 
                            if (strpos($rawStatusClass, 'success') !== false || $order['status'] == 'completed') $cardBadgeClass = 'status-completed';
                            if (strpos($rawStatusClass, 'info') !== false || $order['status'] == 'in_transit') $cardBadgeClass = 'status-shipping';
                            if (strpos($rawStatusClass, 'danger') !== false || $order['status'] == 'cancelled') $cardBadgeClass = 'status-cancelled';
                        ?>
                        <span class="card-badge <?= $cardBadgeClass ?>">
                            <?= app_e(app_status_label($order['status'])) ?>
                        </span>
                    </div>

                    <div class="order-card-info">
                        <div class="info-row">
                            <span class="material-symbols-outlined">person</span>
                            <div class="info-text">
                                <strong>Khách hàng</strong>
                                <span>
                                    <?= app_e($order['customer_name'] ?? 'Khách lẻ') ?> 
                                    <?= !empty($order['customer_phone']) ? '- ' . app_e($order['customer_phone']) : '' ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-row">
                            <span class="material-symbols-outlined">two_wheeler</span>
                            <div class="info-text">
                                <strong>Tài xế</strong>
                                <span>
                                    <?php if (!empty($order['driver_name'])): ?>
                                        <?= app_e($order['driver_name']) ?> - <?= app_e($order['driver_phone'] ?? '') ?>
                                    <?php else: ?>
                                        <em style="color:#94a3b8;">Chưa có tài xế</em>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-card-footer" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 15px;">
                        <div class="fee-block">
                            <strong style="display: block; margin-bottom: 3px;">Cước: <?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></strong>
                            <span style="color: <?= ($order['payment_status'] === 'paid') ? '#10b981' : '#ef4444' ?>; font-size: 13px;">
                                <?= $order['payment_method'] === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' ?> - 
                                <?= ($order['payment_status'] === 'paid') ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="time-block" style="text-align: right; color: #64748b; font-size: 13px;">
                                <?= app_datetime($order['scheduled_at'], 'd/m/Y') ?><br>
                                <?= app_datetime($order['scheduled_at'], 'H:i') ?>
                            </div>
                            <a href="/admin/orders/view/<?= app_e($order['id']) ?>" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 13px; background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; background: #fff; border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-muted);">
                <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 10px; display: block;">inbox</span>
                Không tìm thấy đơn hàng nào phù hợp.
            </div>
        <?php endif; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="pagination-container" style="margin-bottom: 30px;">
            <?php
            $queryParams = [];
            if (!empty($statusFilter)) $queryParams['status'] = $statusFilter;
            if (!empty($search)) $queryParams['search'] = $search;
            ?>

            <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">&laquo; Trước</a>
            <?php else: ?>
                <span class="pagination-link disabled">&laquo; Trước</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">Sau &raquo;</a>
            <?php else: ?>
                <span class="pagination-link disabled">Sau &raquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>