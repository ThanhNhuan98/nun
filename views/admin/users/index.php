<?php
/**
 * View: Admin - Quản lý người dùng
 * @var string $pageTitle
 * @var array $users
 * @var string $search
 * @var string $roleFilter
 * @var int $totalPages
 * @var int $currentPage
 */
$users = $users ?? [];
?>

<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 class="admin-page-title" style="margin-bottom: 6px; font-size: 24px; font-weight: 700; color: var(--text-main);">
                <?= htmlspecialchars($pageTitle ?? 'Quản lý người dùng') ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 14px; margin: 0;">
                Quản lý tài khoản, phân quyền và theo dõi hoạt động người dùng.
            </p>
        </div>
        <a href="/admin/users/create" class="btn-submit-primary" style="text-decoration: none; padding: 12px 20px;">
            <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span> Thêm người dùng mới
        </a>
    </div>

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

    <div class="user-filter-wrapper">
        <form action="/admin/users" method="GET" class="user-filter-grid">
            
            <div class="filter-group">
                <label class="filter-label">Tìm kiếm</label>
                <div class="filter-input-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Tìm tên, email, SĐT..." class="form-control">
                </div>
            </div>

            <div class="filter-group" style="flex: 0.5;">
                <label class="filter-label">Vai trò</label>
                <select name="role" class="form-control">
                    <option value="">Tất cả vai trò</option>
                    <option value="user" <?= ($roleFilter ?? '') === 'user' ? 'selected' : '' ?>>Khách hàng</option>
                    <option value="driver" <?= ($roleFilter ?? '') === 'driver' ? 'selected' : '' ?>>Tài xế</option>
                    <option value="admin" <?= ($roleFilter ?? '') === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filter">
                    <span class="material-symbols-outlined">filter_list</span> Tìm kiếm & Lọc
                </button>
                <?php if (!empty($search) || !empty($roleFilter)): ?>
                    <a href="/admin/users" class="btn-filter" style="color: var(--danger); text-decoration: none;">
                        Xóa lọc
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($users)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-muted);">
            <span class="material-symbols-outlined" style="font-size: 48px; margin-bottom: 15px; color: #cbd5e1;">group_off</span>
            <p style="font-size: 16px; margin: 0; font-weight: 500;">Chưa có người dùng nào trên hệ thống.</p>
        </div>
    <?php else: ?>
        <div class="user-table-container">
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Liên hệ</th>
                            <th>Vai trò</th>
                            <th>Số dư ví</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="td-id">#USR-<?= str_pad($user['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                
                                <td>
                                    <div class="user-profile-flex">
                                        <?php
                                        $rawAvatar = $user['avatar'] ?? '';
                                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                                        if (!empty($rawAvatar) && strpos($rawAvatar, 'default-avatar.png') === false) {
                                            $avatarUrl = (strpos($rawAvatar, 'http') === 0 || strpos($rawAvatar, '/') === 0) ? $rawAvatar : '/uploads/avatars/' . $rawAvatar;
                                        } else {
                                            $avatarUrl = $basePath . '/assets/images/default-avatar.png';
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
                                        <div class="user-info-col">
                                            <span class="user-info-name"><?= htmlspecialchars($user['name']) ?></span>
                                            <span class="user-info-sub" style="font-size: 12px;">
                                                Tham gia: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="td-contact">
                                    <?= htmlspecialchars($user['email'] ?? 'N/A') ?>
                                    <span><?= htmlspecialchars($user['phone']) ?></span>
                                </td>

                                <td>
                                    <?php
                                    $roleClass = 'role-user';
                                    $roleText = 'Khách hàng';
                                    if ($user['role'] === 'admin') { $roleClass = 'role-admin'; $roleText = 'Quản trị viên'; }
                                    elseif ($user['role'] === 'driver') { $roleClass = 'role-driver'; $roleText = 'Tài xế'; }
                                    ?>
                                    <span class="role-tag <?= $roleClass ?>"><?= $roleText ?></span>
                                    <?php if ($user['role'] === 'driver' && !empty($user['is_driver_verified'])): ?>
                                        <span class="material-symbols-outlined" style="color: var(--success); font-size: 16px; vertical-align: -3px; margin-left: 4px;" title="Tài xế đã xác thực">verified</span>
                                    <?php endif; ?>
                                    <?php if ($user['role'] === 'user' && !empty($user['license_plate']) && empty($user['is_driver_verified'])): ?>
                                        <br><span style="display: inline-block; background: #f59e0b; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-top: 4px;">Chờ duyệt tài xế</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($user['role'] === 'driver'): ?>
                                        <strong style="color: #ea580c;"><?= number_format($user['balance'] ?? 0, 0, ',', '.') ?> đ</strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($user['is_blocked']): ?>
                                        <span class="status-dot locked">Đã khóa</span>
                                    <?php else: ?>
                                        <span class="status-dot active">Đang hoạt động</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-links">
                                        <?php if ($user['role'] !== 'admin' || $user['id'] === $_SESSION['user']['id']): ?>
                                            <a href="/admin/users/edit/<?= htmlspecialchars($user['id']) ?>" class="edit">Sửa</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['is_blocked']): ?>
                                            <a href="/admin/users/unblock/<?= htmlspecialchars($user['id']) ?>" class="unlock">Mở khóa</a>
                                        <?php elseif ($user['role'] !== 'admin'): ?>
                                            <a href="/admin/users/block/<?= htmlspecialchars($user['id']) ?>" class="lock">Khóa</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($totalPages ?? 1) > 1): ?>
                <div class="table-footer">
                    <div class="table-footer-text">
                        Trang <strong><?= $currentPage ?></strong> / <strong><?= $totalPages ?></strong>
                    </div>
                    
                    <div class="pagination-container" style="margin: 0;">
                        <?php
                        $queryParams = [];
                        if (!empty($roleFilter)) $queryParams['role'] = $roleFilter;
                        if (!empty($search)) $queryParams['search'] = $search;
                        ?>

                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">&laquo;</a>
                        <?php else: ?>
                            <span class="pagination-link disabled">&laquo;</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">&raquo;</a>
                        <?php else: ?>
                            <span class="pagination-link disabled">&raquo;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>