<?php
/**
 * @var array $dispute
 * @var string $pageTitle
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="admin-page-header" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
        <a href="/admin/disputes" style="color: var(--primary); display: flex; align-items: center; text-decoration: none;">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h2 class="admin-page-title" style="margin: 0; flex: 1; text-align: center; font-size: 18px; font-weight: 700;">
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div style="width: 24px;"></div> </div>

    <form action="/admin/disputes/view/<?= $dispute['id'] ?>" method="POST" id="disputeForm">
        <div class="detail-container">
            
            <div class="detail-section">
                <div class="detail-section-title">Thông tin khiếu nại</div>
                
                <div class="detail-row">
                    <span class="detail-label">Loại sự cố</span>
                    <span class="detail-value"><?= htmlspecialchars($dispute['issue_type'] ?? 'Khác') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ngày Hẹn Lấy Hàng</span>
                    <span class="detail-value"><?= date('d/m/Y H:i', strtotime($dispute['created_at'])) ?></span>
                </div>
                
                <div style="margin-top: 16px;">
                    <span class="detail-label" style="font-size: 14px;">Nội dung phản ánh</span>
                    <div class="detail-content-box">
                        <?= nl2br(htmlspecialchars($dispute['description'] ?? 'Không có nội dung chi tiết')) ?>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Người khiếu nại</div>
                <div class="detail-flex-item">
                    <?php
                    $rAvatar = $dispute['reporter_avatar'] ?? '';
                    $rAvatarUrl = app_avatar_url($rAvatar, $dispute['reporter_name'] ?? 'U');
                    ?>
                    <img src="<?= htmlspecialchars($rAvatarUrl) ?>" alt="Avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                    
                    <div>
                        <div class="item-info-main">
                            <?= htmlspecialchars($dispute['reporter_name']) ?>
                            <span class="role-badge"><?= htmlspecialchars($dispute['reporter_role_label'] ?? $dispute['reporter_role']) ?></span>
                        </div>
                        <div class="item-info-sub">
                            <span class="material-symbols-outlined" style="font-size: 16px;">call</span>
                            <?= htmlspecialchars($dispute['reporter_phone']) ?>
                        </div>
                        <?php if (($dispute['reporter_no_show_count'] ?? 0) > 0): ?>
                            <div class="item-info-sub" style="color: var(--danger); font-weight: 600; margin-top: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 16px; color: var(--danger);">warning</span>
                                Đã bom hàng: <?= htmlspecialchars($dispute['reporter_no_show_count']) ?> lần
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Đơn hàng liên quan</div>
                <div class="detail-flex-item" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div class="icon-box"><span class="material-symbols-outlined">receipt_long</span></div>
                        <div>
                            <div class="item-info-sub" style="margin-bottom: 2px;">Mã vận đơn</div>
                            <a href="/admin/orders/view/<?= $dispute['order_id'] ?>" class="item-info-main" style="color: var(--primary); text-decoration: none;">
                                #<?= htmlspecialchars($dispute['tracking_code']) ?>
                            </a>
                        </div>
                    </div>
                    
                    <span class="card-badge <?= app_status_class($dispute['order_status'] ?? '') ?>">
                        <?= app_e(app_status_label($dispute['order_status'] ?? '')) ?>
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Cập nhật xử lý</div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái xử lý</label>
                    <select name="status" class="form-control">
                        <option value="open" <?= $dispute['status'] === 'open' ? 'selected' : '' ?>>Đang mở (Cần xử lý)</option>
                        <option value="processing" <?= $dispute['status'] === 'processing' ? 'selected' : '' ?>>Đang giải quyết</option>
                        <option value="resolved" <?= $dispute['status'] === 'resolved' ? 'selected' : '' ?>>Đã giải quyết (Hoàn tất)</option>
                        <option value="rejected" <?= $dispute['status'] === 'rejected' ? 'selected' : '' ?>>Từ chối khiếu nại</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quyết định với Đơn hàng</label>
                    <select name="order_status" class="form-control">
                        <option value="">-- Không thay đổi (Giữ nguyên trạng thái) --</option>
                        <option value="returning" <?= ($dispute['order_status'] ?? '') === 'returning' ? 'selected' : '' ?>>Tiếp tục chuyển hoàn (Tài xế báo cáo đúng)</option>
                        <option value="completed" <?= ($dispute['order_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Đã giao thành công (Khách đã nhận được hàng)</option>
                        <option value="cancelled" <?= ($dispute['order_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Hủy đơn (Lỗi tài xế, cần hoàn tiền cho khách)</option>
                    </select>
                    <span class="form-help">Nếu bạn chọn trạng thái mới, lịch sử đơn hàng sẽ tự động cập nhật và lưu vết sự can thiệp của Quản trị viên.</span>
                </div>

                <?php
                // Xác định trạng thái người vi phạm từ ghi chú để hiển thị lại
                $faultSelected = 'none';
                $penaltyAmountValue = '';
                $resNote = $dispute['resolution_note'] ?? $dispute['admin_note'] ?? '';
                if (strpos($resNote, 'Đã phạt khách hàng') !== false) {
                    $faultSelected = 'customer';
                } elseif (strpos($resNote, 'Đã phạt tài xế') !== false) {
                    $faultSelected = 'driver';
                    if (preg_match('/Đã phạt tài xế ([\d\.\,]+)đ/u', $resNote, $matches)) {
                        $penaltyAmountValue = str_replace(['.', ','], '', $matches[1]);
                    }
                }
                ?>
                <div class="form-group">
                    <label class="form-label">Xác định người vi phạm (Tùy chọn)</label>
                    <select name="fault" class="form-control" onchange="togglePenalty(this.value)">
                        <option value="none" <?= $faultSelected === 'none' ? 'selected' : '' ?>>-- Không phạt ai --</option>
                        <option value="customer" <?= $faultSelected === 'customer' ? 'selected' : '' ?>>Khách hàng vi phạm (Cộng 1 lần bom hàng)</option>
                        <option value="driver" <?= $faultSelected === 'driver' ? 'selected' : '' ?>>Tài xế vi phạm (Phạt tiền)</option>
                    </select>
                    <span class="form-help">Chọn đối tượng để hệ thống tự động ghi nhận vi phạm. (Hệ thống chỉ xử lý phạt 1 lần duy nhất)</span>
                </div>

                <div class="form-group" id="penalty_div" style="display: <?= $faultSelected === 'driver' ? 'block' : 'none' ?>;">
                    <label class="form-label">Số tiền phạt tài xế (đ)</label>
                    <input type="number" name="penalty_amount" class="form-control" placeholder="Ví dụ: 50000" min="0" step="1000" value="<?= htmlspecialchars($penaltyAmountValue) ?>">
                    <span class="form-help">Số tiền này sẽ được trừ thẳng vào ví của tài xế và có thông báo đi kèm.</span>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Ghi chú của Admin (Kết quả xử lý)</label>
                    <textarea name="resolution_note" rows="5" class="form-control" placeholder="Nhập biên bản, lý do từ chối hoặc kết quả xử lý vào đây..."><?= htmlspecialchars($dispute['resolution_note'] ?? $dispute['admin_note'] ?? '') ?></textarea>
                    <span class="form-help">Ghi chú này giúp lưu vết quá trình xử lý cho quản trị viên.</span>
                </div>
            </div>

        </div>

        <div class="sticky-action-bar">
            <button type="submit" class="btn-submit-large">
                <span class="material-symbols-outlined">save</span> Lưu thay đổi
            </button>
        </div>
    </form>

    <script>
    function togglePenalty(val) {
        document.getElementById('penalty_div').style.display = val === 'driver' ? 'block' : 'none';
    }
    </script>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>