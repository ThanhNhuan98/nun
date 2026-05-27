<?php
/**
 * @var array $order
 * @var string $pageTitle
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">

    <div class="driver-detail-layout">

        <div class="driver-info-col">

            <div class="detail-card-v2">
                <div class="tracking-label">Mã Vận Đơn</div>
                <div class="tracking-header">
                    <div class="tracking-code-large">#<?= app_e($order['tracking_code']) ?></div>
                    <span class="badge-status status-warning"><?= app_e(app_status_label($order['status'])) ?></span>
                </div>
            </div>

            <div class="detail-card-v2">
                <div class="timeline-v2">

                    <div class="timeline-node-v2">
                        <div class="node-icon-v2 pickup">
                            <span class="material-symbols-outlined">storefront</span>
                        </div>
                        <div class="node-title-v2">ĐIỂM LẤY HÀNG</div>
                        <div class="node-desc-v2">
                            <strong><?= app_e($order['sender_name']) ?></strong><br>
                                    <?= app_e($order['sender_address']) ?>
                        </div>
                        <a href="tel:<?= app_e($order['sender_phone']) ?>" class="node-phone-v2">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span> <?= app_e($order['sender_phone']) ?>
                        </a>
                    </div>

                    <div class="timeline-node-v2">
                        <div class="node-icon-v2 dropoff"></div>
                        <div class="node-title-v2">ĐIỂM GIAO HÀNG</div>
                        <div class="node-desc-v2">
                            <strong><?= app_e($order['receiver_name']) ?></strong><br>
                                    <?= app_e($order['receiver_address']) ?>
                        </div>
                        <a href="tel:<?= app_e($order['receiver_phone']) ?>" class="node-phone-v2">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span> <?= app_e($order['receiver_phone']) ?>
                        </a>
                        <?php if (($order['customer_no_show_count'] ?? 0) > 0): ?>
                            <div style="margin-top: 8px; color: var(--danger); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: var(--danger-light); padding: 4px 8px; border-radius: 4px; border: 1px solid #fca5a5;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">warning</span>
                                Khách Vi phạm giao nhận : <?= htmlspecialchars($order['customer_no_show_count']) ?> lần
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="detail-card-v2">
                <div class="trip-info-header">Thông tin chuyến đi</div>
                <div class="trip-fee-row" style="margin-bottom: 8px;">
                    <span>Dịch vụ:</span>
                    <strong style="color: <?= \App\Models\Order::getShippingMethodColor($order['shipping_method'] ?? 'standard') ?>;">
                        <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                    </strong>
                </div>
                <div class="trip-fee-row" style="margin-bottom: 8px;">
                    <span>Ngày hẹn lấy:</span>
                    <strong><?= !empty($order['scheduled_at']) ? date('H:i d/m/Y', strtotime($order['scheduled_at'])) : 'Càng sớm càng tốt' ?></strong>
                </div>
                <div class="trip-fee-row">
                    <span>Phí vận chuyển:</span>
                    <strong><?= app_money($order['shipping_fee'] ?? 0, ' đ') ?></strong>
                </div>

                <?php if (!empty($order['note'])): ?>
                    <div class="trip-note-box">
                        <strong style="display: block; font-size: 12px; margin-bottom: 4px;">Ghi chú từ khách hàng:</strong>
                        "<?= app_e($order['note']) ?>"
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 24px;">

                <?php if ($order['status'] === 'accepted'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                        <input type="hidden" name="status" value="picking_up">
                        <button type="submit" class="btn-driver-action orange">
                            Đã Đến Điểm Lấy Hàng
                        </button>
                    </form>

                <?php elseif ($order['status'] === 'picking_up'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" style="margin:0;">
                        <input type="hidden" name="status" value="in_transit">
                        <button type="submit" class="btn-driver-action blue">
                            Bắt Đầu Giao Hàng
                        </button>
                    </form>

                <?php elseif ($order['status'] === 'in_transit' || $order['status'] === 'shipping'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data">
                        <input type="hidden" name="status" value="completed">
                        
                        <div style="background: #fdfde6; padding: 12px; border-radius: 4px; border: 1px solid #fef08a; margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 700; color: #854d0e; display: block; margin-bottom: 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">dialpad</span> 
                                Mã PIN nhận hàng (Hỏi Người nhận):
                            </label>
                            <input type="text" name="delivery_pin" placeholder="Nhập 4 số mã PIN" pattern="\d{4}" maxlength="4" class="minimal-input" style="border-color: #fde047; font-size: 18px; letter-spacing: 4px; text-align: center; font-weight: bold; color: var(--primary); margin-bottom: 0;" required>
                        </div>

                        <div style="background: #f8fafc; padding: 12px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 8px;">Ảnh minh chứng giao hàng (Bắt buộc):</label>
                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#3b82f6; padding:12px; border-radius:4px; border:1px dashed #3b82f6; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span> Chụp ảnh
                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#10b981; padding:12px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">image</span> Chọn từ máy
                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                            <div class="active-image-preview" style="display: none; position: relative; text-align: center; margin-bottom: 12px;">
                                <img src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #cbd5e1;">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">close</span></button>
                            </div>
                        </div>
                        <button type="submit" class="btn-driver-action green">
                            Xác Nhận Đã Giao Xong
                        </button>
                    </form>
                <?php elseif ($order['status'] === 'returning'): ?>
                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data">
                        <input type="hidden" name="status" value="returned">
                        <div style="background: #f8fafc; padding: 12px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 8px;">Ảnh minh chứng hoàn hàng (Bắt buộc):</label>
                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#ea580c; padding:12px; border-radius:4px; border:1px dashed #ea580c; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span> Chụp ảnh
                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#10b981; padding:12px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">image</span> Chọn từ máy
                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                            <div class="active-image-preview" style="display: none; position: relative; text-align: center; margin-bottom: 12px;">
                                <img src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #cbd5e1;">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">close</span></button>
                            </div>
                        </div>
                        <button type="submit" class="btn-driver-action orange">
                            Xác Nhận Đã Hoàn Hàng
                        </button>
                    </form>

                    <form method="POST" action="/driver/orders/update-status/<?= $order['id'] ?>" enctype="multipart/form-data" style="margin-top: 15px;">
                        <input type="hidden" name="status" value="disputed">

                        <button type="button" class="btn-driver-action danger-outline" onclick="document.getElementById('dispute-return-box').style.display='block'; this.style.display='none';">
                            <span class="material-symbols-outlined" style="font-size: 18px;">warning</span> Khách Từ Chối Nhận / Sự Cố
                        </button>

                        <div id="dispute-return-box" style="display: none; background: #fef2f2; padding: 16px; border: 1px solid #fecaca; border-radius: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: var(--danger); display: block; margin-bottom: 8px;">Nhập lý do sự cố:</label>
                            <input type="text" name="cancel_reason" placeholder="VD: Khách không nghe máy, từ chối nhận..." class="minimal-input" style="border-color: #fca5a5;" required>

                            <label style="font-size: 12px; font-weight: 700; color: var(--danger); display: block; margin-bottom: 8px;">Ảnh minh chứng (Bắt buộc):</label>
                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 16px;">
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:var(--danger); padding:12px; border-radius:4px; border:1px dashed #fca5a5; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span> Chụp ảnh
                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#10b981; padding:12px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">image</span> Thư viện
                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                            <div class="active-image-preview" style="display: none; margin-bottom: 16px; position: relative; text-align: center;">
                                <img src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #fca5a5;">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">close</span></button>
                            </div>

                            <button type="submit" class="btn-driver-action" style="background: var(--danger); color: white; margin: 0;">
                                Báo Cáo Tranh Chấp
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['accepted', 'picking_up', 'in_transit', 'shipping'])): ?>
                    <!-- Form báo cáo Sự cố Lấy/Giao hàng chuyên biệt -->
                    <?php
                        $isPickingUp = in_array($order['status'], ['accepted', 'picking_up']);
                        $btnLabel = $isPickingUp ? 'Báo Cáo Lấy Thất Bại ' : 'Báo Cáo Giao Thất Bại ';
                        $submitLabel = $isPickingUp ? 'Xác Nhận Hủy Đơn' : 'Xác Nhận Chuyển Hoàn';
                    ?>
                    <form method="POST" action="/driver/orders/report-noshow/<?= $order['id'] ?>" enctype="multipart/form-data" style="margin-top: 15px;">
                        <button type="button" class="btn-driver-action" style="background: #be123c; color: white; border: none;" onclick="document.getElementById('noshow-form-box').style.display='block'; this.style.display='none';">
                            <span class="material-symbols-outlined" style="font-size: 18px;">person_off</span> <?= $btnLabel ?>
                        </button>

                        <div id="noshow-form-box" style="display: none; background: #fff1f2; padding: 16px; border: 1px solid #fda4af; border-radius: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: #be123c; display: block; margin-bottom: 8px;">Lý do sự cố (Bắt buộc):</label>
                            <select name="reason" class="minimal-input" style="border-color: #fda4af; margin-bottom: 12px;" required oninvalid="this.setCustomValidity('Vui lòng chọn lý do sự cố.')" onchange="this.setCustomValidity('')">
                                <option value="">-- Chọn lý do --</option>
                                <?php if ($isPickingUp): ?>
                                    <option value="Người gửi không nghe máy / Không liên lạc được">Người gửi không nghe máy / Không liên lạc được</option>
                                    <option value="Hàng hóa quá khổ / Sai mô tả">Hàng hóa quá khổ / Sai mô tả</option>
                                    <option value="Phát hiện Hàng cấm / Vi phạm pháp luật">🚨 Phát hiện Hàng cấm / Vi phạm pháp luật</option>
                                <?php else: ?>
                                    <option value="Người nhận không nghe máy / Không liên lạc được">Người nhận không nghe máy / Không liên lạc được</option>
                                    <option value="Người nhận từ chối nhận hàng">Người nhận từ chối nhận hàng</option>
                                    <option value="Sai địa chỉ giao / Không tìm thấy địa chỉ">Sai địa chỉ giao / Không tìm thấy địa chỉ</option>
                                <?php endif; ?>
                                <option value="Lý do khác">Lý do khác</option>
                            </select>

                            <label style="font-size: 12px; font-weight: 700; color: #be123c; display: block; margin-bottom: 8px;">Ảnh chụp cửa nhà / lịch sử cuộc gọi (Bắt buộc):</label>
                            <div class="proof-action-btns" style="display: flex; gap: 8px; margin-bottom: 16px;">
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#be123c; padding:12px; border-radius:4px; border:1px dashed #fda4af; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span> Chụp ảnh
                                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                                <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:#fff; color:#10b981; padding:12px; border-radius:4px; border:1px dashed #10b981; cursor:pointer; font-size:13px; font-weight:600; transition:0.2s;">
                                    <span class="material-symbols-outlined" style="font-size:18px;">image</span> Thư viện
                                    <input type="file" accept="image/*" style="display:none;" onchange="handleProofSync(this)">
                                </label>
                            </div>
                            <input type="file" name="proof_image" class="real-proof-input" accept="image/*" required style="opacity: 0; position: absolute; z-index: -1; width: 1px; height: 1px;">
                            <div class="active-image-preview" style="display: none; margin-bottom: 16px; position: relative; text-align: center;">
                                <img src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #fda4af;">
                                <button type="button" onclick="clearProofPreview(this)" style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">close</span></button>
                            </div>

                            <button type="submit" class="btn-driver-action" style="background: #be123c; color: white; margin: 0;">
                                <?= $submitLabel ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

        </div>

        <div class="driver-map-container">
            <div id="route-info" class="driver-route-info" style="display: none;">
                <div class="driver-route-info-main">
                    <div>
                        <div class="driver-nav-label">Đang điều hướng đến</div>
                        <div id="route-next-stop" class="driver-next-stop">Điểm dừng tiếp theo</div>
                        <div class="driver-route-meta">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: bottom; color: var(--primary);">route</span>
                            <strong id="route-distance" style="color: var(--primary);">0</strong> km &bull;
                            <strong id="route-time" style="color: var(--primary);">0</strong> phút
                        </div>
                    </div>
                    <a id="btn-ggmap-nav" class="driver-nav-open" href="#" target="_blank" rel="noopener">
                        <span class="material-symbols-outlined" style="font-size: 16px;">near_me</span> Google Maps
                    </a>
                </div>
            </div>
            <!-- Nút Định vị lại -->
            <button onclick="recenterMapDetail()" id="btn-recenter-detail" style="position: absolute; bottom: 30px; right: 10px; z-index: 1000; background: #fff; border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-main); box-shadow: 0 1px 5px rgba(0,0,0,0.65);" title="Định vị lại">
                <span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>
            </button>
            <div id="route-map"></div>
        </div>

    </div>
</div>

<script>
    // Ham handleProofSync: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    function handleProofSync(input) {
        if (input.files && input.files[0]) {
            const form = input.closest('form');
            const realInput = form.querySelector('.real-proof-input');
            const previewContainer = form.querySelector('.active-image-preview');
            const img = previewContainer.querySelector('img');
            const actionBtns = form.querySelector('.proof-action-btns');

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(input.files[0]);
            realInput.files = dataTransfer.files;

            img.src = URL.createObjectURL(input.files[0]);
            previewContainer.style.display = 'block';
            actionBtns.style.display = 'none';

            input.value = '';
        }
    }

    // Ham clearProofPreview: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    function clearProofPreview(btn) {
        const form = btn.closest('form');
        const realInput = form.querySelector('.real-proof-input');
        const previewContainer = form.querySelector('.active-image-preview');
        const actionBtns = form.querySelector('.proof-action-btns');

        realInput.value = '';
        previewContainer.style.display = 'none';
        actionBtns.style.display = 'flex';
    }
</script>

<?php if (!empty($order['customer_id'])): ?>
<div id="chat-widget" style="position: fixed; bottom: 20px; right: 20px; width: 320px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color);">
    <div style="background: var(--primary); color: #fff; padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleChat()">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined">chat</span>
            <strong id="chat-header-title" style="font-size: 14px;">Chat với Khách Hàng</strong>
        </div>
        <span class="material-symbols-outlined" id="chat-toggle-icon">expand_less</span>
    </div>

    <div id="chat-body" style="display: none; flex-direction: column; height: 350px;">
        <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
            </div>
        <div style="padding: 10px; border-top: 1px solid var(--border-color); background: #fff; display: flex; gap: 8px;">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; outline: none; font-size: 13px;" onkeypress="if(event.key === 'Enter') sendChatMessage()">
            <button onclick="sendChatMessage()" style="background: var(--primary); color: #fff; border: none; width: 38px; height: 38px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">send</span></button>
        </div>
    </div>
</div>

<script>
    const chatOrderId = <?= $order['id'] ?>;
    const chatReceiverId = <?= $order['customer_id'] ?>;
    let chatInterval = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    // Ham toggleChat: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    function toggleChat() {
        const body = document.getElementById('chat-body');
        const icon = document.getElementById('chat-toggle-icon');
        const title = document.getElementById('chat-header-title');
        
        if (body.style.display === 'none') {
            body.style.display = 'flex';
            icon.textContent = 'expand_more';
            if (title) {
                title.style.color = '#fff';
                title.innerText = 'Chat với Khách Hàng';
            }
            loadChatMessages();
            chatInterval = setInterval(loadChatMessages, 3000);
        } else {
            body.style.display = 'none';
            icon.textContent = 'expand_less';
            clearInterval(chatInterval);
        }
    }

    // Ham loadChatMessages: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    async function loadChatMessages() {
        try {
            const res = await fetch(`/api/chat/${chatOrderId}`);
            const data = await res.json();
            if (data.success) {
                const box = document.getElementById('chat-messages');
                const isAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 10;

                let html = '';
                data.messages.forEach(m => {
                    const isMe = Number(m.sender_id) === Number(data.current_user_id);
                    html += `<div style="max-width: 85%; padding: 8px 12px; border-radius: 4px; font-size: 13px; align-self: ${isMe ? 'flex-end' : 'flex-start'}; background: ${isMe ? 'var(--primary)' : '#e2e8f0'}; color: ${isMe ? '#fff' : 'var(--text-main)'}; border-bottom-${isMe ? 'right' : 'left'}-radius: 0;">${escapeHtml(m.message)}</div>`;
                });
                box.innerHTML = html || '<div style="text-align: center; color: var(--text-muted); font-size: 13px; margin-top: auto; margin-bottom: auto;">Chưa có tin nhắn nào.</div>';

                if (isAtBottom) box.scrollTop = box.scrollHeight;
            }
        } catch(e) { console.error("Lỗi tải tin nhắn:", e); }
    }

    // Ham sendChatMessage: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    async function sendChatMessage() {
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';
        await fetch(`/api/chat/${chatOrderId}`, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({receiver_id: chatReceiverId, message: msg})
        });
        loadChatMessages();
    }

    // Lắng nghe Pusher để tự động load tin nhắn
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof pusher !== 'undefined' && typeof currentUserId !== 'undefined') {
            const channel = pusher.channel('notify-user-' + currentUserId) || pusher.subscribe('notify-user-' + currentUserId);
            channel.bind('new_chat_message', function(data) {
                if (Number(data.order_id) === chatOrderId) {
                    const body = document.getElementById('chat-body');
                    if (body.style.display !== 'none' && body.style.display !== '') {
                        loadChatMessages();
                    } else {
                        const title = document.getElementById('chat-header-title');
                        if (title) {
                            title.style.color = '#fde047'; // Đổi tiêu đề sang màu vàng
                            title.innerText = 'Chat (Có tin nhắn mới!)';
                        }
                    }
                }
            });
        }
    });
</script>
<?php endif; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderStatus = '<?= $order['status'] ?>';
        const driverLat = <?= (float)($order['driver_lat'] ?? 0) ?>;
        const driverLng = <?= (float)($order['driver_lng'] ?? 0) ?>;
        const senderLat = <?= (float)($order['sender_lat'] ?? 0) ?>;
        const senderLng = <?= (float)($order['sender_lng'] ?? 0) ?>;
        const receiverLat = <?= (float)($order['receiver_lat'] ?? 0) ?>;
        const receiverLng = <?= (float)($order['receiver_lng'] ?? 0) ?>;
        const senderName = <?= json_encode($order['sender_name'] ?? 'Người gửi', JSON_UNESCAPED_UNICODE) ?>;
        const receiverName = <?= json_encode($order['receiver_name'] ?? 'Khách hàng', JSON_UNESCAPED_UNICODE) ?>;

        if (senderLat !== 0 && senderLng !== 0 && receiverLat !== 0 && receiverLng !== 0) {
            const map = L.map('route-map').setView([senderLat, senderLng], 14);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '&copy; Google Maps'
            }).addTo(map);

            let routingControl = null;
            let currentHasLoc = false;

            // Ham resolveDestinationInfo: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
            function resolveDestinationInfo() {
                if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                    return { lat: senderLat, lng: senderLng, label: `Điểm lấy hàng: ${senderName}` };
                }
                if (orderStatus === 'returning') {
                    return { lat: senderLat, lng: senderLng, label: `Hoàn hàng: ${senderName}` };
                }
                return { lat: receiverLat, lng: receiverLng, label: `Điểm giao hàng: ${receiverName}` };
            }

            // Ham updateRouteNavigationInfo: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
            function updateRouteNavigationInfo(currentDriverLat, currentDriverLng) {
                const destination = resolveDestinationInfo();
                const params = new URLSearchParams({
                    api: '1',
                    destination: `${destination.lat},${destination.lng}`,
                    travelmode: 'driving'
                });

                if (currentDriverLat !== 0 && currentDriverLng !== 0) {
                    params.set('origin', `${currentDriverLat},${currentDriverLng}`);
                }

                document.getElementById('btn-ggmap-nav').href = `https://www.google.com/maps/dir/?${params.toString()}`;
                document.getElementById('route-next-stop').textContent = destination.label;
            }

            // Ham drawRoute: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
            function drawRoute(currentDriverLat, currentDriverLng) {
                const waypoints = [];
                currentHasLoc = (currentDriverLat !== 0 && currentDriverLng !== 0);

                if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    } else {
                        waypoints.push(L.latLng(senderLat, senderLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    }
                } else if (orderStatus === 'in_transit' || orderStatus === 'shipping') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    } else {
                        waypoints.push(L.latLng(senderLat, senderLng));
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                    }
                } else if (orderStatus === 'returning') {
                    if (currentHasLoc) {
                        waypoints.push(L.latLng(currentDriverLat, currentDriverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    } else {
                        waypoints.push(L.latLng(receiverLat, receiverLng));
                        waypoints.push(L.latLng(senderLat, senderLng));
                    }
                } else {
                    waypoints.push(L.latLng(senderLat, senderLng));
                    waypoints.push(L.latLng(receiverLat, receiverLng));
                }

                updateRouteNavigationInfo(currentDriverLat, currentDriverLng);

                const createCustomMarkerIcon = (icon, color) => L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color:${color};width:36px;height:36px;border-radius:50%;border:3px solid #fff;box-shadow:0 4px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined" style="color:#fff;font-size:20px;">${icon}</span><div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-width:8px 6px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border-width:6px 4px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
                    iconSize: [36, 44], iconAnchor: [18, 44], popupAnchor: [0, -44]
                });
                const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');
                const senderIcon = createCustomMarkerIcon('storefront', '#f59e0b');
                const receiverIcon = createCustomMarkerIcon('location_on', '#10b981');

                if (routingControl) {
                    routingControl.setWaypoints(waypoints);
                    return;
                }

                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
                    routeWhileDragging: false,
                    addWaypoints: false,
                    fitSelectedRoutes: true,
                    show: false,
                    lineOptions: {
                        styles: [{color: '#2563eb', opacity: 0.8, weight: 6}]
                    },
                    createMarker: function(i, wp, nWps) {
                        let iconToUse;
                        if (orderStatus === 'accepted' || orderStatus === 'picking_up') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : senderIcon; }
                            else { iconToUse = (i === 0) ? senderIcon : receiverIcon; }
                        } else if (orderStatus === 'in_transit' || orderStatus === 'shipping') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : receiverIcon; }
                            else { iconToUse = (i === 0) ? senderIcon : receiverIcon; }
                        } else if (orderStatus === 'returning') {
                            if (currentHasLoc) { iconToUse = (i === 0) ? driverIcon : senderIcon; }
                            else { iconToUse = (i === 0) ? receiverIcon : senderIcon; }
                        } else {
                            iconToUse = (i === 0) ? senderIcon : receiverIcon;
                        }

                        // Tự động xây dựng URL chuyển hướng Google Maps thông minh
                        let destLat, destLng;
                        if (orderStatus === 'accepted' || orderStatus === 'picking_up') { destLat = senderLat; destLng = senderLng; }
                        else if (orderStatus === 'in_transit' || orderStatus === 'shipping') { destLat = receiverLat; destLng = receiverLng; }
                        else if (orderStatus === 'returning') { destLat = senderLat; destLng = senderLng; }
                        else { destLat = receiverLat; destLng = receiverLng; }

                        let originParam = currentHasLoc ? `${currentDriverLat},${currentDriverLng}` : '';
                        let ggMapUrl = `https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}&travelmode=driving`;
                        if (originParam) ggMapUrl += `&origin=${originParam}`;
                        document.getElementById('btn-ggmap-nav').href = ggMapUrl;

                        let title = '';
                        if (iconToUse === driverIcon) title = 'Vị trí của bạn';
                        if (iconToUse === senderIcon) title = orderStatus === 'returning' ? 'Điểm hoàn hàng (Người gửi)' : 'Điểm lấy hàng';
                        if (iconToUse === receiverIcon) title = 'Điểm giao hàng';

                        return L.marker(wp.latLng, {icon: iconToUse}).bindPopup(`<b>${title}</b>`);
                    }
                }).addTo(map);

                routingControl.on('routesfound', function(e) {
                    const summary = e.routes[0].summary;
                    document.getElementById('route-distance').textContent = (summary.totalDistance / 1000).toFixed(1);
                    document.getElementById('route-time').textContent = Math.round(summary.totalTime / 60);
                    document.getElementById('route-info').style.display = 'block';
                });
            }

            drawRoute(driverLat, driverLng);

            let lastPushTime = 0; // Lưu vết thời gian
            const pushLocationToServer = (lat, lng, accuracy = null) => {
                const now = Date.now();
                // Tối ưu: Nếu chưa qua 10 giây kể từ lần gửi cuối, không gửi lên Server
                if (now - lastPushTime < 10000) return;
                lastPushTime = now;

                fetch('/api/driver/update-location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lat: lat, lng: lng, accuracy: accuracy })
                }).catch(() => {});
            };

            window.recenterMapDetail = function() {
                if (navigator.geolocation) {
                    const btn = document.getElementById('btn-recenter-detail');
                    if(btn) btn.innerHTML = '<span class="material-symbols-outlined icon-spin" style="font-size: 20px;">sync</span>';

                    navigator.geolocation.getCurrentPosition(function(position) {
                        const currentLat = position.coords.latitude;
                        const currentLng = position.coords.longitude;
                        map.setView([currentLat, currentLng], 17);
                        drawRoute(currentLat, currentLng);
                        if (['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus)) {
                            pushLocationToServer(currentLat, currentLng, position.coords.accuracy);
                        }
                        if(btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>';
                    }, function(err) {
                        if(btn) btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">my_location</span>';
                        alert("Không thể lấy vị trí. Vui lòng kiểm tra quyền truy cập vị trí.");
                    }, { enableHighAccuracy: true });
                }
            };

            if (navigator.geolocation && ['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus)) {

                navigator.geolocation.getCurrentPosition(function(position) {
                    drawRoute(position.coords.latitude, position.coords.longitude);
                    pushLocationToServer(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
                }, null, { enableHighAccuracy: true });

                navigator.geolocation.watchPosition(function(position) {
                    drawRoute(position.coords.latitude, position.coords.longitude);
                    pushLocationToServer(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
                }, null, { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 });
            }
        } else {
            document.getElementById('route-map').innerHTML = '<div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--text-muted);">Không có dữ liệu tọa độ bản đồ.</div>';
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
