<?php
/**
 * @var array $dispute
 * @var string $pageTitle
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<style>
    /* CSS nhúng riêng cho trang Chi Tiết Khiếu Nại để ép form giống hệt Mockup */
    .dv-breadcrumb { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; font-weight: 500; }
    .dv-breadcrumb a { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
    .dv-breadcrumb a:hover { color: var(--primary, #2563eb); }
    .dv-breadcrumb span.current { color: var(--text-main); font-weight: 700; }
    
    .dv-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: start; }
    
    .dv-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    .dv-card-title { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    
    /* Cảnh báo lỗi vẫn nên để màu đỏ cho đúng UX */
    .dv-badge-danger { border: 1px solid #f87171; color: #dc2626; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; }
    
    .dv-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .dv-label { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
    .dv-value { font-size: 14px; font-weight: 600; color: var(--text-main); }
    
    /* Đổi nền hộp nội dung sang Xanh nhạt (Light Blue) */
    .dv-content-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 16px; border-radius: 6px; font-size: 14px; color: #1e3a8a; line-height: 1.6; font-style: italic; }
    
    /* Khối đơn hàng */
    .dv-order-block { background: #334155; color: #fff; padding: 16px 20px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .dv-order-code { font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin-top: 4px; }
    .dv-order-status-badge { background: #fff; color: #334155; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; }
    
    .dv-reporter-flex { display: flex; gap: 16px; align-items: center; margin-bottom: 16px; }
    .dv-reporter-avatar { width: 60px; height: 60px; border-radius: 4px; object-fit: cover; background: #f1f5f9; border: 1px solid var(--border-color); }
    .dv-reporter-name { font-weight: 700; font-size: 16px; color: var(--text-main); margin-bottom: 4px; }
    .dv-reporter-meta { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
    
    /* Hộp cảnh báo vi phạm màu xanh nhạt để dịu mắt hơn */
    .dv-warning-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px 16px; border-radius: 4px; color: #1e3a8a; font-size: 13px; display: flex; align-items: flex-start; gap: 8px; line-height: 1.5; }
    
    /* NÚT SUBMIT CHUYỂN SANG MÀU XANH NƯỚC (BLUE) */
    .dv-btn-submit { width: 100%; background: var(--primary, #2563eb); color: #fff; border: none; padding: 14px; border-radius: 4px; font-size: 14px; font-weight: 700; cursor: pointer; text-transform: uppercase; transition: 0.2s; margin-top: 10px; }
    .dv-btn-submit:hover { background: #1d4ed8; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
    
    /* Ghi đè style form cho gọn gàng */
    .form-group label { font-size: 12px; color: var(--text-muted); margin-bottom: 6px; }
    .form-control { border: 1px solid var(--border-color); border-radius: 4px; padding: 10px 12px; font-size: 14px; outline: none; transition: 0.2s; }
    .form-control:focus { border-color: var(--primary, #2563eb); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    @media (max-width: 992px) {
        .dv-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="admin-container">
    
    <!-- Breadcrumb Header -->
    <div class="dv-breadcrumb">
        <a href="/admin/disputes">Quản lý khiếu nại</a>
        <span class="material-symbols-outlined" style="font-size: 16px;">chevron_right</span>
        <span class="current">Chi tiết khiếu nại #<?= htmlspecialchars($dispute['id']) ?></span>
    </div>

    <!-- Bao bọc toàn bộ Grid trong Form để submit -->
    <form action="/admin/disputes/view/<?= $dispute['id'] ?>" method="POST" id="disputeForm">
        
        <div class="dv-grid">
            
            <!-- ================= CỘT TRÁI ================= -->
            <div class="dv-col-left">
                
                <!-- CARD: THÔNG TIN KHIẾU NẠI -->
                <div class="dv-card">
                    <?php
                        // Xử lý tách chuỗi Issue Type và Proof URL (Giữ nguyên logic PHP gốc)[cite: 7]
                        $issueStr = $dispute['issue_type'] ?? 'Khác';
                        $proofUrl = '';
                        if (strpos($issueStr, '||PROOF||') !== false) {
                            $parts = explode('||PROOF||', $issueStr);
                            $issueStr = $parts[0];
                            $proofUrl = $parts[1];
                        }
                        $issueParts = explode(' - Chi tiết: ', $issueStr, 2);
                        $issueTypeLabel = $issueParts[0];
                        $issueDetail = $issueParts[1] ?? $issueStr;
                    ?>
                    <div class="dv-card-title">
                        Thông tin khiếu nại
                        <div class="dv-badge-danger">
                            <span class="material-symbols-outlined" style="font-size: 16px;">warning</span>
                            <?= htmlspecialchars($issueTypeLabel) ?>
                        </div>
                    </div>
                    
                    <div class="dv-info-grid">
                        <div>
                            <div class="dv-label">Ngày tạo</div>
                            <div class="dv-value"><?= date('d/m/Y H:i', strtotime($dispute['created_at'])) ?></div>
                        </div>
                        <div>
                            <div class="dv-label">Mức độ ưu tiên</div>
                            <div class="dv-value" style="color: #dc2626;">Cao</div>
                        </div>
                    </div>
                    
                    <div class="dv-label" style="margin-top: 16px;">Nội dung phản ánh</div>
                    <div class="dv-content-box">
                        "<?= nl2br(htmlspecialchars($issueDetail)) ?>"
                    </div>
                    
                    <?php if ($proofUrl): ?>
                    <div style="margin-top: 24px;">
                        <div class="dv-label">Bằng chứng đính kèm</div>
                        <a href="<?= app_e($proofUrl) ?>" target="_blank" title="Nhấn để xem ảnh gốc">
                            <img src="<?= app_e($proofUrl) ?>" alt="Bằng chứng" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color); margin-top: 8px;">
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CARD: ĐƠN HÀNG LIÊN QUAN -->
                <div class="dv-card">
                    <div class="dv-card-title" style="border: none; padding-bottom: 0;">Đơn hàng liên quan</div>
                    
                    <div class="dv-order-block">
                        <div>
                            <div style="font-size: 12px; opacity: 0.8; text-transform: uppercase;">Mã vận đơn</div>
                            <div class="dv-order-code">
                                <span class="material-symbols-outlined">inventory_2</span>
                                #<?= htmlspecialchars($dispute['tracking_code']) ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; opacity: 0.8; margin-bottom: 4px;">Trạng thái hiện tại</div>
                            <div class="dv-order-status-badge">
                                <?= app_e(app_status_label($dispute['order_status'] ?? '')) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <a href="/admin/orders/view/<?= $dispute['order_id'] ?>" style="color: var(--primary, #2563eb); font-size: 13px; font-weight: 600; text-decoration: none;">Xem chi tiết đơn hàng &rarr;</a>
                    </div>
                </div>

            </div>

            <!-- ================= CỘT PHẢI ================= -->
            <div class="dv-col-right">
                
                <!-- CARD: NGƯỜI BÁO CÁO -->
                <div class="dv-card">
                    <div class="dv-card-title">Thông tin người báo cáo</div>
                    
                    <div class="dv-reporter-flex">
                        <?php
                            $rAvatar = $dispute['reporter_avatar'] ?? '';
                            $rAvatarUrl = app_avatar_url($rAvatar, $dispute['reporter_name'] ?? 'U');
                        ?>
                        <img src="<?= htmlspecialchars($rAvatarUrl) ?>" alt="Avatar" class="dv-reporter-avatar">
                        <div>
                            <div class="dv-reporter-name"><?= htmlspecialchars($dispute['reporter_name']) ?></div>
                            <div class="dv-reporter-meta">Vai trò: <?= htmlspecialchars($dispute['reporter_role_label'] ?? $dispute['reporter_role'] ?? 'Không xác định') ?></div>
                            <div class="dv-reporter-meta">SĐT: <?= htmlspecialchars($dispute['reporter_phone']) ?></div>
                        </div>
                    </div>
                    
                    <?php if (($dispute['reporter_no_show_count'] ?? 0) > 0): ?>
                        <div class="dv-warning-box">
                            <span class="material-symbols-outlined" style="font-size: 18px;">info</span>
                            Lịch sử: Khách hàng đã có <?= htmlspecialchars($dispute['reporter_no_show_count']) ?> khiếu nại/tương tự trong 3 tháng qua. Cần xem xét kỹ lưỡng.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CARD: CẬP NHẬT XỬ LÝ -->
                <div class="dv-card">
                    <div class="dv-card-title">Cập nhật xử lý</div>
                    
                    <div class="form-group">
                        <label>Trạng thái khiếu nại</label>
                        <select name="status" class="form-control">
                            <option value="open" <?= $dispute['status'] === 'open' ? 'selected' : '' ?>>Đang mở</option>
                            <option value="in_review" <?= in_array($dispute['status'], ['in_review', 'processing'], true) ? 'selected' : '' ?>>Đang xử lý</option>
                            <option value="resolved" <?= $dispute['status'] === 'resolved' ? 'selected' : '' ?>>Đã giải quyết (Hoàn tất)</option>
                            <option value="rejected" <?= $dispute['status'] === 'rejected' ? 'selected' : '' ?>>Từ chối khiếu nại</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cập nhật trạng thái Đơn hàng đích</label>
                        <select name="target_order_status" class="form-control">
                            <option value="">Không thay đổi (<?= app_e(app_status_label($dispute['order_status'] ?? '')) ?>)</option>
                            <option value="completed" <?= ($dispute['order_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                            <option value="returning" <?= ($dispute['order_status'] ?? '') === 'returning' ? 'selected' : '' ?>>Chuyển hoàn</option>
                            <option value="returned" <?= ($dispute['order_status'] ?? '') === 'returned' ? 'selected' : '' ?>>Đã hoàn hàng</option>
                            <option value="cancelled" <?= ($dispute['order_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy đơn</option>
                        </select>
                    </div>

                    <?php
                    // Logic xác định đối tượng phạt[cite: 7]
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
                        <label>Xác định lỗi</label>
                        <select name="fault" class="form-control" onchange="togglePenalty(this.value)">
                            <option value="none" <?= $faultSelected === 'none' ? 'selected' : '' ?>>Chưa xác định</option>
                            <option value="customer" <?= $faultSelected === 'customer' ? 'selected' : '' ?>>Khách hàng (Cộng vi phạm)</option>
                            <option value="driver" <?= $faultSelected === 'driver' ? 'selected' : '' ?>>Tài xế (Phạt tiền)</option>
                        </select>
                    </div>

                    <div class="form-group" id="penalty_div" style="display: <?= $faultSelected === 'driver' ? 'block' : 'none' ?>;">
                        <label>Số tiền phạt tài xế (đ)</label>
                        <input type="number" name="penalty_amount" class="form-control" placeholder="Nhập 0 để cảnh cáo..." min="0" step="1000" value="<?= htmlspecialchars($penaltyAmountValue) ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Ghi chú xử lý (Nội bộ)</label>
                        <textarea name="resolution_note" rows="4" class="form-control" placeholder="Nhập chi tiết quá trình xử lý, biên bản làm việc..."><?= htmlspecialchars($dispute['resolution_note'] ?? $dispute['admin_note'] ?? '') ?></textarea>
                    </div>

                    <!-- Nút LƯU THAY ĐỔI chuẩn màu Xanh -->
                    <button type="submit" class="dv-btn-submit">
                        Lưu thay đổi
                    </button>
                </div>

            </div> <!-- End Cột Phải -->
            
        </div>
    </form>

    <script>
    function togglePenalty(val) {
        document.getElementById('penalty_div').style.display = val === 'driver' ? 'block' : 'none';
    }
    </script>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>