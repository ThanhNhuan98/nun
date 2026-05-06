<?php

// Bật Output Buffering để ngăn chặn triệt để lỗi không thể chuyển trang (redirect)
ob_start();

session_start();

// Bật hiển thị lỗi PHP ra màn hình để dễ dàng phát hiện nguyên nhân
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đồng bộ múi giờ Việt Nam cho toàn bộ PHP và các script gọi ngoài (như Python AI)
date_default_timezone_set('Asia/Ho_Chi_Minh');
putenv('TZ=Asia/Ho_Chi_Minh');

// 1. Nạp autoload của Composer để sử dụng các thư viện ngoài (PHPMailer, Cloudinary...)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("<h3 class='text-danger'>Lỗi nghiêm trọng: Không tìm thấy thư mục vendor/autoload.php.</h3><p>Vui lòng mở Terminal tại thư mục gốc của dự án và chạy lệnh: <b>composer install</b> hoặc <b>composer require phpmailer/phpmailer</b></p>");
}

require_once __DIR__ . '/../app/Helpers/helpers.php';

// 2. Tải cấu hình biến môi trường từ file .env (nếu bạn đã cài phpdotenv)
if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Cấu hình tự động tải class (Autoloading) theo chuẩn PSR-4
// (Thay thế cho require_once thủ công khắp nơi)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) require $file;
});

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$request = new Request();
$response = new Response($request);
$router = new Router($request, $response);

// Nạp danh sách các đường dẫn (Routes)
require_once __DIR__ . '/../routes/web.php';

// Thực thi Router
$router->resolve();
