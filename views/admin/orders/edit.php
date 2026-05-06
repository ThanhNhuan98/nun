<?php
/**
 * @var array $order
 * @var string $pageTitle
 */
require_once __DIR__ . '/../../layouts/user_header.php'; 
?>

<div class="admin-container">
    <div class="order-view-header">
        <div class="order-view-title">
            <a href="/admin/orders/view/<?= $order['id'] ?>" class="btn-back" title="Quay lại">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            Chỉnh sửa đơn hàng #<?= htmlspecialchars($order['tracking_code']) ?>
        </div>
    </div>

    <?php if (isset($order['error'])): ?>
        <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <?= htmlspecialchars($order['error']) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/orders/edit/<?= $order['id'] ?>" method="POST" class="info-card">
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Trạng thái đơn hàng</label>
            <select name="status" class="form-control" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                <?php foreach (\App\Models\Order::STATUS_LABELS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Phí vận chuyển (VNĐ)</label>
            <input type="number" name="shipping_fee" class="form-control" value="<?= (int)($order['shipping_fee'] ?? 0) ?>" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Phương thức thanh toán</label>
            <select name="payment_method" class="form-control" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                <option value="cash" <?= ($order['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Tiền mặt (COD)</option>
                <option value="transfer" <?= ($order['payment_method'] ?? '') === 'transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                <option value="wallet" <?= ($order['payment_method'] ?? '') === 'wallet' ? 'selected' : '' ?>>Ví điện tử</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Trạng thái thanh toán</label>
            <select name="payment_status" class="form-control" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;">
                <option value="pending" <?= ($order['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                <option value="unpaid" <?= ($order['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                <option value="paid" <?= ($order['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                <option value="refunded" <?= ($order['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Ghi chú</label>
            <textarea name="note" rows="4" class="form-control" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px;"><?= htmlspecialchars($order['note'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-submit-primary" style="background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">Lưu thay đổi</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>