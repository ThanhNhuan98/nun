<?php

namespace App\Controllers;

use App\Core\Response;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

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

    // Helper gộp chung logic Upload ảnh lên Cloudinary an toàn cho toàn bộ Controllers
    protected function uploadToCloudinary(array $file, string $folder, ?string $publicId = null, array $extraOptions = []): string
    {
        try {
            Configuration::instance($_ENV['CLOUDINARY_URL']);
            $uploadApi = new UploadApi();
            
            $options = ['folder' => $folder];
            if ($publicId) $options['public_id'] = $publicId;
            $options = array_merge($options, $extraOptions);

            $result = $uploadApi->upload($file['tmp_name'], $options);
            return $result['secure_url'];
        } catch (\Exception $e) {
            error_log('Cloudinary Upload Error: ' . $e->getMessage());
            throw new \RuntimeException('Lỗi đồng bộ ảnh lên máy chủ. Vui lòng thử lại sau.');
        }
    }
}
