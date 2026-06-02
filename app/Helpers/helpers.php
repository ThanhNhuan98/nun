<?php

use App\Models\Order;

if (!function_exists('app_e')) {
    // Mã hóa chuỗi đầu ra (chống tấn công XSS) bằng hàm htmlspecialchars.
    function app_e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_money')) {
    // Định dạng số tiền tệ theo chuẩn hiển thị Việt Nam (VD: 100.000 đ).
    function app_money($amount, string $suffix = 'đ'): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.') . $suffix;
    }
}

if (!function_exists('app_datetime')) {
    // Định dạng chuỗi ngày tháng thời gian theo format mong muốn.
    function app_datetime($value, string $format = 'H:i d/m/Y'): string
    {
        if (empty($value)) {
            return '';
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date($format, $timestamp) : '';
    }
}

if (!function_exists('app_current_user')) {
    // Lấy thông tin chi tiết hoặc một trường cụ thể của người dùng đang đăng nhập từ Session.
    function app_current_user(?string $key = null, $default = null)
    {
        $user = $_SESSION['user'] ?? null;
        if ($key === null) {
            return $user;
        }

        return $user[$key] ?? $default;
    }
}

if (!function_exists('app_has_role')) {
    // Kiểm tra xem người dùng hiện tại có thuộc một (hoặc nhiều) vai trò cụ thể hay không.
    function app_has_role($roles): bool
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($_SESSION['user']['role'] ?? null, $roles, true);
    }
}

if (!function_exists('app_flash')) {
    // Lấy và tự động xóa thông báo trạng thái (flash message) dùng một lần trong Session.
    function app_flash(string $key): ?string
    {
        if (!isset($_SESSION[$key])) {
            return null;
        }

        $message = (string) $_SESSION[$key];
        unset($_SESSION[$key]);

        return $message;
    }
}

if (!function_exists('app_render_toast')) {
    // Sinh ra mã HTML/JS để hiển thị thông báo nổi (Toast Notification) trên giao diện.
    function app_render_toast(): string
    {
        $success = app_flash('flash_success');
        $error = app_flash('flash_error');
        
        $html = '<div class="toast-container-center" id="toastContainer"></div>';
        $html .= '<script>
            // Xử lý tạo và tự động xóa các thẻ Toast Notification trên trình duyệt.
            function showToast(message, type = "success") {
                const container = document.getElementById("toastContainer");
                if (!container) return;
                
                const toast = document.createElement("div");
                toast.className = `toast-center toast-${type}`;
                toast.innerHTML = `
                    <span>${message}</span>
                    <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
                `;
                container.appendChild(toast);
                
                // Tự động mất sau 5 giây
                setTimeout(() => { if(toast && toast.parentElement) toast.remove(); }, 5000);
            }
        </script>';
        
        if ($success) {
            $html .= '<script>document.addEventListener("DOMContentLoaded", () => showToast('
                . json_encode($success, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . ', "success"));</script>';
        }
        if ($error) {
            $html .= '<script>document.addEventListener("DOMContentLoaded", () => showToast('
                . json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . ', "error"));</script>';
        }

        return $html;
    }
}

if (!function_exists('app_selected')) {
    // Hỗ trợ in ra thuộc tính 'selected' cho thẻ <option> trong form select.
    function app_selected($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('app_checked')) {
    // Hỗ trợ in ra thuộc tính 'checked' cho thẻ <input type="checkbox/radio">.
    function app_checked($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'checked' : '';
    }
}

if (!function_exists('app_status_label')) {
    // Lấy nhãn tiếng Việt tương ứng cho mã trạng thái đơn hàng.
    function app_status_label(string $status): string
    {
        return Order::getStatusLabel($status);
    }
}

if (!function_exists('app_status_class')) {
    // Tạo tên class CSS tương ứng với trạng thái để tùy biến màu sắc Badge.
    function app_status_class(string $status): string
    {
        $safeStatus = preg_replace('/[^a-z0-9_-]/i', '', $status);
        return trim('status-badge status-' . $safeStatus);
    }
}

if (!function_exists('app_avatar_url')) {
    // Xử lý và phân giải đường dẫn URL hợp lệ của ảnh đại diện (Avatar).
    function app_avatar_url($avatar, string $name = 'User'): string
    {
        if (empty($avatar) || strpos((string)$avatar, 'default-avatar.png') !== false) {
            return '/assets/images/default-avatar.png';
        }

        $avatar = (string) $avatar;
        if (strpos($avatar, 'http://') === 0 || strpos($avatar, 'https://') === 0 || strpos($avatar, '/') === 0) {
            return (string) $avatar;
        }

        return '/uploads/avatars/' . ltrim($avatar, '/');
    }
}

if (!function_exists('app_truncate')) {
    // Cắt ngắn chuỗi văn bản nếu quá dài và nối thêm dấu ba chấm (...).
    function app_truncate($text, int $length = 30): string
    {
        $text = (string) ($text ?? '');
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8') > $length ? mb_substr($text, 0, $length, 'UTF-8') . '...' : $text;
        }

        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
}

if (!function_exists('app_nav_active')) {
    // Kiểm tra URL hiện hành để thêm class 'active' cho việc highlight Menu điều hướng.
    function app_nav_active(string $path, bool $exact = false): string
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($exact) {
            return $current === $path ? 'active' : '';
        }

        $prefix = rtrim($path, '/') . '/';
        return $current === $path || strpos($current, $prefix) === 0 ? 'active' : '';
    }
}

if (!function_exists('app_format_address')) {
    // Định dạng địa chỉ chuẩn phong cách Việt Nam, lược bỏ mã bưu điện và tên quốc gia.
    function app_format_address($address): string
    {
        $address = (string) ($address ?? '');
        if ($address === '') return '';
        
        $parts = array_map('trim', explode(',', $address));
        
        // Lược bỏ Mã bưu điện và chữ Việt Nam
        $parts = array_filter($parts, function($p) {
            return !preg_match('/^\d{5,6}$/', $p) 
                && strcasecmp($p, 'Việt Nam') !== 0 
                && strcasecmp($p, 'Vietnam') !== 0;
        });
        
        $parts = array_values($parts);
        $merged = [];
        $i = 0;
        $count = count($parts);
        
        while ($i < $count) {
            // Ghép Số nhà vào Tên đường nếu bị tách rời
            if (preg_match('/^\d+[A-Za-z\/\-]*$/', $parts[$i]) && $i < $count - 1) {
                $merged[] = $parts[$i] . ' ' . $parts[$i+1];
                $i += 2;
            } else {
                $merged[] = $parts[$i];
                $i++;
            }
        }
        
        return implode(', ', $merged);
    }
}

if (!function_exists('app_build_driver_route_points')) {
    // Chuẩn hóa danh sách các điểm lấy/giao hàng thành lộ trình trật tự để hiển thị trên bản đồ tài xế.
    function app_build_driver_route_points(array $group, array $routeDetails = []): array
    {
        $ordersById = [];
        foreach ($group as $order) {
            $ordersById[(int) $order['id']] = $order;
        }

        // Tạo một node điểm dừng Lấy hoặc Giao hàng trên bản đồ nếu đơn đang ở trạng thái phù hợp.
        $appendDriverRoutePoint = static function (array $order, string $type, int $sequence) {
            $status = $order['status'] ?? '';

            if ($type === 'pickup') {
                if (!in_array($status, ['accepted', 'picking_up', 'returning'], true)) {
                    return null;
                }
                if (empty($order['sender_lat']) || empty($order['sender_lng'])) {
                    return null;
                }

                return [
                    'lat' => (float) $order['sender_lat'],
                    'lng' => (float) $order['sender_lng'],
                    'title' => ($status === 'returning' ? 'Hoàn hàng: ' : 'Lấy hàng: ') . ($order['sender_name'] ?? ''),
                    'type' => 'pickup',
                    'sequence' => $sequence,
                    'tracking_code' => $order['tracking_code'] ?? '',
                    'address' => $order['pickup_address'] ?? '',
                ];
            }

            if (!in_array($status, ['accepted', 'picking_up', 'in_transit', 'shipping'], true)) {
                return null;
            }
            if (empty($order['receiver_lat']) || empty($order['receiver_lng'])) {
                return null;
            }

            return [
                'lat' => (float) $order['receiver_lat'],
                'lng' => (float) $order['receiver_lng'],
                'title' => 'Giao hàng: ' . ($order['receiver_name'] ?? 'Khách'),
                'type' => 'delivery',
                'sequence' => $sequence,
                'tracking_code' => $order['tracking_code'] ?? '',
                'address' => $order['delivery_address'] ?? '',
            ];
        };

        $points = [];
        if (!empty($routeDetails)) {
            foreach ($routeDetails as $index => $step) {
                $orderId = (int) ($step['order_id'] ?? 0);
                $type = ($step['type'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
                if (!isset($ordersById[$orderId])) {
                    continue;
                }
                $point = $appendDriverRoutePoint($ordersById[$orderId], $type, $index + 1);
                if ($point !== null) {
                    $points[] = $point;
                }
            }
        }

        if (!empty($points)) {
            return $points;
        }

        $sequence = 1;
        foreach ($group as $order) {
            foreach (['pickup', 'delivery'] as $type) {
                $point = $appendDriverRoutePoint($order, $type, $sequence);
                if ($point !== null) {
                    $points[] = $point;
                    $sequence++;
                }
            }
        }

        return $points;
    }
}

if (!function_exists('app_sanitize')) {
    // Làm sạch dữ liệu đầu vào (chuỗi hoặc mảng) bằng cách loại bỏ các thẻ HTML/PHP để chống XSS.
    function app_sanitize($data)
    {
        if (is_string($data)) {
            return trim(strip_tags($data));
        }

        if (is_array($data)) {
            return array_map('app_sanitize', $data);
        }

        // Trả về nguyên bản nếu không phải chuỗi hoặc mảng (VD: số, boolean).
        return $data;
    }
}

if (!function_exists('array_is_list')) {
    // Polyfill hỗ trợ hàm array_is_list cho các phiên bản PHP cũ hơn 8.1.
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }
}

if (!function_exists('app_validate_uploaded_image')) {
    // Kiểm tra tính hợp lệ của tệp ảnh tải lên (định dạng MIME, kích thước, lỗi upload).
    function app_validate_uploaded_image(array $file, array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'], int $maxBytes = 5242880): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Tải tệp thất bại.'];
        }

        if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
            return ['valid' => false, 'error' => 'Kích thước ảnh không hợp lệ hoặc vượt quá giới hạn cho phép.'];
        }

        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['valid' => false, 'error' => 'Không tìm thấy tệp upload hợp lệ.'];
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mimeType = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mimeType === '' || !in_array($mimeType, $allowedMimeTypes, true)) {
            return ['valid' => false, 'error' => 'Chỉ cho phép tải lên ảnh JPG, PNG hoặc WEBP.'];
        }

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        return [
            'valid' => true,
            'extension' => $extensionMap[$mimeType] ?? 'jpg',
        ];
    }
}

if (!function_exists('app_calculate_platform_fee')) {
    /**
     * Tính toán phí nền tảng (Platform Fee) từ cước phí vận chuyển.
     * @param float $shippingFee Cước phí vận chuyển của đơn hàng.
     * @return int Phí nền tảng đã được làm tròn lên.
     */
    function app_calculate_platform_fee(float $shippingFee): int
    {
        static $platformFeePercent = null;

        if ($platformFeePercent === null) {
            $settingModel = new \App\Models\Setting();
            $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
        }

        if ($platformFeePercent <= 0) {
            return 0;
        }

        return (int) ceil($shippingFee * $platformFeePercent / 100);
    }
}

if (!function_exists('app_calculate_driver_earnings')) {
    /**
     * Tính toán thu nhập thực nhận của tài xế (Cước phí - Phí nền tảng).
     * @param float $shippingFee Cước phí vận chuyển của đơn hàng.
     * @return int Thu nhập thực nhận của tài xế.
     */
    function app_calculate_driver_earnings(float $shippingFee): int
    {
        $platformFee = app_calculate_platform_fee($shippingFee);
        return max(0, (int) $shippingFee - $platformFee);
    }
}

if (!function_exists('app_json_success')) {
    // Chuẩn hóa định dạng JSON trả về khi xử lý thành công (API Response).
    function app_json_success($data = [], string $message = ''): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data
        ];
    }
}

if (!function_exists('app_json_error')) {
    // Chuẩn hóa định dạng JSON trả về khi có lỗi (API Response).
    function app_json_error(string $message = 'Có lỗi xảy ra', array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ];
    }
}

if (!function_exists('app_is_valid_coordinates')) {
    // Kiểm tra tính hợp lệ của tọa độ trên bản đồ thế giới.
    function app_is_valid_coordinates(float $lat, float $lng): bool
    {
        return is_finite($lat) && is_finite($lng) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat === 0.0 && $lng === 0.0);
    }
}

if (!function_exists('app_normalize_coordinates')) {
    // Chuẩn hóa và sửa lỗi đảo ngược tọa độ Vĩ độ - Kinh độ (nếu có).
    function app_normalize_coordinates($lat, $lng): ?array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $normalizedLat = (float) $lat;
        $normalizedLng = (float) $lng;

        if (!app_is_valid_coordinates($normalizedLat, $normalizedLng)
            && app_is_valid_coordinates($normalizedLng, $normalizedLat)) {
            [$normalizedLat, $normalizedLng] = [$normalizedLng, $normalizedLat];
        }

        if (!app_is_valid_coordinates($normalizedLat, $normalizedLng)) {
            return null;
        }

        return ['lat' => $normalizedLat, 'lng' => $normalizedLng];
    }
}

if (!function_exists('app_mask_email')) {
    // Che giấu một phần địa chỉ email (VD: abc***@gmail.com) để bảo vệ quyền riêng tư.
    function app_mask_email(string $email): string
    {
        if (empty($email)) {
            return '';
        }
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1] ?? '';
        $len = strlen($username);
        
        if ($len <= 3) {
            return str_repeat('*', $len) . '@' . $domain;
        }
        
        return substr($username, 0, 3) . str_repeat('*', $len - 3) . '@' . $domain;
    }
}

if (!function_exists('app_merge_detailed_address')) {
    // Gộp địa chỉ chi tiết (số nhà, hẻm) với địa chỉ trên bản đồ thành một chuỗi hoàn chỉnh.
    function app_merge_detailed_address(string $baseAddress, string $detailAddress): string
    {
        $baseAddress = trim(trim($baseAddress), ", ");
        $detailAddress = trim(trim($detailAddress), ", ");

        if ($detailAddress === '') {
            return $baseAddress;
        }

        if ($baseAddress === '') {
            return $detailAddress;
        }

        // Để tránh trùng lặp như "Số 1, Số 1 Hùng Vương...", kiểm tra nếu địa chỉ map đã bắt đầu bằng địa chỉ chi tiết (không phân biệt hoa thường).
        if (stripos($baseAddress, $detailAddress) === 0) {
            return $baseAddress;
        }

        return $detailAddress . ', ' . $baseAddress;
    }
}

if (!function_exists('app_component')) {
    // Hỗ trợ gọi và render một UI Component dùng chung với phạm vi biến độc lập.
    function app_component(string $componentPath, array $params = []): string
    {
        $fullPath = __DIR__ . '/../../views/components/' . $componentPath . '.php';
        
        if (!file_exists($fullPath)) {
            return "<!-- Missing UI Component: [$componentPath] -->";
        }

        extract($params, EXTR_SKIP);
        ob_start();
        require $fullPath;
        return ob_get_clean();
    }
}

if (!function_exists('app_compress_image_before_upload')) {
    /**
     * TỐI ƯU HÓA: Nén và giảm kích thước ảnh cục bộ (Local Compression) trước khi đẩy lên Cloudinary.
     * Giúp giảm dung lượng ảnh từ 5-10MB xuống còn ~150KB, tăng tốc độ phản hồi cho tài xế lên gấp 5 lần.
     */
    function app_compress_image_before_upload(string $sourcePath, int $maxWidth = 1024, int $quality = 80): bool
    {
        if (!file_exists($sourcePath)) return false;
        
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];
        $image = null;

        // Tạo đối tượng ảnh trong RAM tùy theo định dạng
        switch ($mime) {
            case 'image/jpeg': $image = imagecreatefromjpeg($sourcePath); break;
            case 'image/png': $image = imagecreatefrompng($sourcePath); break;
            case 'image/webp': $image = imagecreatefromwebp($sourcePath); break;
            default: return false; // Bỏ qua nếu không phải định dạng ảnh hỗ trợ
        }

        if (!$image) return false;

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Nếu ảnh quá lớn, tiến hành thu nhỏ (Scale down)
        if ($origWidth > $maxWidth) {
            $ratio = $maxWidth / $origWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) ($origHeight * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $newImage;
        }

        // Ghi đè file ảnh tạm (tmp_name) bằng ảnh đã được nén giảm chất lượng
        $success = false;
        switch ($mime) {
            case 'image/jpeg': $success = imagejpeg($image, $sourcePath, $quality); break;
            case 'image/png': $success = imagepng($image, $sourcePath, 8); break; // Mức nén PNG 0-9
            case 'image/webp': $success = imagewebp($image, $sourcePath, $quality); break;
        }

        imagedestroy($image);
        return $success;
    }
}
