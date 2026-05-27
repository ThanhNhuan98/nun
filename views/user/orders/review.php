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

<div class="admin-container">
    <div class="review-card-v2">
        
        <?php if (!empty($flashMessage)): ?>
            <div style="background: var(--success-light); color: var(--success); padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                <?= app_e($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: var(--danger-light); color: var(--danger); padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                <?= app_e($error) ?>
            </div>
            <div style="text-align: center;"><a href="/user/orders/track/<?= app_e($order['tracking_code'] ?? '') ?>" style="color: var(--primary);">Quay lại đơn hàng</a></div>
            
        <?php elseif (isset($existingReview) && $existingReview): ?>
            <div style="text-align: center; padding: 30px 15px; background: #f0fdf4; border-radius: 4px; border: 1px solid #bbf7d0;">
                <span class="material-symbols-outlined" style="font-size: 44px; color: var(--success); display: block; margin-bottom: 8px;">check_circle</span>
                <strong style="color: #065f46; font-size: 16px;">Bạn đã đánh giá chuyến đi này!</strong>
                <div style="margin: 12px 0; color: #f59e0b; font-size: 20px;">
                    <?= str_repeat('★', $existingReview['rating']) ?><?= str_repeat('☆', 5 - $existingReview['rating']) ?>
                </div>
                <p style="color: #334155; font-size: 14px; margin: 0; font-style: italic;">"<?= app_e($existingReview['comment']) ?>"</p>
            </div>
            <div style="text-align: center; margin-top: 24px;">
                <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-submit-primary" style="text-decoration: none; padding: 10px 24px;">Quay lại theo dõi đơn</a>
            </div>
            
        <?php else: ?>
            <div class="review-avatar-container">
                <img src="<?= app_e($driverAvatar) ?>" alt="Avatar">
                <div class="review-driver-name"><?= app_e($order['driver_name']) ?></div>
                <div class="review-driver-meta">
                    Tài xế giao hàng &bull; Biển số: <?= app_e($order['driver_license_plate'] ?? 'Chưa cập nhật') ?>
                </div>
            </div>

            <form action="/user/orders/review/<?= app_e($order['id']) ?>" method="POST">
                <input type="hidden" name="driver_id" value="<?= app_e($order['driver_id']) ?>">
                
                <div class="review-question-text">Bạn cảm thấy chuyến giao hàng thế nào?</div>
                
                <div class="star-rating-group">
                    <label>★<input type="radio" name="rating" value="1"></label>
                    <label>★<input type="radio" name="rating" value="2"></label>
                    <label>★<input type="radio" name="rating" value="3"></label>
                    <label>★<input type="radio" name="rating" value="4"></label>
                    <label>★<input type="radio" name="rating" value="5" checked></label>
                </div>

                <div class="tag-selection-group">
                    <label class="tag-pill">
                        <input type="checkbox" name="tags[]" value="Giao đúng giờ"> Giao đúng giờ
                    </label>
                    <label class="tag-pill">
                        <input type="checkbox" name="tags[]" value="Tài xế thân thiện"> Tài xế thân thiện
                    </label>
                    <label class="tag-pill">
                        <input type="checkbox" name="tags[]" value="Gói hàng cẩn thận"> Gói hàng cẩn thận
                    </label>
                    <label class="tag-pill">
                        <input type="checkbox" name="tags[]" value="Gọi điện trước"> Gọi điện trước
                    </label>
                </div>

                <div class="review-textarea-group">
                    <label>Nhận xét chi tiết (Không bắt buộc)</label>
                    <textarea name="comment" class="review-textarea" rows="4" placeholder="Chia sẻ thêm về trải nghiệm của bạn..."></textarea>
                </div>

                <div class="review-action-group">
                    <button type="submit" class="btn-submit-review">Gửi đánh giá</button>
                    <a href="/user/orders/track/<?= app_e($order['tracking_code']) ?>" class="btn-skip-review">Bỏ qua</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
