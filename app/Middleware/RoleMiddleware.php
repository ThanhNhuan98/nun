<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RoleMiddleware implements Middleware
{
    private string $role;

    // Khởi tạo Middleware bảo vệ với vai trò yêu cầu bắt buộc (VD: admin, driver).
    public function __construct(string $role)
    {
        $this->role = $role;
    }

    // Kiểm tra quyền của người dùng hiện tại có khớp với vai trò được yêu cầu để truy cập hay không.
    public function handle(Request $request, Response $response, \Closure $next)
    {
        // 1. Phải đăng nhập trước
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash_error'] = 'Vui lòng đăng nhập để tiếp tục.';
            return $response->redirect('/login');
        }

        // 2. Kiểm tra vai trò
        if ($_SESSION['user']['role'] !== $this->role) {
            $_SESSION['flash_error'] = 'Bạn không có quyền truy cập khu vực này.';
            return $response->redirect('/');
        }

        return $next($request, $response);
    }
}
