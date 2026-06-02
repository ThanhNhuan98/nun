        </div> <!-- End content-area -->
        
        <footer class="app-footer">
            <p style="margin: 0;">&copy; <?= date('Y') ?> NUN Express. All rights reserved.</p>
        </footer>
    </div> <!-- End main-content -->
    
    <script>
        // Tự động xử lý hiển thị cảnh báo lỗi (Validation) HTML5 cho mọi input có thuộc tính data-error trên toàn hệ thống.
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-error]').forEach(function(input) {
                input.addEventListener('invalid', function() { this.setCustomValidity(this.getAttribute('data-error')); });
                input.addEventListener('input', function() { this.setCustomValidity(''); });
            });
        });
    </script>
</body>
</html>
