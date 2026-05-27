<?php
/**
 * @var array $order
 * @var string $pageTitle
 * @var array $history
 * @var array $ratingInfo
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<style>
    .proof-image-wrapper { margin-top: 10px; }
    .proof-image { max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid var(--border-color); object-fit: cover; }
</style>

<div class="admin-container">
    
    <div class="order-view-header">
        <div class="order-view-title">
            <a href="/admin/orders" class="btn-back" title="Quay lại danh sách">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            Chi tiết đơn hàng #<?= htmlspecialchars($order['tracking_code']) ?>
        </div>
        <a href="/admin/orders/edit/<?= $order['id'] ?>" class="btn-edit-primary">
            <span class="material-symbols-outlined" style="font-size: 18px;">edit</span> Chỉnh sửa
        </a>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <?php if ($order['status'] === 'disputed' || ($order['has_dispute'] ?? false)): ?>
        <div class="alert-banner danger" style="margin-bottom: 24px; display: flex; gap: 12px; padding: 16px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 4px; color: #b91c1c;">
            <span class="material-symbols-outlined" style="font-size: 24px;">warning</span>
            <div>
                <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700;">Đơn hàng đang bị khiếu nại</h4>
                <p style="margin: 0; font-size: 13px;">Khách hàng báo cáo sự cố. Vui lòng chuyển sang mục <a href="/admin/disputes" style="font-weight: bold; text-decoration: underline; color: #991b1b;">Quản lý Khiếu nại</a> để xem hình ảnh đính kèm và xử lý.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="view-grid top-row">
        <div class="info-card">
            <div class="data-label" style="margin-bottom: 8px;">Mã vận đơn</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div class="data-value-large">#<?= htmlspecialchars($order['tracking_code']) ?></div>
                
                <div style="display: flex; gap: 8px;">
                    <?php if (($order['payment_status'] ?? '') === 'refunded'): ?>
                        <span class="card-badge status-warning">ĐÃ HOÀN TIỀN</span>
                    <?php endif; ?>
                    
                    <?php 
                        $statusColors = [
                            'awaiting_payment' => 'status-pending', 'pending' => 'status-pending', 'searching_driver' => 'status-warning',
                            'in_transit' => 'status-shipping', 'completed' => 'status-completed',
                            'cancelled' => 'status-cancelled', 'disputed' => 'status-cancelled'
                        ];
                        $badgeClass = $statusColors[$order['status']] ?? 'status-pending';
                    ?>
                    <span class="card-badge <?= $badgeClass ?>"><?= app_e(app_status_label($order['status'])) ?></span>
                </div>
            </div>

            <div style="display: flex; gap: 40px;">
                <div class="data-group">
                    <span class="data-label">Ngày Hẹn Lấy Hàng</span>
                    <span class="data-value"><?= date('d/m/Y, H:i', strtotime($order['scheduled_at'])) ?></span>
                </div>
                <div class="data-group">
                    <span class="data-label">Dịch vụ</span>
                    <span class="data-value"><?= htmlspecialchars(\App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard')) ?></span>
                </div>
            </div>

            <div class="note-box">
                <span class="material-symbols-outlined">format_align_left</span>
                <div>
                    <span class="data-label" style="display: block; margin-bottom: 2px;">Ghi chú đơn hàng</span>
                    <?= empty($order['note']) ? '<em>Không có ghi chú</em>' : nl2br(htmlspecialchars($order['note'])) ?>
                </div>
            </div>
        </div>

        <div class="info-card">
            <div class="info-card-header">
                <div class="title-left"><span class="material-symbols-outlined">payments</span> Chi phí & Thanh toán</div>
            </div>
            
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                <div class="payment-row">
                    <span>Phí vận chuyển</span>
                    <strong><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></strong>
                </div>
                <div class="payment-row">
                    <span>Phương thức TT</span>
                    <span style="display: flex; align-items: center; gap: 6px; color: var(--text-main); font-weight: 500;">
                        <?php 
                            if (($order['payment_method'] ?? '') === 'transfer') echo 'Chuyển khoản <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">account_balance</span>';
                            elseif (($order['payment_method'] ?? '') === 'wallet') echo 'Ví điện tử <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">account_balance_wallet</span>';
                            else echo 'Tiền mặt (COD) <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">payments</span>';
                        ?>
                    </span>
                </div>
                <div class="payment-row">
                    <span>Trạng thái TT</span>
                    <?php 
                        $pStatus = $order['payment_status'] ?? 'pending';
                        if ($pStatus === 'paid') echo '<span class="card-badge status-completed">ĐÃ THANH TOÁN</span>';
                        elseif ($pStatus === 'refunded') echo '<span class="card-badge status-warning">ĐÃ HOÀN TIỀN</span>';
                        else echo '<span class="card-badge status-pending" style="background:#f1f5f9; color:#64748b;">CHƯA THANH TOÁN</span>';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="view-grid half-row">
        <div class="info-card">
            <div class="info-card-header">
                <div class="title-left"><span class="material-symbols-outlined">person</span> Người đặt hàng</div>
                <?php if (!empty($order['customer_id'])): ?>
                    <a href="/profile/<?= htmlspecialchars($order['customer_id']) ?>" style="color: var(--text-muted);" title="Xem hồ sơ"><span class="material-symbols-outlined" style="font-size: 18px;">open_in_new</span></a>
                <?php endif; ?>
            </div>
            <<?= !empty($order['customer_id']) ? 'a href="/profile/'.htmlspecialchars($order['customer_id']).'"' : 'div' ?> class="user-profile-flex">
                <?php
                    $cAvatarUrl = app_avatar_url($order['customer_avatar'] ?? '', $order['customer_name'] ?? 'U');
                    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    if (empty($cAvatarUrl) || strpos($cAvatarUrl, 'default-avatar.png') !== false) {
                        $cAvatarUrl = $basePath . '/assets/images/default-avatar.png';
                    }
                ?>
                <img src="<?= htmlspecialchars($cAvatarUrl) ?>" alt="Customer">
                <div class="user-info-col">
                    <span class="user-info-name"><?= htmlspecialchars($order['customer_name'] ?? 'Không xác định') ?></span>
                    <span class="user-info-sub"><span class="material-symbols-outlined">call</span> <?= htmlspecialchars($order['customer_phone'] ?? 'Chưa cập nhật') ?></span>
                    <span class="user-info-sub"><span class="material-symbols-outlined">mail</span> <?= htmlspecialchars($order['customer_email'] ?? 'Chưa cập nhật') ?></span>
                    <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                        <span class="user-info-sub" style="color: var(--danger); font-weight: 600;">
                            <span class="material-symbols-outlined" style="color: var(--danger);">warning</span>
                            Vi phạm giao nhận: <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                        </span>
                    <?php endif; ?>
                </div>
            </<?= !empty($order['customer_id']) ? 'a' : 'div' ?>>
        </div>

        <div class="info-card">
            <div class="info-card-header">
                <div class="title-left"><span class="material-symbols-outlined">two_wheeler</span> Tài xế nhận đơn</div>
                <?php if (!empty($order['driver_id'])): ?>
                    <a href="/profile/<?= htmlspecialchars($order['driver_id']) ?>" style="color: var(--text-muted);" title="Xem hồ sơ"><span class="material-symbols-outlined" style="font-size: 18px;">open_in_new</span></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($order['driver_name'])): ?>
            <<?= !empty($order['driver_id']) ? 'a href="/profile/'.htmlspecialchars($order['driver_id']).'"' : 'div' ?> class="user-profile-flex">
                <?php
                    $dAvatarUrl = app_avatar_url($order['driver_avatar'] ?? '', $order['driver_name'] ?? 'D');
                    if (empty($dAvatarUrl) || strpos($dAvatarUrl, 'default-avatar.png') !== false) {
                        $dAvatarUrl = $basePath . '/assets/images/default-avatar.png';
                    }
                ?>
                <img src="<?= htmlspecialchars($dAvatarUrl) ?>" alt="Driver">
                <div class="user-info-col">
                    <span class="user-info-name"><?= htmlspecialchars($order['driver_name']) ?></span>
                    <span class="user-info-sub"><span class="material-symbols-outlined">call</span> <?= htmlspecialchars($order['driver_phone'] ?? 'Chưa cập nhật') ?></span>
                    <span class="user-info-sub" style="color: #f59e0b;"><span class="material-symbols-outlined" style="color: inherit;">star</span> <?= app_e($ratingInfo['avg'] ?? '0.0') ?> (<?= app_e($ratingInfo['total'] ?? '0') ?> đánh giá) &bull; <span style="color: var(--text-muted);">Biển số: <?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?></span></span>
                </div>
            </<?= !empty($order['driver_id']) ? 'a' : 'div' ?>>
            
            <?php if (!empty($order['driver_id']) && in_array($order['status'], ['cancelled', 'returning', 'returned'])): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color);">
                    <form action="/admin/orders/penalize-driver/<?= $order['id'] ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn phạt tài xế này? Nếu số dư âm, tài khoản sẽ bị khóa tự động.');" style="margin: 0;">
                        <div style="margin-bottom: 8px; font-weight: 600; color: var(--danger); display: flex; align-items: center; gap: 5px; font-size: 14px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">gavel</span> Phạt tài xế vi phạm
                        </div>
                        <div style="display: flex; gap: 8px; align-items: stretch;">
                            <input type="number" name="penalty_amount" class="form-control" placeholder="Số tiền..." value="50000" required style="width: 100px; padding: 6px; font-size: 13px;">
                            <input type="text" name="reason" class="form-control" placeholder="Lý do phạt..." value="Báo cáo sai sự thật" required style="flex: 1; padding: 6px; font-size: 13px;">
                            <button type="submit" class="btn-submit-primary" style="background: var(--danger); border: none; padding: 0 12px; font-size: 13px;">Phạt</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            <?php else: ?>
                <div style="display:flex; align-items:center; justify-content:center; flex:1; color: var(--text-muted); font-style: italic;">
                    Chưa có tài xế nhận đơn
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="view-grid half-row">
        <div class="info-card">
            <div class="info-card-header">
                <div class="title-left"><span class="material-symbols-outlined">location_on</span> Chi tiết địa chỉ</div>
            </div>
            
            <div class="address-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon pickup"></div>
                    <div class="timeline-title">Điểm lấy hàng</div>
                    <div class="timeline-address"><?= htmlspecialchars($order['sender_address'] ?? 'Chưa cập nhật') ?></div>
                    <div class="timeline-contact">
                        <span class="material-symbols-outlined" style="font-size: 14px;">person</span> 
                        <?= htmlspecialchars($order['sender_name'] ?? '') ?> - <?= htmlspecialchars($order['sender_phone'] ?? '') ?>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon dropoff"></div>
                    <div class="timeline-title">Điểm giao hàng</div>
                    <div class="timeline-address"><?= htmlspecialchars($order['receiver_address'] ?? 'Chưa cập nhật') ?></div>
                    <div class="timeline-contact">
                        <span class="material-symbols-outlined" style="font-size: 14px;">person</span> 
                        <?= htmlspecialchars($order['receiver_name'] ?? '') ?> - <?= htmlspecialchars($order['receiver_phone'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-card" style="padding: 0; border: 1px solid var(--border-color); overflow: hidden; border-radius: 4px;">
            <div id="order-map" style="min-height: 300px; height: 100%; width: 100%; z-index: 1;"></div>
        </div>
    </div>

    <div class="info-card" style="margin-bottom: 30px;">
        <div class="info-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
            <div class="title-left"><span class="material-symbols-outlined">history</span> Lịch sử trạng thái</div>
        </div>
        
        <?php if (empty($history)): ?>
            <p style="color: var(--text-muted); font-style: italic; margin: 0;">Không có dữ liệu lịch sử cho đơn hàng này.</p>
        <?php else: ?>
            <ul class="history-list">
                <?php foreach ($history as $h): ?>
                    <li class="history-item">
                        <div class="history-time">
                            <?= date('d/m/Y H:i:s', strtotime($h['created_at'])) ?>
                        </div>
                        <div class="history-content">
                            <strong style="color: <?= \App\Models\Order::getStatusColor($h['status']) ?? '#0f172a' ?>;">
                                <?= app_e(app_status_label($h['status'])) ?>
                            </strong>
                            <?php if (!empty($h['description'])): ?>
                            <div class="history-desc"><?= $h['description'] ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // Truyền dữ liệu map từ PHP
    window.OrderMapConfig = {
        status: <?= json_encode(htmlspecialchars($order['status'] ?? '')) ?>,
        senderLat: <?= (float)($order['sender_lat'] ?? 0) ?>,
        senderLng: <?= (float)($order['sender_lng'] ?? 0) ?>,
        receiverLat: <?= (float)($order['receiver_lat'] ?? 0) ?>,
        receiverLng: <?= (float)($order['receiver_lng'] ?? 0) ?>,
        driverLat: <?= (float)($order['driver_lat'] ?? 0) ?>,
        driverLng: <?= (float)($order['driver_lng'] ?? 0) ?>,
        senderAddress: <?= json_encode(htmlspecialchars($order['sender_address'] ?? '')) ?>,
        receiverAddress: <?= json_encode(htmlspecialchars($order['receiver_address'] ?? '')) ?>,
        trackingCode: <?= json_encode(htmlspecialchars($order['tracking_code'] ?? '')) ?>
    };
</script>
<script src="/assets/js/map-helper.js"></script>
<script src="/assets/js/admin-order-map.js"></script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
