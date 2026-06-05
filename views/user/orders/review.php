<?php
/**
 * @var string $pageTitle
 * @var string $flashMessage
 * @var string $error
 * @var array $order
 * @var array|bool|null $existingReview
 * @var string $driverAvatar
 * @var array $ratingInfo
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<style>
    .review-card-pro { background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; border: 1px solid var(--border-color); }
    .review-center { text-align: center; }
    .review-header-back { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text-muted); font-weight: 500; text-decoration: none; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; transition: 0.2s; }
    .review-header-back:hover { color: var(--text-main); border-color: #cbd5e1; background: #f8fafc; }
    
    .review-avatar-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 32px; }
    .review-avatar-wrap { position: relative; width: 88px; height: 88px; margin-bottom: 12px; }
    .review-avatar-lg { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-light); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .review-verified-badge { position: absolute; bottom: 0; right: 0; background: var(--primary); color: #fff; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    
    .review-driver-name { font-size: 20px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
    .review-driver-meta { font-size: 14px; color: var(--text-muted); }
    
    .review-question-text { text-align: center; font-weight: 600; margin-bottom: 16px; font-size: 16px; color: var(--text-main); }
    
    .review-stars-input { display: flex; flex-direction: row-reverse; justify-content: center; gap: 8px; margin-bottom: 32px; }
    .review-stars-input input { display: none; }
    .review-stars-input label { font-size: 44px; color: var(--star-inactive); cursor: pointer; transition: color 0.2s; }
    .review-stars-input input:checked ~ label, .review-stars-input label:hover, .review-stars-input label:hover ~ label { color: var(--star-active); }
    
    .review-tags-section { margin-bottom: 24px; }
    .review-tags-label { font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px; color: var(--text-main); }
    .review-tags-hint { font-size: 12px; color: var(--text-muted); font-weight: normal; }
    .review-tags-grid { display: flex; flex-wrap: wrap; gap: 10px; }
    .tag-pill-v2 { border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 20px; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s; color: var(--text-muted); background: #f8fafc; font-weight: 500; }
    .tag-pill-v2:hover { border-color: #cbd5e1; background: #f1f5f9; }
    .tag-pill-v2.active { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
    .tag-pill-v2.active .material-symbols-outlined { color: var(--primary); }
    .tag-pill-v2 input { display: none; }
    .tag-pill-v2 .material-symbols-outlined { font-size: 18px; }
    
    .review-textarea-group { margin-bottom: 32px; }
    .review-textarea-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
    .review-textarea-pro { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; font-size: 14px; font-family: inherit; resize: vertical; outline: none; transition: 0.2s; background: #f8fafc; line-height: 1.5; }
    .review-textarea-pro:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); background: #fff; }
    
    .review-action-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; }
    
    .review-success-icon { font-size: 72px; color: var(--success); margin-bottom: 16px; }
    .review-success-title { font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 16px; }
    .review-stars-readonly { font-size: 32px; color: var(--star-active); margin-bottom: 24px; display: flex; justify-content: center; gap: 4px; }
    .review-star-muted { color: var(--star-inactive); }
    .review-comment-box { background: #f8fafc; padding: 20px; border-radius: 8px; font-style: italic; color: var(--text-muted); margin-bottom: 32px; border: 1px solid var(--border-color); font-size: 15px; line-height: 1.6; }
    .review-back-wide { display: block; width: 100%; text-align: center; text-decoration: none; padding: 12px; }
    
    .review-error-actions { max-width: 600px; margin: 24px auto 0; }
    
    .alert-banner { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border-radius: 8px; margin-bottom: 24px; max-width: 600px; margin-left: auto; margin-right: auto; }
    .alert-banner.danger { background: var(--danger-light); border: 1px solid #fecaca; color: #991b1b; }
    .alert-banner-icon { font-size: 24px; color: var(--danger); }
    .alert-banner-content h4 { margin: 0 0 4px 0; font-size: 16px; }
    .alert-banner-content p { margin: 0; font-size: 14px; }
    
    @media (max-width: 640px) {
        .review-action-grid { grid-template-columns: 1fr; }
        .review-card-pro { padding: 20px; border-radius: 0; border-left: none; border-right: none; }
    }
</style>

<div class="admin-container">
    <div class="user-page-header">
        <div>
            <h2 class="user-page-title">Đánh giá chuyến đi</h2>
            <p class="user-page-subtitle">Đơn hàng #<?= app_e($order['tracking_code']) ?></p>
        </div>
        <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-cancel review-header-back">
            <span class="material-symbols-outlined icon-sm">arrow_back</span> Quay lại
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-banner danger">
            <span class="material-symbols-outlined alert-banner-icon">error</span>
            <div class="alert-banner-content">
                <h4>Lỗi đánh giá</h4>
                <p><?= app_e($error) ?></p>
            </div>
        </div>
        <div class="review-error-actions">
            <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-submit">Quay lại đơn hàng</a>
        </div>
    <?php elseif (isset($existingReview) && $existingReview): ?>
        <div class="review-card-pro review-center">
            <span class="material-symbols-outlined review-success-icon">check_circle</span>
            <h3 class="review-success-title">Bạn đã đánh giá chuyến đi này!</h3>
            <div class="star-rating-group review-stars-readonly">
                <?php for($i = 0; $i < $existingReview['rating']; $i++) echo '<label>★</label>'; ?>
                <?php for($i = 0; $i < 5 - $existingReview['rating']; $i++) echo '<label class="review-star-muted">★</label>'; ?>
            </div>
            <?php if (!empty($existingReview['comment'])): ?>
                <div class="review-comment-box">
                    "<?= app_e($existingReview['comment']) ?>"
                </div>
            <?php endif; ?>
            <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-submit review-back-wide">Quay lại theo dõi đơn</a>
        </div>
    <?php else: ?>
        <div class="review-card-pro">
            <div class="review-avatar-container clean">
                <div class="review-avatar-wrap">
                    <img src="<?= app_e($driverAvatar) ?>" alt="Avatar" class="review-avatar-lg">
                    <div class="review-verified-badge">
                        <span class="material-symbols-outlined icon-xs">verified</span>
                    </div>
                </div>
                <div class="review-driver-name prominent"><?= app_e($order['driver_name']) ?></div>
                <div class="review-driver-meta prominent">Biển số: <strong><?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?></strong></div>
            </div>

            <form action="/user/orders/review/<?= app_e($order['id']) ?>" method="POST">
                <input type="hidden" name="driver_id" value="<?= app_e($order['driver_id']) ?>">
                
                <div class="review-question-text">Bạn đánh giá trải nghiệm giao hàng này như thế nào?</div>
                
                <div class="star-rating-group review-stars-input">
                    <input type="radio" name="rating" id="star5" value="5" checked>
                    <label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1">
                    <label for="star1">★</label>
                </div>

                <div class="review-tags-section">
                    <label class="review-tags-label">Điều gì làm bạn hài lòng? <span class="review-tags-hint">(Tùy chọn)</span></label>
                    <div class="review-tags-grid">
                        <label class="tag-pill-v2"><input type="checkbox" name="tags[]" value="Giao đúng giờ"> <span class="material-symbols-outlined">schedule</span> Giao đúng giờ</label>
                        <label class="tag-pill-v2"><input type="checkbox" name="tags[]" value="Tài xế thân thiện"> <span class="material-symbols-outlined">sentiment_satisfied</span> Tài xế thân thiện</label>
                        <label class="tag-pill-v2"><input type="checkbox" name="tags[]" value="Gói hàng cẩn thận"> <span class="material-symbols-outlined">inventory_2</span> Gói hàng cẩn thận</label>
                        <label class="tag-pill-v2"><input type="checkbox" name="tags[]" value="Gọi điện trước"> <span class="material-symbols-outlined">call</span> Gọi điện trước</label>
                    </div>
                </div>

                <div class="review-textarea-group">
                    <label>Nhận xét thêm</label>
                    <textarea name="comment" class="review-textarea-pro" rows="4" placeholder="Chia sẻ thêm về trải nghiệm của bạn với tài xế..."></textarea>
                </div>

                <div class="review-action-grid">
                    <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-cancel">Bỏ qua</a>
                    <button type="submit" class="btn-submit">Gửi đánh giá</button>
                </div>
            </form>
        </div>

        <script>
            document.querySelectorAll('.tag-pill-v2 input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', function() {
                    if (this.checked) {
                        this.parentElement.classList.add('active');
                    } else {
                        this.parentElement.classList.remove('active');
                    }
                });
            });
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
