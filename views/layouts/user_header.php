<?php
$role = app_current_user('role', 'user');
$userName = app_current_user('name', 'Guest');
$userId = (int) app_current_user('id', 0);
$userAvatar = app_current_user('avatar', '');
$displayAvatar = $layoutDisplayAvatar ?? app_avatar_url($userAvatar, $userName);

// Nhận diện tự động thư mục gốc (nếu chạy qua XAMPP)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptName === '/' || $scriptName === '\\') ? '' : $scriptName;

// Xử lý trường hợp hàm app_avatar_url tự động nối thêm /uploads/avatars/ làm sai đường dẫn
if (strpos($displayAvatar, 'default-avatar.png') !== false) {
    $displayAvatar = $basePath . '/assets/images/default-avatar.png';
}

// Chốt chặn cuối cùng, đảm bảo luôn có ảnh mặc định
if (empty($displayAvatar)) {
    $displayAvatar = $basePath . '/assets/images/default-avatar.png';
}

// Layout data is prepared by Response to keep the view out of database work.
$notifications = $layoutNotifications ?? [];
$unreadCount = (int) ($layoutUnreadCount ?? 0);

// Lấy từ khóa tìm kiếm hiện tại từ URL để đồng bộ lên thanh tìm kiếm
$globalSearchQuery = $_GET['search'] ?? $_GET['code'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Trang của tôi') ?> - NUN Express</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar" id="app-sidebar">
        <a href="/" style="text-decoration: none; color: inherit; display: block;">
            <h2 class="sidebar-logo">NUN Express</h2>
        </a>
        <div class="sidebar-user" style="display: flex; align-items: center; gap: 12px;">
            <img src="<?= app_e($displayAvatar) ?>" alt="Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #334155; background: #fff;">
            <div>
                <small class="sidebar-user-greeting">Xin chào,</small>
                <strong class="sidebar-user-name" style="margin-top: 2px; display: block;"><?= app_e($userName) ?></strong>
            </div>
        </div>

        <?php if ($role === 'user'): ?>
            <a href="/user/dashboard" class="<?= app_nav_active('/user/dashboard', true) ?>"><span class="material-symbols-outlined">dashboard</span> Bảng điều khiển</a>
            <a href="/user/orders/create" class="<?= app_nav_active('/user/orders/create') ?>"><span class="material-symbols-outlined">add_box</span> Tạo đơn hàng</a>
            <a href="/user/orders" class="<?= app_nav_active('/user/orders', true) ?>"><span class="material-symbols-outlined">list_alt</span> Danh sách đơn</a>
        <?php elseif ($role === 'driver'): ?>
            <a href="/driver/active-orders" class="<?= app_nav_active('/driver/active-orders') ?>"><span class="material-symbols-outlined">route</span> Đơn đang chạy</a>
            <a href="/driver/receive-orders" class="<?= app_nav_active('/driver/receive-orders') ?>"><span class="material-symbols-outlined">radar</span> Radar nhận đơn</a>
            <a href="/driver/wallet/topup" class="<?= app_nav_active('/driver/wallet/topup') ?>"><span class="material-symbols-outlined">account_balance_wallet</span> Nạp tiền ví</a>
            <a href="/driver/history" class="<?= app_nav_active('/driver/history') ?>"><span class="material-symbols-outlined">history</span> Lịch sử chạy</a>
        <?php elseif ($role === 'admin'): ?>
            <a href="/admin/dashboard" class="<?= app_nav_active('/admin/dashboard') ?>"><span class="material-symbols-outlined">admin_panel_settings</span> Tổng quan</a>
            <a href="/admin/orders" class="<?= app_nav_active('/admin/orders') ?>"><span class="material-symbols-outlined">local_shipping</span> Quản lý đơn hàng</a>
            <a href="/admin/users" class="<?= app_nav_active('/admin/users') ?>"><span class="material-symbols-outlined">groups</span> Quản lý người dùng</a>
            <a href="/admin/disputes" class="<?= app_nav_active('/admin/disputes') ?>"><span class="material-symbols-outlined">gavel</span> Khiếu nại</a>
            <a href="/admin/settings" class="<?= app_nav_active('/admin/settings') ?>"><span class="material-symbols-outlined">settings</span> Cài đặt</a>
        <?php endif; ?>
        
        <div class="sidebar-bottom">
            <a href="/profile/<?= $userId ?>" class="<?= app_nav_active('/profile/' . $userId) ?>"><span class="material-symbols-outlined">person</span> Hồ sơ cá nhân</a>
            <a href="/logout" class="logout-btn"><span class="material-symbols-outlined">logout</span> Đăng xuất</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            
            <div style="display: flex; align-items: center; flex: 1;">
                <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="header-search">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" id="global-header-search" value="<?= app_e($globalSearchQuery) ?>" placeholder="Tìm kiếm mã vận đơn, đơn hàng...">
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                
                <div class="dropdown-wrapper" id="notification-dropdown-container">
                    <span class="material-symbols-outlined header-notification" onclick="toggleNotificationDropdown()" title="Thông báo">
                        notifications
                        
                        <span class="notification-badge" style="display: <?= $unreadCount > 0 ? 'flex' : 'none' ?>;">
                            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                        </span>
                    </span>
                    
                    <div id="notification-dropdown-menu" class="notification-dropdown-menu" style="display: none;">
                        <div class="notification-header">
                            <strong>Thông báo</strong>
                            <?php if ($unreadCount > 0): ?>
                                <a href="/notifications/read" class="notification-mark-read">Đánh dấu đã đọc</a>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="notification-empty">
                                    <span class="material-symbols-outlined">notifications_off</span>
                                    Bạn chưa có thông báo nào.
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <a href="<?= app_e($n['link'] ?: '#') ?>" class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                                        <div class="notification-item-layout">
                                            <div class="notification-icon-wrap <?= !$n['is_read'] ? 'unread' : '' ?>">
                                                <span class="material-symbols-outlined notification-icon <?= !$n['is_read'] ? 'unread' : '' ?>">
                                                    <?= $n['type'] === 'system' ? 'info' : 'local_shipping' ?>
                                                </span>
                                            </div>
                                            <div class="notification-content">
                                                <strong><?= app_e($n['title']) ?></strong>
                                                <p><?= app_e($n['message']) ?></p>
                                                <span><?= app_datetime($n['created_at']) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="/notifications">Xem tất cả thông báo</a>
                        </div>
                    </div>
                </div>

                <div class="dropdown-wrapper" id="nav-dropdown-container">
                    <img src="<?= app_e($displayAvatar) ?>" alt="Nav Avatar" onclick="toggleNavDropdown()" class="nav-avatar-img" title="Tài khoản">
                    
                    <div id="nav-dropdown-menu" class="nav-dropdown-menu" style="display: none;">
                        <div class="nav-dropdown-header">
                            <strong><?= app_e($userName) ?></strong>
                            <span><?= $role === 'driver' ? 'Tài xế' : ($role === 'admin' ? 'Quản trị viên' : 'Khách hàng') ?></span>
                        </div>
                        <div class="nav-dropdown-list">
                            <a href="/profile/<?= $userId ?>" class="nav-dropdown-item">
                                <span class="material-symbols-outlined">person</span> Hồ sơ cá nhân
                            </a>
                            <?php if ($role === 'admin'): ?>
                            <a href="/admin/settings" class="nav-dropdown-item">
                                <span class="material-symbols-outlined">settings</span> Cài đặt hệ thống
                            </a>
                            <?php endif; ?>
                            <div class="nav-dropdown-divider"></div>
                            <a href="/logout" class="nav-dropdown-item danger">
                                <span class="material-symbols-outlined">logout</span> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Bổ sung hàm toggleSidebar cho Mobile
            function toggleSidebar() {
                document.getElementById('app-sidebar').classList.toggle('show');
            }

            function toggleNotificationDropdown() {
                const notifMenu = document.getElementById('notification-dropdown-menu');
                const avatarMenu = document.getElementById('nav-dropdown-menu');
                if (avatarMenu) avatarMenu.style.display = 'none'; // Đóng menu Avatar nếu đang mở
                notifMenu.style.display = (notifMenu.style.display === 'none' || notifMenu.style.display === '') ? 'block' : 'none';
            }

            function toggleNavDropdown() {
                const menu = document.getElementById('nav-dropdown-menu');
                const notifMenu = document.getElementById('notification-dropdown-menu');
                if (notifMenu) notifMenu.style.display = 'none'; // Đóng menu Thông báo nếu đang mở
                menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
            }

            // Tự động đóng menu khi nhấp chuột ra ngoài vùng dropdown
            document.addEventListener('click', function(event) {
                const avatarContainer = document.getElementById('nav-dropdown-container');
                const avatarMenu = document.getElementById('nav-dropdown-menu');
                if (avatarContainer && !avatarContainer.contains(event.target) && avatarMenu) {
                    avatarMenu.style.display = 'none';
                }

                const notifContainer = document.getElementById('notification-dropdown-container');
                const notifMenu = document.getElementById('notification-dropdown-menu');
                if (notifContainer && !notifContainer.contains(event.target) && notifMenu) {
                    notifMenu.style.display = 'none';
                }
            });

            // Xử lý sự kiện tìm kiếm/tra cứu ở Header
            document.getElementById('global-header-search')?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (!query) return;
                    
                    const role = '<?= $role ?>';
                    if (role === 'admin') {
                        window.location.href = '/admin/orders?search=' + encodeURIComponent(query);
                    } else {
                        window.location.href = '/tracking?code=' + encodeURIComponent(query);
                    }
                }
            });
        </script>

        <div class="content-area">
            <div class="breadcrumb-container">
                <a href="/" class="breadcrumb-item">Trang chủ</a>
                <?php 
                    $roleName = 'Khách hàng';
                    if ($role === 'admin') $roleName = 'Quản trị viên';
                    elseif ($role === 'driver') $roleName = 'Tài xế';
                ?>
                <span class="breadcrumb-item"><?= $roleName ?></span>
                <span class="breadcrumb-item active"><?= app_e($pageTitle ?? 'Tổng quan') ?></span>
            </div>