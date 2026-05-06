<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

interface Middleware
{
    /**
     * Xử lý request trước khi đi tiếp vào ứng dụng
     * Trả về Response (nếu bị chặn) hoặc gọi $next() để đi tiếp
     */
    public function handle(Request $request, Response $response, \Closure $next);
}