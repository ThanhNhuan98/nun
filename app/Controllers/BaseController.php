<?php

namespace App\Controllers;

use App\Core\Response;

abstract class BaseController
{
    // Lấy thông tin chi tiết của người dùng đang đăng nhập từ Session.
    protected function currentUser(?string $key = null, $default = null)
    {
        return \app_current_user($key, $default);
    }

    // Lấy ID của người dùng đang đăng nhập hiện tại.
    protected function userId(): int
    {
        return (int) ($this->currentUser('id', 0));
    }

    // Trả về trang thông báo lỗi 404 Not Found.
    protected function notFound(Response $response)
    {
        $response->setStatusCode(404);
        return $response->render('layouts/404');
    }
}
