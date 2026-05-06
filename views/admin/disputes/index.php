<?php
/**
 * View: Admin - Quản lý khiếu nại
 * @var array $disputes
 * @var string $pageTitle
 * @var int $currentPage
 * @var int $totalPages
 * @var string $search
 * @var string $statusFilter
 */
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    <div class="admin-page-header" style="text-align: center; margin-bottom: 25px;">
        <h2 class="admin-page-title" style="color: var(--primary); font-weight: 700;"><?= htmlspecialchars($pageTitle ?? 'Quản lý Khiếu nại') ?></h2>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="GET" action="/admin/disputes" class="order-search-form">
        <span class="material-symbols-outlined search-icon">search</span>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm mã đơn, tên khách hàng...">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <?php endif; ?>
        <button type="submit" style="display: none;"></button>
    </form>

    <div class="order-filters">
        <a href="/admin/disputes<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
           class="filter-pill <?= empty($statusFilter) ? 'active' : '' ?>">
           Tất cả
        </a>
        
        <?php 
        $filterOptions = [
            'open' => 'Chưa xử lý',
            'processing' => 'Đang giải quyết',
            'resolved' => 'Đã giải quyết',
            'rejected' => 'Từ chối'
        ];
        foreach ($filterOptions as $val => $label): 
            $isActive = ($statusFilter === $val);
            $url = "/admin/disputes?status=" . $val;
            if (!empty($search)) $url .= "&search=" . urlencode($search);
        ?>
            <a href="<?= $url ?>" class="filter-pill <?= $isActive ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="order-grid">
        <?php if (empty($disputes)): ?>
            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; background: #fff; border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-muted);">
                <span class="material-symbols-outlined" style="font-size: 48px; color: #cbd5e1; margin-bottom: 10px; display: block;">assignment_turned_in</span>
                Không có khiếu nại nào phù hợp.
            </div>
        <?php else: ?>
            <?php foreach ($disputes as $d): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-id" style="display: flex; align-items: center; gap: 5px;">
                            <span class="material-symbols-outlined">receipt_long</span>
                            #<?= htmlspecialchars($d['tracking_code'] ?? 'N/A') ?>
                        </div>
                        
                        <?php 
                            // Định dạng màu trạng thái
                            $badgeClass = 'status-pending';
                            if ($d['status'] === 'open') { $badgeClass = 'status-cancelled'; }
                            elseif ($d['status'] === 'processing') { $badgeClass = 'status-warning'; }
                            elseif ($d['status'] === 'resolved') { $badgeClass = 'status-completed'; }
                            elseif ($d['status'] === 'rejected') { $badgeClass = 'status-pending'; }
                        ?>
                        <span class="card-badge <?= $badgeClass ?>" style="<?= $d['status'] === 'processing' ? 'background: #fef3c7; color: #f59e0b;' : '' ?>">
                            <?= htmlspecialchars($d['status_label'] ?? $d['status']) ?>
                        </span>
                    </div>

                    <div class="order-card-info">
                        <div style="font-weight: bold; font-size: 15px; margin-bottom: 10px; color: #1e293b;">
                            <?= htmlspecialchars($d['issue_type'] ?? 'Sự cố khác') ?>
                        </div>
                        <div class="info-row">
                            <span class="material-symbols-outlined">person</span>
                            <div class="info-text">
                                <strong>Người gửi</strong>
                                <span><?= htmlspecialchars($d['reporter_name']) ?> (<?= htmlspecialchars($d['reporter_role_label'] ?? $d['reporter_role']) ?>)</span>
                            </div>
                        </div>
                    </div>

                    <div class="order-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="time-block" style="color: #64748b; font-size: 13px;">
                            <?= date('d/m/Y', strtotime($d['created_at'])) ?><br>
                            <?= date('H:i', strtotime($d['created_at'])) ?>
                        </div>
                        <?php if ($d['status'] === 'open' || $d['status'] === 'processing'): ?>
                            <a href="/admin/disputes/view/<?= $d['id'] ?>" class="btn-primary" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                                Xem & Xử lý <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                            </a>
                        <?php else: ?>
                            <a href="/admin/disputes/view/<?= $d['id'] ?>" class="btn-secondary" style="text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 13px;">
                                Xem chi tiết
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="pagination-container" style="margin-bottom: 30px;">
            <?php
            $queryParams = [];
            if (!empty($statusFilter)) $queryParams['status'] = $statusFilter;
            if (!empty($search)) $queryParams['search'] = $search;
            ?>

            <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">&laquo; Trước</a>
            <?php else: ?>
                <span class="pagination-link disabled">&laquo; Trước</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">Sau &raquo;</a>
            <?php else: ?>
                <span class="pagination-link disabled">Sau &raquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>