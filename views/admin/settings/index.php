<?php require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<div class="admin-container">
    
    <div class="admin-page-header" style="margin-bottom: 30px;">
        <h2 class="admin-page-title" style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            Cài đặt hệ thống
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">
            Quản lý các tham số vận hành dùng chung cho toàn bộ hệ thống.
        </p>
    </div>

    <form action="/admin/settings" method="POST">
        <div class="settings-layout">
            
            <div class="settings-form-card">
                
                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="platform_fee_percent">Tỷ lệ phí nền tảng (%)</label>
                        <p>Phần trăm cước vận chuyển mà hệ thống sẽ thu từ tài xế cho mỗi đơn hàng thành công.</p>
                    </div>
                    <div class="settings-input-col">
                        <div class="input-suffix-group">
                            <input type="number" id="platform_fee_percent" name="platform_fee_percent" class="form-control" min="0" max="100" step="0.1" value="<?= app_e($settings['platform_fee_percent'] ?? '20') ?>" required>
                            <span class="suffix">%</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="max_order_weight">Giới hạn cân nặng tối đa/đơn (kg)</label>
                        <p>Mức cân nặng lớn nhất mà khách hàng có thể nhập khi tạo 1 đơn hàng mới.</p>
                    </div>
                    <div class="settings-input-col">
                        <div class="input-suffix-group">
                            <input type="number" id="max_order_weight" name="max_order_weight" class="form-control" min="1" step="0.1" value="<?= app_e($settings['max_order_weight'] ?? '100') ?>" required>
                            <span class="suffix">kg</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="default_max_total_weight">Giới hạn tải trọng tối đa mặc định (kg)</label>
                        <p>Mức tổng tải trọng tối đa được gán mặc định cho các tài xế khi hệ thống tạo mới hồ sơ tài xế.</p>
                    </div>
                    <div class="settings-input-col">
                        <div class="input-suffix-group">
                            <input type="number" id="default_max_total_weight" name="default_max_total_weight" class="form-control" min="1" step="0.1" value="<?= app_e($settings['default_max_total_weight'] ?? '100') ?>" required>
                            <span class="suffix">kg</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="default_max_concurrent_orders">Giới hạn số đơn nhận đồng thời mặc định</label>
                        <p>Số đơn hàng tối đa tài xế có thể ôm cùng lúc được gán mặc định khi tạo mới hồ sơ tài xế.</p>
                    </div>
                    <div class="settings-input-col">
                        <input type="number" id="default_max_concurrent_orders" name="default_max_concurrent_orders" class="form-control" min="1" step="1" value="<?= app_e($settings['default_max_concurrent_orders'] ?? '3') ?>" required style="text-align: right;">
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="max_orders_per_batch">Số đơn ghép tối đa của AI (đơn/chuyến)</label>
                        <p>Giới hạn số lượng đơn hàng tối đa mà AI có thể ghép vào cùng 1 chuyến đi.</p>
                    </div>
                    <div class="settings-input-col">
                        <input type="number" id="max_orders_per_batch" name="max_orders_per_batch" class="form-control" min="1" step="1" value="<?= app_e($settings['max_orders_per_batch'] ?? '5') ?>" required style="text-align: right;">
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="fast_max_orders">Giới hạn ghép đơn "Giao nhanh"</label>
                        <p>Số đơn hàng tối đa trong một chuyến đi nếu có chứa ít nhất 1 đơn "Giao nhanh" (để đảm bảo không làm trễ cam kết thời gian).</p>
                    </div>
                    <div class="settings-input-col">
                        <input type="number" id="fast_max_orders" name="fast_max_orders" class="form-control" min="1" step="1" value="<?= app_e($settings['fast_max_orders'] ?? '3') ?>" required style="text-align: right;">
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="no_show_threshold_for_ban">Giới hạn "Vi phạm giao nhận" (Lần)</label>
                        <p>Số lần từ chối gửi/nhận hàng hoặc không liên lạc được tối đa trước khi tài khoản khách hàng bị hệ thống tự động khóa vĩnh viễn.</p>
                    </div>
                    <div class="settings-input-col">
                        <input type="number" id="no_show_threshold_for_ban" name="no_show_threshold_for_ban" class="form-control" min="1" step="1" value="<?= app_e($settings['no_show_threshold_for_ban'] ?? '3') ?>" required style="text-align: right;">
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label>Cước phí: Giao tiêu chuẩn</label>
                        <p>Lần lượt: Giá nền, Giá mỗi kg, Giá mỗi km.</p>
                    </div>
                    <div class="settings-input-col" style="width: auto; display: flex; gap: 10px;">
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_standard_base" class="form-control" min="0" value="<?= app_e($settings['price_standard_base'] ?? '12000') ?>" required>
                            <span class="suffix">đ</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_standard_weight" class="form-control" min="0" value="<?= app_e($settings['price_standard_weight'] ?? '5000') ?>" required>
                            <span class="suffix">đ/kg</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_standard_distance" class="form-control" min="0" value="<?= app_e($settings['price_standard_distance'] ?? '3000') ?>" required>
                            <span class="suffix">đ/km</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label>Cước phí: Giao nhanh</label>
                        <p>Lần lượt: Giá nền, Giá mỗi kg, Giá mỗi km.</p>
                    </div>
                    <div class="settings-input-col" style="width: auto; display: flex; gap: 10px;">
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_fast_base" class="form-control" min="0" value="<?= app_e($settings['price_fast_base'] ?? '18000') ?>" required>
                            <span class="suffix">đ</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_fast_weight" class="form-control" min="0" value="<?= app_e($settings['price_fast_weight'] ?? '6200') ?>" required>
                            <span class="suffix">đ/kg</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_fast_distance" class="form-control" min="0" value="<?= app_e($settings['price_fast_distance'] ?? '3800') ?>" required>
                            <span class="suffix">đ/km</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label>Cước phí: Giao siêu tốc</label>
                        <p>Lần lượt: Giá nền, Giá mỗi kg, Giá mỗi km.</p>
                    </div>
                    <div class="settings-input-col" style="width: auto; display: flex; gap: 10px;">
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_express_base" class="form-control" min="0" value="<?= app_e($settings['price_express_base'] ?? '25000') ?>" required>
                            <span class="suffix">đ</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_express_weight" class="form-control" min="0" value="<?= app_e($settings['price_express_weight'] ?? '7500') ?>" required>
                            <span class="suffix">đ/kg</span>
                        </div>
                        <div class="input-suffix-group" style="width: 120px;">
                            <input type="number" name="price_express_distance" class="form-control" min="0" value="<?= app_e($settings['price_express_distance'] ?? '4800') ?>" required>
                            <span class="suffix">đ/km</span>
                        </div>
                    </div>
                </div>

                <div class="settings-row">
                    <div class="settings-label-col">
                        <label for="vehicle_speed_kmh">Tốc độ xe giả định (km/h)</label>
                        <p>Dùng để tính thời gian di chuyển nếu API Bản đồ gặp sự cố.</p>
                    </div>
                    <div class="settings-input-col">
                        <div class="input-suffix-group">
                            <input type="number" id="vehicle_speed_kmh" name="vehicle_speed_kmh" class="form-control" min="1" step="0.1" value="<?= app_e($settings['vehicle_speed_kmh'] ?? '28.0') ?>" required style="text-align: right;">
                            <span class="suffix">km/h</span>
                        </div>
                    </div>
                </div>

            </div>

            <div>
                <div class="settings-note-box">
                    <div class="settings-note-header">
                        <span class="material-symbols-outlined">info</span> Lưu ý hệ thống
                    </div>
                    <div class="settings-note-desc">
                        Các thay đổi tại đây sẽ được áp dụng ngay lập tức cho toàn bộ các giao dịch và hồ sơ tạo mới trên hệ thống. Đối với các hồ sơ tài xế đã tồn tại, cấu hình riêng lẻ của họ sẽ không bị ghi đè.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-submit" style="width: 100%; justify-content: center; padding: 12px; font-size: 14px;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">save</span> Lưu thay đổi
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>
