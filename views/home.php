<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= app_e($pageTitle ?? 'Trang chủ - NUN Express') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="landing-page">

    <header class="landing-header">
        <a href="/" class="landing-logo">
            <span class="material-symbols-outlined" style="font-size: 28px;">local_shipping</span>
            NUN Express
        </a>
        <nav class="landing-nav">
            <a href="/" class="active">Trang chủ</a>
            <a href="/tracking">Tra cứu đơn hàng</a>
        </nav>
        
        <div class="header-actions">
            <?php if (!app_current_user('id')): ?>
                <a href="/login" class="btn-login-text">Đăng nhập</a>
                <a href="/register" class="btn-register-solid">Đăng ký</a>
            <?php else: ?>
                <?php 
                    $role = app_current_user('role', 'user');
                    $dashboardLink = '/user/dashboard';
                    if ($role === 'driver') $dashboardLink = '/driver/receive-orders';
                    if ($role === 'admin') $dashboardLink = '/admin/dashboard';
                ?>
                <a href="<?= $dashboardLink ?>" class="btn-register-solid">Hệ thống quản lý</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero-section">
        <div class="hero-hero-left">
            <div class="hero-label">
                <span class="material-symbols-outlined" style="font-size: 16px;">electric_bolt</span> Vận chuyển nhanh chóng
            </div>
            <h1 class="hero-title">Giao hàng siêu tốc -<br>Tiện lợi mọi nơi</h1>
            <p class="hero-desc">Trải nghiệm dịch vụ giao hàng thế hệ mới với công nghệ AI tối ưu lộ trình thông minh, đảm bảo hàng hóa của bạn đến nơi an toàn và nhanh nhất.</p>
            
            <form action="/tracking" method="GET" class="hero-tracking-form">
                <span class="material-symbols-outlined" style="color: var(--text-muted); margin-left: 12px; margin-top: 12px;">search</span>
                <input type="text" name="code" placeholder="Nhập mã vận đơn (VD: NUN123456)" required autocomplete="off">
                <button type="submit">Tra cứu</button>
            </form>

            <div class="hero-social-proof">
                <div class="hero-avatars">
                    <img src="https://i.pravatar.cc/100?img=1" alt="User">
                    <img src="https://i.pravatar.cc/100?img=2" alt="User">
                    <img src="https://i.pravatar.cc/100?img=3" alt="User">
                </div>
                Hơn 10,000+ đối tác tin dùng
            </div>
        </div>

        <div class="hero-image-wrapper">
            <img src="https://images.unsplash.com/photo-1580674285054-bed31e145f59?q=80&w=1470&auto=format&fit=crop" alt="NUN Express Delivery Van">
            <div class="floating-badge">
                <div class="floating-badge-icon">
                    <span class="material-symbols-outlined">rocket_launch</span>
                </div>
                <div class="floating-badge-text">
                    <strong>15 <span style="font-size: 14px;">phút</span></strong>
                    <span>Thời gian nhận hàng trung bình</span>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-section">
        <div class="partner-logo">PARTNER ONE</div>
        <div class="partner-logo">GLOBAL LOGISTICS</div>
        <div class="partner-logo">TECH EXPRESS</div>
        <div class="partner-logo">ECO DELIVERY</div>
    </section>

    <section class="features-section">
        <h2 class="section-title">Giải pháp Vận chuyển Toàn diện</h2>
        <p class="section-desc">Chúng tôi áp dụng công nghệ tiên tiến nhất để đảm bảo mỗi kiện hàng đều được giao đến đúng người, đúng lúc.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="f-icon"><span class="material-symbols-outlined">bolt</span></div>
                <h3 class="f-title">Giao hàng nhanh</h3>
                <p class="f-desc">Mạng lưới đối tác rộng khắp giúp lấy và giao hàng ngay lập tức. Đáp ứng mọi nhu cầu khẩn cấp.</p>
            </div>
            <div class="feature-card">
                <div class="f-icon"><span class="material-symbols-outlined">route</span></div>
                <h3 class="f-title">AI tối ưu lộ trình</h3>
                <p class="f-desc">Hệ thống trí tuệ nhân tạo phân tích giao thông thực tế để đề xuất tuyến đường ngắn và an toàn nhất cho tài xế.</p>
            </div>
            <div class="feature-card">
                <div class="f-icon"><span class="material-symbols-outlined">verified_user</span></div>
                <h3 class="f-title">An toàn & Minh bạch</h3>
                <p class="f-desc">Theo dõi đơn hàng theo thời gian thực. Mọi thông tin cước phí và tình trạng vận chuyển đều được công khai rõ ràng.</p>
            </div>
        </div>
    </section>

    <section class="pricing-section">
        <h2 class="section-title">Bảng giá cước phí tham khảo</h2>
        <p class="section-desc">Lựa chọn gói dịch vụ phù hợp nhất với nhu cầu vận chuyển của bạn. Minh bạch, không phí ẩn.</p>
        
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3 class="p-title">Giao hàng cơ bản</h3>
                <div class="p-subtitle">Phù hợp cho nhu cầu hàng ngày</div>
                <div class="p-price">20k <span>/đơn đầu tiên</span></div>
                <ul class="p-list">
                    <li><span class="material-symbols-outlined">check_circle</span> Thời gian: 30-60 phút</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Phí km tiếp theo: 5k/km</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Khối lượng tối đa: 10kg</li>
                </ul>
                <a href="/register" class="btn-pricing-outline">Chọn gói cơ bản</a>
            </div>

            <div class="pricing-card highlight">
                <div class="pricing-badge-top">⭐ Lựa chọn phổ biến nhất</div>
                <h3 class="p-title">Giao hàng siêu tốc</h3>
                <div class="p-subtitle">Dành cho tài liệu, hàng hóa khẩn cấp</div>
                <div class="p-price">25k <span>/đơn đầu tiên</span></div>
                <ul class="p-list">
                    <li><span class="material-symbols-outlined">check_circle</span> Thời gian: 15-30 phút</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Phí km tiếp theo: 6k/km</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Khối lượng tối đa: 20kg</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Ưu tiên phân bổ tài xế bằng AI</li>
                </ul>
                <a href="/register" class="btn-pricing-solid">Chọn siêu tốc</a>
            </div>

            <div class="pricing-card">
                <h3 class="p-title">Hàng cồng kềnh</h3>
                <div class="p-subtitle">Dành cho đồ nội thất, thiết bị lớn</div>
                <div class="p-price">40k <span>/đơn đầu tiên</span></div>
                <ul class="p-list">
                    <li><span class="material-symbols-outlined">check_circle</span> Thời gian: 30-60 phút</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Phí km tiếp theo: 8k/km</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Khối lượng tối đa: 50kg</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Điều xe chuyên dụng (Bán tải/Tải nhỏ)</li>
                </ul>
                <a href="/register" class="btn-pricing-outline">Chọn giao hàng lớn</a>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-box">
            <h2 class="cta-title">Bạn cần gửi hàng ngay bây giờ?</h2>
            <p class="cta-desc">Tham gia cùng hàng ngàn khách hàng cá nhân và doanh nghiệp đang trải nghiệm dịch vụ giao hàng thông minh, minh bạch và nhanh chóng nhất.</p>
            
            <?php if (!app_current_user('id')): ?>
                <a href="/login" class="btn-register-solid" style="padding: 14px 32px;">Đăng nhập để Bắt đầu</a>
            <?php else: ?>
                <a href="/user/orders/create" class="btn-register-solid" style="padding: 14px 32px; display: inline-flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">add_box</span> Tạo đơn hàng ngay
                </a>
            <?php endif; ?>
        </div>
    </section>

    <footer class="landing-footer">
        <div>
            <div class="f-logo">
                <span class="material-symbols-outlined">local_shipping</span> NUN Express
            </div>
            <div>&copy; <?= date('Y') ?> NUN Express. All rights reserved.</div>
        </div>
        <div class="landing-footer-links">
            <a href="#">Điều khoản dịch vụ</a>
            <a href="#">Chính sách bảo mật</a>
            <a href="#">Liên hệ</a>
        </div>
    </footer>

</body>
</html>