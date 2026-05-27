<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

interface Middleware
{
    // Xử lý request trước khi đi tiếp vào ứng dụng, trả về Response (chặn) hoặc gọi $next().
    public function handle(Request $request, Response $response, \Closure $next);
}
