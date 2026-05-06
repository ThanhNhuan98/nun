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

    <div class="user-filter-bar">
        <div class="filter-pills-group">
            <a href="/user/orders" class="filter-pill-btn <?= empty($statusFilter) ? 'active' : '' ?>">Tất cả</a>
            <a href="/user/orders?status=picking_up" class="filter-pill-btn <?= ($statusFilter === 'picking_up') ? 'active' : '' ?>">Đang lấy hàng</a>
            <a href="/user/orders?status=in_transit" class="filter-pill-btn <?= ($statusFilter === 'in_transit' || $statusFilter === 'shipping') ? 'active' : '' ?>">Đang giao</a>
            <a href="/user/orders?status=completed" class="filter-pill-btn <?= ($statusFilter === 'completed') ? 'active' : '' ?>">Đã giao</a>
            <a href="/user/orders?status=cancelled" class="filter-pill-btn <?= ($statusFilter === 'cancelled') ? 'active' : '' ?>">Đã hủy</a>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <select class="form-control" style="width: 140px; padding: 8px 12px; height: 38px;">
                <option>Tháng này</option>
                <option>Tháng trước</option>
            </select>
            <button type="button" class="btn-cancel" style="height: 38px; padding: 0 16px; margin: 0; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 18px;">filter_list</span> Lọc
            </button>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid var(--border-color); border-radius: 4px;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;">inbox</span>
            <p style="color: var(--text-muted); font-size: 15px; margin: 0;">Bạn chưa có đơn hàng nào.</p>
        </div>
    <?php else: ?>
        <div class="user-table-card">
            <div style="overflow-x: auto;">
                <table class="user-table-v2">
                    <thead>
                        <tr>
                            <th>Mã vận đơn</th>
                            <th>Ngày Hẹn Lấy Hàng</th>
                            <th>Địa chỉ nhận</th>
                            <th>Cước phí</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="td-tracking">NUN-<?= app_e(str_replace('NUN', '', $order['tracking_code'])) ?></td>
                                
                                <td class="td-date">
                                    <?= date('d/m/Y', strtotime($order['scheduled_at'])) ?>
                                    <span><?= date('H:i', strtotime($order['scheduled_at'])) ?></span>
                                </td>
                                
                                <td>
                                    <div class="td-address" title="<?= app_e($order['receiver_address'] ?? '') ?>">
                                        <?= app_e($order['receiver_address'] ?? 'Chưa cập nhật') ?>
                                    </div>
                                </td>
                                
                                <td class="td-fee">
                                    <?= app_money($order['shipping_fee'] ?? 0, '') ?>
                                    <span>đ</span>
                                </td>
                                
                                <td>
                                    <?php 
                                        $s = $order['status'];
                                        $pillClass = 'pending';
                                        if (in_array($s, ['searching_driver', 'accepted', 'picking_up'])) $pillClass = 'warning';
                                        elseif (in_array($s, ['in_transit', 'shipping'])) $pillClass = 'shipping';
                                        elseif ($s === 'completed') $pillClass = 'success';
                                        elseif ($s === 'cancelled') $pillClass = 'danger';
                                    ?>
                                    <span class="pill-status <?= $pillClass ?>">
                                        <?= app_e(app_status_label($s)) ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="action-link-blue">
                                        <?php echo in_array($order['status'], ['completed', 'cancelled']) ? 'Chi tiết' : 'Theo dõi'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($totalPages ?? 1) > 1): ?>
                <div class="table-footer">
                    <div class="table-footer-text">
                        Trang <strong><?= $currentPage ?></strong> / <strong><?= $totalPages ?></strong>
                    </div>
                    
                    <div class="pagination-container" style="margin: 0;">
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
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>