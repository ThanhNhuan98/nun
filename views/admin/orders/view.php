<?php
/**
 * @var array $order
 * @var string $pageTitle
 * @var array $history
 * @var array $ratingInfo
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="order-view-header">
        <div class="order-view-title">
            <a href="/admin/orders" class="btn-back" title="Quay lại danh sách">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            Chi tiết đơn hàng #<?= htmlspecialchars($order['tracking_code']) ?>
        </div>
        <button type="button" class="btn-edit-primary" onclick="openEditModal()">
        <span class="material-symbols-outlined icon-18">edit</span> Chỉnh sửa
        </button>
    </div>

    <?php if ($order['status'] === 'disputed' || ($order['has_dispute'] ?? false)): ?>
        <div class="alert-banner danger">
        <span class="material-symbols-outlined icon-24">warning</span>
            <div class="alert-banner-content">
                <h4>Đơn hàng đang bị khiếu nại</h4>
            <p>Khách hàng báo cáo sự cố. Vui lòng chuyển sang mục <a href="/admin/disputes" class="disputed-link">Quản lý Khiếu nại</a> để xem hình ảnh đính kèm và xử lý.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="view-grid top-row">
        <div class="info-card">
        <div class="data-label mb-8">Mã vận đơn</div>
            <div class="flex-between-start">
                <div class="data-value-large">#<?= htmlspecialchars($order['tracking_code']) ?></div>
                
                <div class="flex-gap-8">
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

            <div class="flex-gap-40">
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
                    <?= empty($order['note']) ? '<em class="text-italic-muted">Không có ghi chú</em>' : nl2br(htmlspecialchars($order['note'])) ?>
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
                    <span class="payment-method-row">
                        <?php 
                            if (($order['payment_method'] ?? '') === 'transfer') echo 'Chuyển khoản <span class="material-symbols-outlined icon-muted-18">account_balance</span>';
                            elseif (($order['payment_method'] ?? '') === 'wallet') echo 'Ví điện tử <span class="material-symbols-outlined icon-muted-18">account_balance_wallet</span>';
                            else echo 'Tiền mặt (COD) <span class="material-symbols-outlined icon-muted-18">payments</span>';
                        ?>
                    </span>
                </div>
                <div class="payment-row">
                    <span>Trạng thái TT</span>
                    <?php 
                        $pStatus = $order['payment_status'] ?? 'pending';
                        if ($pStatus === 'paid') echo '<span class="card-badge status-completed">ĐÃ THANH TOÁN</span>';
                        elseif ($pStatus === 'refunded') echo '<span class="card-badge status-warning">ĐÃ HOÀN TIỀN</span>';
                        else echo '<span class="card-badge status-pending badge-unpaid">CHƯA THANH TOÁN</span>';
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
                <a href="/profile/<?= htmlspecialchars($order['customer_id']) ?>" class="btn-icon-link" title="Xem hồ sơ"><span class="material-symbols-outlined icon-18">open_in_new</span></a>
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
                        <span class="user-info-sub user-violation">
                            <span class="material-symbols-outlined icon-danger">warning</span>
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
                <a href="/profile/<?= htmlspecialchars($order['driver_id']) ?>" class="btn-icon-link" title="Xem hồ sơ"><span class="material-symbols-outlined icon-18">open_in_new</span></a>
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
                <div class="penalty-box">
                    <form action="/admin/orders/penalize-driver/<?= $order['id'] ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn phạt tài xế này? Nếu số dư âm, tài khoản sẽ bị khóa tự động.');" class="m-0">
                        <div class="penalty-header">
                            <span class="material-symbols-outlined" style="font-size: 18px;">gavel</span> Phạt tài xế vi phạm
                        </div>
                        <div class="penalty-form-row">
                            <input type="number" name="penalty_amount" class="m-form-control penalty-input-sm" placeholder="Số tiền..." value="50000" required data-error="Vui lòng nhập số tiền.">
                            <input type="text" name="reason" class="m-form-control penalty-input-lg" placeholder="Lý do phạt..." value="Báo cáo sai sự thật" required data-error="Vui lòng nhập lý do.">
                            <button type="submit" class="btn-penalty">Phạt</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            <?php else: ?>
                <div class="flex-center-center text-italic-muted">
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
                    <span class="material-symbols-outlined icon-14">person</span> 
                        <?= htmlspecialchars($order['sender_name'] ?? '') ?> - <?= htmlspecialchars($order['sender_phone'] ?? '') ?>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon dropoff"></div>
                    <div class="timeline-title">Điểm giao hàng</div>
                    <div class="timeline-address"><?= htmlspecialchars($order['receiver_address'] ?? 'Chưa cập nhật') ?></div>
                    <div class="timeline-contact">
                    <span class="material-symbols-outlined icon-14">person</span> 
                        <?= htmlspecialchars($order['receiver_name'] ?? '') ?> - <?= htmlspecialchars($order['receiver_phone'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>

    <div class="info-card info-card-map">
        <div id="order-map" class="order-map-container"></div>
        </div>
    </div>

<div class="info-card info-card-history">
    <div class="info-card-header history-card-header">
            <div class="title-left"><span class="material-symbols-outlined">history</span> Lịch sử trạng thái</div>
        </div>
        
        <?php if (empty($history)): ?>
            <p class="text-italic-muted m-0">Không có dữ liệu lịch sử cho đơn hàng này.</p>
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

    <div id="editModal" class="modal-backdrop">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Chỉnh sửa đơn hàng #<?= htmlspecialchars($order['tracking_code']) ?></h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form action="/admin/orders/view/<?= $order['id'] ?>" method="POST" id="form-edit-order">
                <div class="modal-body">
                    <div class="m-form-group">
                        <label>Trạng thái đơn hàng</label>
                        <select name="status" class="m-form-control">
                            <?php foreach (\App\Models\Order::STATUS_LABELS as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="m-form-group">
                        <label>Phí vận chuyển (VNĐ)</label>
                        <input type="number" name="shipping_fee" class="m-form-control" value="<?= (int)($order['shipping_fee'] ?? 0) ?>">
                    </div>

                    <div class="m-form-group">
                        <label>Phương thức thanh toán</label>
                        <select name="payment_method" class="m-form-control">
                            <option value="cash" <?= ($order['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Tiền mặt (COD)</option>
                            <option value="transfer" <?= ($order['payment_method'] ?? '') === 'transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                            <option value="wallet" <?= ($order['payment_method'] ?? '') === 'wallet' ? 'selected' : '' ?>>Ví điện tử</option>
                        </select>
                    </div>

                    <div class="m-form-group">
                        <label>Trạng thái thanh toán</label>
                        <select name="payment_status" class="m-form-control">
                            <option value="pending" <?= ($order['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                            <option value="unpaid" <?= ($order['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                            <option value="paid" <?= ($order['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                            <option value="refunded" <?= ($order['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                        </select>
                    </div>

            <div class="m-form-group m-form-group-last">
                        <label>Ghi chú</label>
                        <textarea name="note" rows="3" class="m-form-control"><?= htmlspecialchars($order['note'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeEditModal()">Hủy</button>
                    <button type="submit" class="btn-save">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // Xử lý Popup Modal
    function openEditModal() {
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Khóa cuộn trang nền
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = ''; // Mở lại cuộn trang
    }

    // Đóng Modal khi click ra ngoài Overlay
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

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
