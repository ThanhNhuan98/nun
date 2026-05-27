<?php

namespace App\Core;

class Response
{
    // Thiết lập mã trạng thái HTTP (200, 404, 500,...) cho phản hồi.
    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    // Xử lý render file View (.php) và truyền các tham số dữ liệu ra giao diện.
    public function render($view, $params = [])
    {
        // Đảm bảo luôn có biến pageTitle mặc định nếu controller quên truyền vào (đặc biệt ở trang 404)
        if (!isset($params['pageTitle'])) {
            $params['pageTitle'] = 'NUN Express';
        }
        $params = array_merge($this->layoutParams(), $params);

        // Biến các key của mảng $params thành các biến trong view 
        // VD: ['pageTitle' => 'Home'] sẽ tạo ra biến $pageTitle
        extract($params, EXTR_SKIP); // Dùng EXTR_SKIP để không ghi đè mất các biến cục bộ như $view, $content
        
        ob_start();
        // Load file view từ thư mục views
        require __DIR__ . "/../../views/$view.php"; // Thay require_once bằng require để cho phép render các component/view nhiều lần
        $content = ob_get_clean();
        
        return $content;
    }

    // Trả về dữ liệu dưới định dạng JSON kèm thiết lập Content-Type (thường dùng cho API).
    public function json(array $data, int $statusCode = 200): string
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Chuyển hướng trình duyệt đến một URL khác và ngắt thực thi PHP.
    public function redirect($url)
    {
        // Tự động thêm tiền tố thư mục nếu chạy trên XAMPP
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && $scriptName !== '\\' && strpos($url, 'http') !== 0) {
            $url = $scriptName . $url;
        }

        header("Location: $url");
        exit;
    }

    // Lấy các tham số dữ liệu mặc định (số thông báo chưa đọc, avatar người dùng) dùng chung cho khung Layout chính.
    private function layoutParams(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || empty($user['id'])) {
            return [
                'layoutNotifications' => [],
                'layoutUnreadCount' => 0,
                'layoutDisplayAvatar' => null,
            ];
        }

        $userId = (int) $user['id'];
        $userName = (string) ($user['name'] ?? 'Guest');
        $userAvatar = $user['avatar'] ?? null;
        $displayAvatar = function_exists('app_avatar_url')
            ? app_avatar_url($userAvatar, $userName)
            : ($userAvatar ?: '/assets/images/default-avatar.png');

        try {
            $notificationModel = new \App\Models\Notification();

            return [
                'layoutNotifications' => $notificationModel->latestForUser($userId),
                'layoutUnreadCount' => $notificationModel->unreadCount($userId),
                'layoutDisplayAvatar' => $displayAvatar,
            ];
        } catch (\Throwable $e) {
            error_log('Layout notification load failed: ' . $e->getMessage());
        }

        return [
            'layoutNotifications' => [],
            'layoutUnreadCount' => 0,
            'layoutDisplayAvatar' => $displayAvatar,
        ];
    }
}
