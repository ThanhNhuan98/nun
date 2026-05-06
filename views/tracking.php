<?php
/**
 * @var array $order
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Tra cứu vận đơn') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tải thư viện Bản đồ Leaflet và OSRM Routing -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Hiệu ứng xoay vòng cho icon loading */
        @keyframes spin { 
            100% { transform: rotate(360deg); } 
        }
        .icon-spin { 
            animation: spin 1s linear infinite; 
            display: inline-block;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .home-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .home-header-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 22px;
            font-weight: 800;
            color: #2A59EB;
            text-decoration: none;
        }
        .home-header-nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        .home-header-nav a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .home-header-nav a:hover {
            color: #2A59EB;
        }
        .btn-header-login {
            background: #2A59EB;
            color: #fff !important;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600 !important;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-header-login:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px);
        }
        .tracking-container {
            flex: 1; /* Tự động giãn vùng nội dung để đẩy footer xuống đáy */
            padding-top: 100px; /* Bù trừ khoảng trống bị Header cố định che khuất */
        }
        .public-footer {
            background: #0F172A;
            color: #94A3B8;
            text-align: center;
            padding: 24px 20px;
            font-size: 14px;
            margin-top: 60px;
        }
        .tracking-page-title {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 30px;
        }
        .tracking-search-box {
            display: flex;
            max-width: 600px;
            margin: 0 auto 40px auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .tracking-search-input {
            flex: 1;
            padding: 16px 24px;
            border: none;
            outline: none;
            font-size: 16px;
            font-family: inherit;
            color: #1e293b;
        }
        .tracking-search-btn {
            background: #2A59EB;
            color: white;
            border: none;
            padding: 0 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .tracking-search-btn:hover {
            background: #1d4ed8;
        }
        .tracking-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .tracking-error-card {
            text-align: center;
            padding: 60px 20px;
        }
        .tracking-error-icon {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        .tracking-error-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
        }
        .tracking-error-text {
            color: #64748b;
            font-size: 15px;
            margin: 0;
        }
        .tracking-order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .tracking-order-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 5px 0;
        }
        .tracking-order-time {
            color: #64748b;
            font-size: 14px;
        }
        .tracking-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .tracking-info-box {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }
        .tracking-info-label {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .tracking-info-value {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .tracking-info-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }
        .tracking-history-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 20px 0;
        }
        .tracking-history-empty {
            color: #64748b;
            font-size: 15px;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .tracking-info-grid { grid-template-columns: 1fr; }
            .tracking-search-box { flex-direction: column; }
            .tracking-search-btn { padding: 16px; justify-content: center; }
        }
    </style>
</head>
<body>

<header class="home-header">
    <a href="/" class="home-header-logo">
        <span class="material-symbols-outlined" style="font-size: 28px;">local_shipping</span>
        NUN Express
    </a>
    <nav class="home-header-nav">
        <a href="/"><span class="material-symbols-outlined" style="font-size: 20px;">home</span> Trang chủ</a>
        <a href="/tracking"><span class="material-symbols-outlined" style="font-size: 20px;">search</span> Tra cứu đơn hàng</a>
        <?php if (!app_current_user('id')): ?>
            <a href="/login" class="btn-header-login">Đăng nhập / Đăng ký</a>
        <?php else: ?>
            <?php 
                $role = app_current_user('role', 'user');
                $dashboardLink = '/user/dashboard';
                if ($role === 'driver') $dashboardLink = '/driver/receive-orders';
                if ($role === 'admin') $dashboardLink = '/admin/dashboard';
            ?>
            <a href="<?= $dashboardLink ?>" class="btn-header-login">Hệ thống quản lý</a>
        <?php endif; ?>
    </nav>
</header>

<div class="tracking-container">
    <h2 class="tracking-page-title">Tra cứu hành trình đơn hàng</h2>
    
    <form action="/tracking" method="GET" class="tracking-search-box" id="trackingForm">
        <input type="text" name="code" value="<?= app_e(str_replace('#', '', $code ?? '')) ?>" class="tracking-search-input" placeholder="Nhập mã vận đơn của bạn (VD: NUN123456)..." required oninput="this.value = this.value.replace('#', '').trim()">
        <button type="submit" class="tracking-search-btn"><span class="material-symbols-outlined">search</span> Tra cứu</button>
    </form>

    <div id="tracking-result-area">
    <?php if (!empty($code)): ?>
        <?php if (!$order): ?>
            <div class="tracking-card tracking-error-card">
                <span class="material-symbols-outlined tracking-error-icon">search_off</span>
                <h3 class="tracking-error-title">Không tìm thấy đơn hàng!</h3>
                <p class="tracking-error-text">Mã vận đơn <strong><?= app_e(str_replace('#', '', $code)) ?></strong> không tồn tại hoặc đã bị xóa. Vui lòng kiểm tra lại.</p>
            </div>
        <?php else: ?>
            <div id="tracking-map-data" 
                 data-status="<?= $order['status'] ?>" 
                 data-code="<?= $order['tracking_code'] ?>"
                 data-slat="<?= (float)($order['sender_lat'] ?? 0) ?>" 
                 data-slng="<?= (float)($order['sender_lng'] ?? 0) ?>"
                 data-rlat="<?= (float)($order['receiver_lat'] ?? 0) ?>" 
                 data-rlng="<?= (float)($order['receiver_lng'] ?? 0) ?>"
                 data-dlat="<?= (float)($order['driver_lat'] ?? 0) ?>" 
                 data-dlng="<?= (float)($order['driver_lng'] ?? 0) ?>">
            </div>
            <div class="tracking-card">
                <div class="tracking-order-header">
                    <div>
                        <h3 class="tracking-order-title">Đơn hàng #<?= app_e($order['tracking_code']) ?></h3>
                        <span class="tracking-order-time">Hẹn lấy hàng lúc <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        <?php
                            $sMethod = $order['shipping_method'] ?? 'standard';
                            $sColor = \App\Models\Order::getShippingMethodColor($sMethod);
                            $sBg = '#e2e8f0';
                            if ($sMethod === 'express') $sBg = '#fee2e2'; // Nền đỏ nhạt
                            elseif ($sMethod === 'fast') $sBg = '#fef3c7'; // Nền cam nhạt
                        ?>
                        <span style="display: inline-block; margin-left: 10px; background: <?= $sBg ?>; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: <?= $sColor ?>; font-weight: 700;">
                            <?= \App\Models\Order::getShippingMethodLabel($order['shipping_method'] ?? 'standard') ?>
                        </span>
                    </div>
                    <div>
                        <span class="<?= app_status_class($order['status']) ?>" style="font-size: 14px; padding: 6px 12px;">
                            <?= app_e(\App\Models\Order::getStatusLabel($order['status'])) ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($order['driver_name'])): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0;">
                        <?php
                        $dAvatarUrl = app_avatar_url($order['driver_avatar'] ?? '', $order['driver_name'] ?? 'Driver');
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                        
                        if (empty($dAvatarUrl) || strpos($dAvatarUrl, 'default-avatar.png') !== false) {
                            $dAvatarUrl = $basePath . '/assets/images/default-avatar.png';
                        } elseif (strpos($dAvatarUrl, '/') === 0 && strpos($dAvatarUrl, $basePath) !== 0) {
                            // Nếu dùng XAMPP có thư mục con, tự động nối thêm thư mục gốc vào đường dẫn
                            $dAvatarUrl = $basePath . $dAvatarUrl;
                        }
                        ?>
                        <img src="<?= htmlspecialchars($dAvatarUrl) ?>" alt="Driver Avatar" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6;">
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #0f172a; font-size: 15px;">Tài xế: <?= app_e($order['driver_name']) ?></h4>
                            <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">Biển số: <?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?></div>
                            <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: #475569; font-weight: 500;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">local_shipping</span> 
                                Đang phụ trách đơn hàng của bạn
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="tracking-info-grid" style="margin-top: 20px;">
                    <div class="tracking-info-box">
                        <div class="tracking-info-label">NGƯỜI GỬI</div>
                        <div class="tracking-info-value"><?= app_e($order['sender_name']) ?></div>
                        <div class="tracking-info-desc"><?= app_e($order['sender_address']) ?></div>
                    </div>
                    <div class="tracking-info-box">
                        <div class="tracking-info-label">NGƯỜI NHẬN</div>
                        <div class="tracking-info-value"><?= app_e($order['receiver_name']) ?></div>
                        <div class="tracking-info-desc"><?= app_e($order['receiver_address']) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($order['sender_lat']) && !empty($order['receiver_lat'])): ?>
            <div class="tracking-card" style="padding: 20px; margin-top: 20px;">
                <h3 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 5px;">
                    <span class="material-symbols-outlined">map</span> Bản đồ trực tiếp
                </h3>
                <div id="route-map" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;"></div>
            </div>
            <?php endif; ?>

            <div class="tracking-card" style="margin-top: 20px;">
                <h4 class="tracking-history-title">Lịch sử hành trình</h4>
                <div>
                    <?php if (!empty($history)): ?>
                        <div class="timeline" style="padding: 10px 0;">
                        <?php foreach (array_reverse($history) as $index => $item): ?>
                            <div class="timeline-item" style="position: relative; padding-left: 30px; margin-bottom: 20px;">
                                <div class="timeline-dot" style="position: absolute; left: 0; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: <?= $index === 0 ? '#3b82f6' : '#cbd5e1' ?>; border: 3px solid <?= $index === 0 ? '#bfdbfe' : '#f1f5f9' ?>;"></div>
                                <div class="timeline-status" style="color: <?= app_e(\App\Models\Order::getStatusColor($item['status'])) ?>; font-weight: bold; font-size: 15px; margin-bottom: 4px;">
                                    <?= app_e(\App\Models\Order::getStatusLabel($item['status'])) ?>
                                </div>
                                <div class="timeline-time" style="color: #64748b; font-size: 13px; margin-bottom: 4px;">
                                    <?= date('H:i - d/m/Y', strtotime($item['created_at'])) ?>
                                </div>
                                <div class="timeline-description" style="color: #475569; font-size: 14px; line-height: 1.5;">
                                    <?= $item['description'] ?>
                                </div>
                                <?php if ($index !== count($history) - 1): ?>
                                    <div style="position: absolute; left: 5px; top: 20px; bottom: -20px; width: 2px; background: #e2e8f0;"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="tracking-history-empty">Chưa có lịch sử cập nhật nào.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<footer class="public-footer">
    <p style="margin: 0;">&copy; <?= date('Y') ?> NUN Express. All rights reserved.</p>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="/assets/js/map-helper.js"></script>
<script>
let myMapHelper = null;

function destroyTrackingMap() {
    if (myMapHelper) {
        myMapHelper.destroy();
        myMapHelper = null;
    }
}

function initTrackingMap() {
    const mapDataEl = document.getElementById('tracking-map-data');
    const mapContainer = document.getElementById('route-map');
    
    destroyTrackingMap(); // Dọn dẹp bản đồ cũ (nếu có) trước khi khởi tạo mới

    if (!mapDataEl || !mapContainer) return;

    myMapHelper = new MapHelper('route-map');
    myMapHelper.initTracking({
        orderStatus: mapDataEl.dataset.status,
        trackingCode: mapDataEl.dataset.code,
        driverLat: parseFloat(mapDataEl.dataset.dlat) || 0,
        driverLng: parseFloat(mapDataEl.dataset.dlng) || 0,
        senderLat: parseFloat(mapDataEl.dataset.slat) || 0,
        senderLng: parseFloat(mapDataEl.dataset.slng) || 0,
        receiverLat: parseFloat(mapDataEl.dataset.rlat) || 0,
        receiverLng: parseFloat(mapDataEl.dataset.rlng) || 0
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('trackingForm');
    
    window.addEventListener('popstate', () => {
        window.location.reload();
    });

    initTrackingMap(); // Khởi tạo map nếu tải trang trực tiếp bằng Link có mã vận đơn

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = form.querySelector('button');
        const originalBtnHtml = btn.innerHTML;
        
        // Tự động loại bỏ dấu # và khoảng trắng thừa nếu người dùng vô tình nhập
        const inputEl = form.querySelector('input[name="code"]');
        const code = inputEl.value.trim().replace(/^#/, '');
        inputEl.value = code; // Cập nhật lại UI input cho người dùng thấy
        
        // Hiển thị hiệu ứng loading
        btn.innerHTML = '<span class="material-symbols-outlined icon-spin">autorenew</span> Đang tìm...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';

        try {
            const url = '/tracking?code=' + encodeURIComponent(code);
            const response = await fetch(url);
            const html = await response.text();
            
            // Parse (Phân tích) HTML trả về từ server
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newResult = doc.querySelector('#tracking-result-area');
            if (newResult) {
                // Hủy bản đồ cũ trước khi DOM bị thay thế để ngăn rò rỉ bộ nhớ
                destroyTrackingMap(); 
                document.getElementById('tracking-result-area').innerHTML = newResult.innerHTML;
                initTrackingMap(); // Re-render bản đồ sau khi trích xuất kết quả AJAX
            }
            
            window.history.pushState({code: code}, '', url);
        } catch (error) {
            alert('Có lỗi xảy ra trong quá trình tra cứu. Vui lòng thử lại!');
        } finally {
            // Khôi phục lại trạng thái ban đầu của nút bấm
            btn.innerHTML = originalBtnHtml;
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
        }
    });
});
</script>
</body>
</html>