<?php

use App\Models\Order;

if (!function_exists('app_e')) {
    function app_e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_money')) {
    function app_money($amount, string $suffix = 'đ'): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.') . $suffix;
    }
}

if (!function_exists('app_datetime')) {
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

if (!function_exists('app_selected')) {
    function app_selected($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('app_checked')) {
    function app_checked($current, $expected): string
    {
        return (string) $current === (string) $expected ? 'checked' : '';
    }
}

if (!function_exists('app_status_label')) {
    function app_status_label(string $status): string
    {
        return Order::getStatusLabel($status);
    }
}

if (!function_exists('app_status_class')) {
    function app_status_class(string $status): string
    {
        $safeStatus = preg_replace('/[^a-z0-9_-]/i', '', $status);
        return trim('status-badge status-' . $safeStatus);
    }
}

if (!function_exists('app_avatar_url')) {
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
    function app_truncate($text, int $length = 30): string
    {
        $text = (string) ($text ?? '');
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, $length, 'UTF-8') . '...';
        }

        if (!function_exists('mb_strlen') && strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }

        return $text;
    }
}

if (!function_exists('app_nav_active')) {
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

if (!function_exists('app_sanitize')) {
    /**
     * Làm sạch (sanitize) dữ liệu đầu vào để chống XSS.
     * Hàm này sẽ loại bỏ tất cả các thẻ HTML và PHP.
     * Nó có thể xử lý cả chuỗi và mảng (đệ quy).
     *
     * @param mixed $data Dữ liệu cần làm sạch (string hoặc array).
     * @return mixed Dữ liệu đã được làm sạch.
     */
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
    /**
     * Polyfill cho hàm array_is_list (Được giới thiệu từ PHP 8.1)
     * Sửa lỗi Cloudinary SDK khi chạy trên PHP <= 8.0
     */
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }
}

if (!function_exists('app_validate_uploaded_image')) {
    /**
     * Kiá»ƒm tra file upload cĂ³ pháº£i áº£nh há»£p lá»‡ khĂ´ng.
     *
     * @param array $file Dữ liệu từ $_FILES[...]
     * @param array $allowedMimeTypes Danh sách MIME cho phép
     * @param int $maxBytes Kích thước tối đa
     * @return array{valid: bool, extension?: string, error?: string}
     */
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
