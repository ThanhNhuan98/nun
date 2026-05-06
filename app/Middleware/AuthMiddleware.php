<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Response $response, \Closure $next)
    {
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash_error'] = 'Vui lòng đăng nhập để tiếp tục.';
            
            // Nếu là request AJAX, trả về JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                return $response->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return $response->redirect('/login');
        }
        
        return $next($request, $response);
    }
}