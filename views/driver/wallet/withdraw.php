<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    <div class="topup-wrapper">
        <a href="/driver/receive-orders" class="topup-header-nav">
            <span class="material-symbols-outlined" style="font-size: 20px;">arrow_back</span>
            Quay lại Radar
        </a>

        <!-- HIỂN THỊ SỐ DƯ CÓ THỂ RÚT -->
        <div style="background: linear-gradient(135deg, var(--warning), #d97706); color: white; padding: 24px; border-radius: 8px; margin-bottom: 24px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Số dư khả dụng để rút</div>
            <div style="font-size: 32px; font-weight: 700; letter-spacing: 0.5px;"><?= app_money($balance ?? 0, ' đ') ?></div>
        </div>

        <h2 class="topup-title">Rút tiền</h2>
        <p class="topup-desc">Hệ thống sẽ mô phỏng trừ tiền trong ví và báo thành công ngay lập tức để phục vụ kiểm thử luồng đối soát.</p>

        <form method="POST" action="/driver/wallet/withdraw">
            <div class="topup-form-group">
                <label for="amount">Số tiền cần rút (VNĐ)</label>
                <input type="number" id="amount" name="amount" class="topup-input" min="50000" step="1000" placeholder="Nhập số tiền tối thiểu 50.000đ" required>
                <div class="topup-helper">
                    <span class="material-symbols-outlined">info</span>
                    Bạn phải có đủ số dư mới thực hiện được thao tác này.
                </div>
            </div>
            
            <div class="topup-form-group">
                <label for="bank_info">Ngân hàng thụ hưởng (Ghi chú)</label>
                <input type="text" id="bank_info" name="bank_info" class="topup-input" placeholder="VD: VCB - 123456789 - NGUYEN VAN A">
            </div>

            <div class="topup-actions">
                <a href="/driver/receive-orders" class="btn-topup-cancel">Hủy bỏ</a>
                <button type="submit" class="btn-topup-submit" style="background: var(--warning); color: #fff; box-shadow: 0 4px 10px rgba(217,119,6,0.2);">Xác nhận Rút tiền</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
