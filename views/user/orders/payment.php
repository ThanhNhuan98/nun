<?php
/**
 * @var array $order
 * @var string $pageTitle
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    <div class="payment-container">
        <div class="payment-card">
            
            <?php 
                // Cấu hình VietQR theo chuẩn Mockup thiết kế
                $bankId = "VCB"; // Vietcombank
                $accountNo = "1234567890";
                $accountName = "CONG TY TNHH NUN EXPRESS"; 
                $amount = (int)($order['shipping_fee'] ?? 0);
                $description = "NUN TT DH " . ($order['tracking_code'] ?? '');
                
                // API tạo QR Code
                $qrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-compact2.png?amount={$amount}&addInfo=" . urlencode($description) . "&accountName=" . urlencode($accountName);
            ?>

            <div class="payment-qr-col">
                <div class="qr-title">Quét mã để thanh toán</div>
                <div class="qr-image-wrapper">
                    <img src="<?= app_e($qrUrl) ?>" alt="Mã QR Thanh toán">
                </div>
                <div class="qr-helper-text">
                    <span class="material-symbols-outlined" style="font-size: 18px;">qr_code_scanner</span>
                    Hỗ trợ VNPay, Momo, ZaloPay
                </div>
            </div>

            <div class="payment-info-col">
                
                <div>
                    <div class="amount-label">Số tiền cần thanh toán</div>
                    <div class="amount-value"><?= app_money($amount, ' đ') ?></div>
                </div>

                <div class="transfer-title">Thông tin chuyển khoản</div>
                
                <div class="transfer-detail-row">
                    <span class="t-label">Ngân hàng:</span>
                    <span class="t-value"><strong>Vietcombank</strong></span>
                </div>
                
                <div class="transfer-detail-row">
                    <span class="t-label">Chủ tài khoản:</span>
                    <span class="t-value" style="text-transform: uppercase;"><strong><?= app_e($accountName) ?></strong></span>
                </div>
                
                <div class="transfer-detail-row">
                    <span class="t-label">Số tài khoản:</span>
                    <span class="t-value">
                        <strong id="copy-account"><?= app_e($accountNo) ?></strong>
                        <button type="button" class="btn-copy" onclick="copyToClipboard('copy-account')" title="Copy số tài khoản">
                            <span class="material-symbols-outlined" style="font-size: 18px;">content_copy</span>
                        </button>
                    </span>
                </div>
                
                <div class="transfer-detail-row">
                    <span class="t-label">Nội dung:</span>
                    <span class="t-value">
                        <strong id="copy-content"><?= app_e($description) ?></strong>
                        <button type="button" class="btn-copy" onclick="copyToClipboard('copy-content')" title="Copy nội dung">
                            <span class="material-symbols-outlined" style="font-size: 18px;">content_copy</span>
                        </button>
                    </span>
                </div>

                <form method="POST" action="/user/orders/payment/<?= app_e($order['tracking_code']) ?>" style="margin: 0;">
                    <button type="submit" class="btn-paid-submit">
                        <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                        Tôi đã thanh toán
                    </button>
                </form>
                
                <a href="/user/orders" class="btn-pay-later">Thanh toán sau</a>
                
                <small style="display: block; text-align: center; color: var(--text-muted); margin-top: 12px; font-size: 12px;">
                    * Lưu ý: Nút "Tôi đã thanh toán" chỉ dành cho môi trường Test.
                </small>

            </div>
        </div>
    </div>
</div>

<script>
// Sao chép nội dung thanh toán vào khay nhớ tạm và hiển thị hiệu ứng thành công.
function copyToClipboard(elementId) {
    const textToCopy = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(textToCopy).then(() => {
        // Thay đổi icon tạm thời để báo hiệu đã copy
        const btn = document.querySelector(`#${elementId}`).nextElementSibling;
        const icon = btn.querySelector('.material-symbols-outlined');
        const originalIcon = icon.innerText;
        
        icon.innerText = 'check';
        icon.style.color = '#10b981'; // Màu xanh lá success
        
        setTimeout(() => {
            icon.innerText = originalIcon;
            icon.style.color = '';
        }, 2000);
    }).catch(err => {
        console.error('Lỗi khi copy:', err);
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
