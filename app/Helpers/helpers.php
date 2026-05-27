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
        
        if ($success) $html .= "<script>document.addEventListener('DOMContentLoaded', () => showToast('" . app_e($success) . "', 'success'));</script>";
        if ($error) $html .= "<script>document.addEventListener('DOMContentLoaded', () => showToast('" . app_e($error) . "', 'error'));</script>";

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
