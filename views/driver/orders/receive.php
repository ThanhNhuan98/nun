<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>
        
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert-banner" style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
        <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert-banner" style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fecaca;">
        <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<div class="wallet-summary-card">
    <div class="wallet-summary-info">
        <span class="wallet-label">Số dư ví</span>
        <span class="wallet-balance-amount"><?= app_money($walletBalance ?? 2450000, ' đ') ?></span>
    </div>
    <a href="/driver/wallet/topup" class="btn-topup-outline">
        <span class="material-symbols-outlined" style="font-size: 18px;">payments</span> Nạp tiền vào ví
    </a>
</div>

<div id="radar-loading" class="radar-scan-wrapper">
    <div class="radar-animation-box">
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-core">
            <span class="material-symbols-outlined">radar</span>
        </div>
    </div>
    <div class="radar-status-text">Đang quét khu vực...</div>
</div>

<div id="batches-wrapper" style="display: none;">
    <div class="section-divider">Chuyến Mới Khu Vực</div>
    <div id="batches-container"></div>
</div>

<div id="empty-state" style="display: none; text-align: center; padding: 40px; border: 1px solid var(--border-color); border-radius: 4px; background: #fff;">
    <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;">location_off</span>
    <h3 style="font-size: 16px; color: var(--text-main); margin-bottom: 8px;">Không tìm thấy chuyến đi mới</h3>
    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Hiện tại không có đơn hàng nào chờ ghép tài xế trong khu vực của bạn.</p>
    <button onclick="window.location.reload();" class="btn-topup-outline" style="margin: 0 auto;">
        Tải lại radar
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const loadingEl = document.getElementById('radar-loading');
        const wrapperEl = document.getElementById('batches-wrapper');
        const containerEl = document.getElementById('batches-container');
        const emptyEl = document.getElementById('empty-state');
        
        try {
            // Gọi API tính toán AI ở Background
            const res = await fetch('/driver/receive-orders?ajax=1');
            const data = await res.json();
            
            loadingEl.style.display = 'none';
            
            if (data.success && data.batches && data.batches.length > 0) {
                wrapperEl.style.display = 'block';
                renderBatches(data.batches);
            } else {
                emptyEl.style.display = 'block';
            }
        } catch (e) {
            console.error('Radar scan failed:', e);
            loadingEl.innerHTML = '<div style="color: #ef4444; text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px;">error</span><h4>Lỗi kết nối máy chủ</h4></div>';
        }
        
        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        }

        function renderBatches(batches) {
            let html = '';
            batches.forEach((batch, index) => {
                const isBest = index === 0 && batch.efficiency_score > 0;
                const efficiencyFmt = batch.efficiency_score ? formatMoney(batch.efficiency_score) + '/phút' : 'N/A';
                const accessMin = batch.access_duration_minutes != null ? batch.access_duration_minutes + ' phút' : 'N/A';
                const totalMin = batch.total_trip_duration_minutes != null ? batch.total_trip_duration_minutes + ' phút' : 'N/A';
                const totalFee = formatMoney(batch.total_fee || 0);
                const displayBatchId = batch.batch_id ? batch.batch_id.replace('BATCH_', 'CHUYẾN_') : 'N/A';

                // Gom mảng các điểm dừng (Stops) để vẽ Timeline
                let stops = [];
                if (batch.order_details) {
                    batch.order_details.forEach((order, idx) => {
                        stops.push({ title: `Lấy hàng (Đơn #${order.tracking_code})`, address: order.pickup_address });
                        stops.push({ title: `Giao hàng (Đơn #${order.tracking_code})`, address: order.delivery_address });
                    });
                }

                let timelineHtml = '<div class="batch-route-timeline">';
                stops.forEach((stop, idx) => {
                    let dotClass = '';
                    if (idx === 0) dotClass = 'start';
                    else if (idx === stops.length - 1) dotClass = 'end';
                    
                    timelineHtml += `
                        <div class="route-node">
                            <div class="node-dot ${dotClass}"></div>
                            <div class="node-title">${stop.title}</div>
                            <div class="node-address">${stop.address || 'Đang cập nhật...'}</div>
                        </div>
                    `;
                });
                timelineHtml += '</div>';

                // Hiển thị danh sách chi tiết các đơn hàng trong chuyến
                let ordersListHtml = '';
                if (batch.order_details && batch.total_orders > 0) {
                    ordersListHtml = '<div style="padding: 15px 20px 5px 20px; border-bottom: 1px dashed var(--border-color); background: #f8fafc;">';
                    ordersListHtml += `<div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">Chi tiết ${batch.total_orders} đơn hàng:</div>`;
                    ordersListHtml += '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    batch.order_details.forEach(order => {
                        
                        // Tính toán thời gian và đổi màu đỏ nếu còn dưới 15 phút (900000ms)
                        let timeStyle = 'color: var(--text-muted);';
                        if (order.scheduled_at) {
                            const scheduledTime = new Date(order.scheduled_at.replace(/-/g, '/')).getTime();
                            const now = new Date().getTime();
                            if (scheduledTime - now <= 900000) {
                                timeStyle = 'color: var(--danger); font-weight: 600;';
                            }
                        }

                        ordersListHtml += `
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; background: #fff; padding: 12px 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                                <div style="display: flex; flex-direction: column; gap: 4px; overflow: hidden; padding-right: 10px;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="material-symbols-outlined" style="color: var(--primary); font-size: 16px;">inventory_2</span>
                                        <strong style="font-size: 13px; color: var(--text-main);">#${order.tracking_code}</strong>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); padding-left: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${order.pickup_address || ''}">
                                        <strong>Lấy:</strong> ${order.pickup_address || 'Đang cập nhật'}
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); padding-left: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${order.delivery_address || ''}">
                                        <strong>Giao:</strong> ${order.delivery_address || 'Đang cập nhật'}
                                    </div>
                                    <div style="font-size: 12px; padding-left: 22px; margin-top: 2px; ${timeStyle}">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: text-bottom;">schedule</span>
                                        <strong>Hẹn lấy:</strong> ${order.formatted_scheduled_at || 'Càng sớm càng tốt'}
                                    </div>
                                </div>
                                <strong style="font-size: 14px; color: var(--primary); flex-shrink: 0; margin-top: 2px;">${formatMoney(order.shipping_fee || 0)}</strong>
                            </div>
                        `;
                    });
                    ordersListHtml += '</div></div>';
                }

                // Lấy order_ids ẩn cho Form Submit
                let inputsHtml = '';
                if (batch.optimized_route) {
                    batch.optimized_route.forEach(oid => {
                        inputsHtml += `<input type="hidden" name="order_ids[]" value="${oid}">`;
                    });
                }

                html += `
                <div class="batch-card-v2">
                    <div class="batch-header-v2">
                        <div class="batch-title">
                             ${displayBatchId} (${batch.total_orders} đơn)
                            ${isBest ? '<span class="badge-best-choice"><span class="material-symbols-outlined" style="font-size:14px;">workspace_premium</span> Lựa chọn tốt nhất</span>' : ''}
                        </div>
                        <span class="badge-batch-type">${batch.total_orders > 1 ? 'GHÉP CHUYẾN' : 'ĐƠN LẺ'}</span>
                    </div>

                    <div class="batch-metrics-grid">
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">trending_up</span>
                            <div class="metric-label">Hiệu quả</div>
                            <div class="metric-value" style="color: #059669;">${efficiencyFmt}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">directions_run</span>
                            <div class="metric-label">Điểm lấy</div>
                            <div class="metric-value">${accessMin}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">timer</span>
                            <div class="metric-label">Tổng T.Gian</div>
                            <div class="metric-value">${totalMin}</div>
                        </div>
                        <div class="metric-col">
                            <span class="material-symbols-outlined metric-icon">weight</span>
                            <div class="metric-label">Khối Lượng</div>
                            <div class="metric-value">${batch.total_weight ? parseFloat(batch.total_weight).toFixed(1) + ' kg' : 'N/A'}</div>
                        </div>
                    </div>

                    ${ordersListHtml}

                    ${timelineHtml}

                    <div class="batch-footer-v2">
                        <div class="batch-total-fee">
                            Tổng phí
                            <strong>${totalFee}</strong>
                        </div>
                        <form method="POST" action="/driver/receive-orders" style="margin: 0;">
                            ${inputsHtml}
                            <button type="submit" class="btn-accept-trip">Nhận Chuyến Ngay</button>
                        </form>
                    </div>
                </div>
                `;
            });
            containerEl.innerHTML = html;
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>