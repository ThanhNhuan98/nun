<?php
/**
 * @var string $pageTitle
 * @var array $stats
 */
?>
<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="admin-page-header">
        <h2 class="admin-page-title" style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            <?= htmlspecialchars($pageTitle ?? 'Công việc cần xử lý') ?>
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0; max-width: 600px; line-height: 1.5;">
            Tổng hợp các công việc cần quản trị viên can thiệp hoặc xử lý ngay để đảm bảo luồng vận hành không bị gián đoạn.
        </p>
    </div>

    <div class="task-grid-layout">
        
        <div class="task-card-large danger">
            <div class="task-card-top">
                <div class="task-icon-box">
                    <span class="material-symbols-outlined">report</span>
                </div>
                <div class="task-count-number">
                    <?= str_pad(htmlspecialchars($stats['open_disputes'] ?? 0), 2, '0', STR_PAD_LEFT) ?>
                </div>
            </div>
            
            <div class="task-card-title">Khiếu nại cần xử lý</div>
            <div class="task-card-desc">
                Các tranh chấp mở, phản hồi từ khách hàng hoặc tài xế cần được giải quyết kịp thời.
            </div>
            
            <a href="/admin/disputes" class="btn-task-action outline-danger">
                Đi tới xử lý <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
            </a>
        </div>

        <div class="task-card-large primary">
            <div class="task-card-top">
                <div class="task-icon-box">
                    <span class="material-symbols-outlined">credit_card</span>
                </div>
                <div class="task-count-number">
                    <?= str_pad(htmlspecialchars($stats['pending_online_payments'] ?? 0), 2, '0', STR_PAD_LEFT) ?>
                </div>
            </div>
            
            <div class="task-card-title">Đơn chờ thanh toán</div>
            <div class="task-card-desc">
                Các đơn hàng online đang chờ xác nhận giao dịch chuyển khoản thành công để duyệt đơn.
            </div>
            
            <a href="/admin/orders?status=awaiting_payment" class="btn-task-action outline-primary">
                Xem danh sách <span class="material-symbols-outlined" style="font-size: 18px;">receipt_long</span>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
