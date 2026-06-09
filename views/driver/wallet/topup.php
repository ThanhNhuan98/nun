<?php
$minTopupAmount = (int) ($minTopup ?? 10000);
$paymentSettings = $paymentSettings ?? [];
$bankId = $paymentSettings['bank_id'] ?? 'VCB';
$bankName = $paymentSettings['bank_name'] ?? 'Vietcombank';
$accountNo = $paymentSettings['bank_account_no'] ?? '1234567890';
$accountName = $paymentSettings['bank_account_name'] ?? 'CONG TY TNHH NUN EXPRESS';
require_once __DIR__ . '/../../layouts/user_header.php';
?>

<div class="admin-container">
    
    <!-- BƯỚC 1: NHẬP SỐ TIỀN -->
    <div id="step-1" class="topup-wrapper">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            
            <a href="/driver/wallet/withdraw" class="btn-withdraw-outline">
                <span class="material-symbols-outlined" style="font-size: 18px;">payments</span> Rút tiền
            </a>
        </div>

        <!-- HIỂN THỊ SỐ DƯ HIỆN TẠI -->
        <div style="background: linear-gradient(135deg, var(--primary), #1e40af); color: white; padding: 24px; border-radius: 8px; margin-bottom: 24px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Số dư ví hiện tại</div>
            <div style="font-size: 32px; font-weight: 700; letter-spacing: 0.5px;"><?= app_money($currentBalance ?? 0, ' đ') ?></div>
        </div>

        <h2 class="topup-title">Nạp tiền vào ví</h2>
        <p class="topup-desc">Nạp tiền để duy trì số dư tối thiểu, giúp bạn nhận các chuyến đi mới từ NUN AI.</p>

        <div class="topup-form-group">
            <label for="amount">Số tiền cần nạp (VNĐ)</label>
            <div style="display: flex; gap: 10px;">
                <input type="number" id="amount" class="topup-input" min="<?= app_e($minTopupAmount) ?>" step="1000" placeholder="Ví dụ: 50000" style="margin-bottom: 0;">
                <button type="button" class="btn-topup-submit" onclick="generateQR()" style="width: auto; padding: 0 20px; margin: 0; white-space: nowrap;">Tạo mã QR</button>
            </div>
            
            <div class="topup-helper">
                <span class="material-symbols-outlined">info</span>
                Nạp tối thiểu <?= number_format($minTopupAmount, 0, ',', '.') ?>đ để tiếp tục nhận đơn.
            </div>
        </div>

        <div class="quick-amount-grid">
            <div class="btn-quick-amount" onclick="setAmount(50000)">50.000đ</div>
            <div class="btn-quick-amount" onclick="setAmount(100000)">100.000đ</div>
            <div class="btn-quick-amount" onclick="setAmount(200000)">200.000đ</div>
        </div>

    </div>

    <!-- BƯỚC 2: THANH TOÁN QR CODE -->
    <div id="step-2" class="payment-container" style="display: none; margin-top: 0; padding-top: 24px; border-top: 1px dashed var(--border-color);">

        <div class="payment-card">
            <div class="payment-qr-col">
                <div class="qr-title">Quét mã để thanh toán</div>
                <div class="qr-image-wrapper">
                    <img id="qr-image" src="" alt="Mã QR Nạp tiền">
                </div>
                <div class="qr-helper-text">
                    <span class="material-symbols-outlined" style="font-size: 18px;">qr_code_scanner</span>
                    Hỗ trợ VNPay, Momo, ZaloPay
                </div>
            </div>

            <div class="payment-info-col">
                <div>
                    <div class="amount-label">Số tiền nạp</div>
                    <div class="amount-value" id="display-amount">0 đ</div>
                </div>

                <div class="transfer-title">Thông tin chuyển khoản</div>
                
                <div class="transfer-detail-row">
                    <span class="t-label">Ngân hàng:</span>
                    <span class="t-value"><strong><?= app_e($bankName) ?></strong></span>
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
                        <strong id="copy-content"></strong>
                        <button type="button" class="btn-copy" onclick="copyToClipboard('copy-content')" title="Copy nội dung">
                            <span class="material-symbols-outlined" style="font-size: 18px;">content_copy</span>
                        </button>
                    </span>
                </div>

                <form method="POST" action="/driver/wallet/topup" style="margin: 0;">
                    <input type="hidden" name="amount" id="form-amount" value="">
                    <button type="submit" class="btn-paid-submit">
                        <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                        Tôi đã thanh toán
                    </button>
                </form>
                
                <small style="display: block; text-align: center; color: var(--text-muted); margin-top: 12px; font-size: 12px;">
                    * Hệ thống tự động đối soát và cộng tiền vào ví trong 1-3 phút.
                </small>
            </div>
        </div>
    </div>

</div>

<script>
    function setAmount(value) {
        document.getElementById('amount').value = value;
        generateQR();
    }

    function generateQR() {
        const amountInput = document.getElementById('amount').value;
        const amount = parseInt(amountInput);
        const formatter = new Intl.NumberFormat('vi-VN');
        const minTopupAmount = <?= (int) $minTopupAmount ?>;

        if (!amount || amount < minTopupAmount) {
            const message = 'Vui lòng nhập số tiền hợp lệ (tối thiểu ' + formatter.format(minTopupAmount) + 'đ).';
            if (typeof showToast === 'function') {
                showToast(message, 'error');
            } else {
                alert(message);
            }
            return;
        }

        document.getElementById('display-amount').innerText = formatter.format(amount) + ' đ';
        document.getElementById('form-amount').value = amount;

        const bankId = <?= json_encode($bankId, JSON_UNESCAPED_UNICODE) ?>;
        const accountNo = <?= json_encode($accountNo, JSON_UNESCAPED_UNICODE) ?>;
        const accountName = <?= json_encode($accountName, JSON_UNESCAPED_UNICODE) ?>;
        const driverId = <?= json_encode((string) ($userId ?? 0), JSON_UNESCAPED_UNICODE) ?>;
        const description = "NUN NAP VI " + driverId;

        document.getElementById('copy-content').innerText = description;

        const qrUrl = `https://img.vietqr.io/image/${bankId}-${accountNo}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(description)}&accountName=${encodeURIComponent(accountName)}`;
        document.getElementById('qr-image').src = qrUrl;
        document.getElementById('step-2').style.display = 'block';
        
        setTimeout(() => {
            document.getElementById('step-2').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }

    function copyToClipboard(elementId) {
        const textToCopy = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const btn = document.querySelector(`#${elementId}`).nextElementSibling;
            const icon = btn.querySelector('.material-symbols-outlined');
            const originalIcon = icon.innerText;
            
            icon.innerText = 'check';
            
            setTimeout(() => {
                icon.innerText = originalIcon;
            }, 2000);
            
            if (typeof showToast === 'function') {
                showToast('Đã sao chép thành công!', 'success');
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
