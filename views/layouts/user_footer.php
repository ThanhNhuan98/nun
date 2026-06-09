        </div> <!-- End content-area -->
        
        <footer class="app-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <p style="margin: 0;">&copy; <?= date('Y') ?> NUN Express. All rights reserved.</p>
            <div class="footer-hotline" style="font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 18px;">support_agent</span> 
                <span>Hotline CSKH:</span>
                <a href="tel:19009999" style="color: var(--primary); font-weight: 600; text-decoration: none;">1900 9999</a>
            </div>
        </footer>
    </div> <!-- End main-content -->
    
    <!-- Thư viện Pusher JS cho tính năng Real-time -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        <?php if (app_current_user('role') === 'driver'): ?>
        // Lắng nghe sự kiện Radar qua WebSockets để tự động tải lại đơn mà không cần F5
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy Key từ cấu hình (Hoặc bạn có thể dán cứng chuỗi Key Pusher của bạn vào đây)
            const pusherKey = '<?= $_ENV['PUSHER_APP_KEY'] ?? '' ?>';
            const pusherCluster = '<?= $_ENV['PUSHER_APP_CLUSTER'] ?? 'ap1' ?>';
            
            if (typeof Pusher !== 'undefined' && pusherKey) {
                const pusher = new Pusher(pusherKey, { cluster: pusherCluster });
                const radarChannel = pusher.subscribe('driver-radar');

                // Lắng nghe sự kiện 1: Khách hàng vừa tạo xong đơn mới
                radarChannel.bind('new_order_pending', function(data) {
                    if (window.location.pathname.includes('/driver/receive-orders')) {
                        window.location.reload(); // Tự động load lại Radar / Chạy lại AI
                    } else if (typeof showToast === 'function') {
                        showToast('📢 Radar: Có đơn hàng mới tại khu vực của bạn!', 'success');
                    }
                });

                // Lắng nghe sự kiện 2: Có tài xế khác đã nhanh tay nhận chuyến ghép
                radarChannel.bind('order_taken', function(data) {
                    if (window.location.pathname.includes('/driver/receive-orders')) {
                        window.location.reload(); // Tự động load lại để xóa chuyến ghép đã bị nhận mất
                    }
                });
            }
        });
        <?php endif; ?>

        // Tự động xử lý hiển thị cảnh báo lỗi (Validation) HTML5 cho mọi input có thuộc tính data-error trên toàn hệ thống.
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-error]').forEach(function(input) {
                input.addEventListener('invalid', function() { this.setCustomValidity(this.getAttribute('data-error')); });
                input.addEventListener('input', function() { this.setCustomValidity(''); });
                const clearValidity = function() { this.setCustomValidity(''); };
                input.addEventListener('input', clearValidity);
                input.addEventListener('change', clearValidity);
            });
        });
    </script>
</body>
</html>
