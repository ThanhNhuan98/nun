<?php require_once __DIR__ . '/../layouts/user_header.php'; ?>

<div class="active-page-header">
    <h2 class="active-page-title">Tất cả thông báo</h2>
    <p class="active-page-subtitle">Xem lại lịch sử thông báo của bạn</p>
</div>

<div class="user-table-card">
    <?php if (empty($allNotifications)): ?>
        <div style="padding: 40px; text-align: center; color: var(--text-muted);">
            <span class="material-symbols-outlined" style="font-size: 48px; margin-bottom: 16px;">notifications_off</span>
            <p>Bạn chưa có thông báo nào.</p>
        </div>
    <?php else: ?>
        <div class="notification-list-full" style="padding: 10px;">
            <?php foreach ($allNotifications as $n): ?>
                <?php 
                    // Xác định biểu tượng và màu sắc theo loại thông báo
                    $icon = 'notifications';
                    $colorClass = 'var(--text-main)';
                    $bgClass = 'var(--bg-body)';
                    
                    if ($n['type'] === 'system') {
                        $icon = 'info';
                        $colorClass = 'var(--primary)';
                        $bgClass = 'var(--primary-light)';
                    } elseif ($n['type'] === 'order') {
                        $icon = 'local_shipping';
                        $colorClass = 'var(--success)';
                        $bgClass = 'var(--success-light)';
                    } elseif ($n['type'] === 'wallet' || $n['type'] === 'penalty') {
                        $icon = 'account_balance_wallet';
                        $colorClass = 'var(--warning)';
                        $bgClass = 'var(--warning-light)';
                    }
                ?>
                <a href="<?= app_e($n['link'] ?: '#') ?>" style="display: flex; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border-color); text-decoration: none; color: inherit; transition: 0.2s; border-radius: 8px;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: <?= $bgClass ?>; color: <?= $colorClass ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined"><?= $icon ?></span>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px 0; font-size: 15px; color: var(--text-main); font-weight: <?= empty($n['is_read']) ? '700' : '500' ?>;"><?= app_e($n['title']) ?></h4>
                        <p style="margin: 0 0 8px 0; font-size: 14px; color: var(--text-muted); line-height: 1.5;"><?= app_e($n['message']) ?></p>
                        <span style="font-size: 12px; color: #94a3b8;"><?= app_datetime($n['created_at']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="table-footer" style="border-top: 1px solid var(--border-color); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div class="table-footer-text">
                    Trang <strong><?= $currentPage ?></strong> / <?= $totalPages ?>
                </div>
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?>" class="pagination-link">Trước</a>
                    <?php endif; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?>" class="pagination-link">Sau</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/user_footer.php'; ?>
