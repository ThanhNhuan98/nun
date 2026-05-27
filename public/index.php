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
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

try {
    $request = new Request();
    $response = new Response();
    $router = new Router($request, $response);
    
    // Nạp danh sách các đường dẫn (Routes)
    require_once __DIR__ . '/../routes/web.php';
    
    // Thực thi Router
    $router->resolve();

} catch (\PDOException $e) {
    // Xử lý lỗi kết nối CSDL một cách duyên dáng
    http_response_code(503); // Service Unavailable
    echo "<h1>Lỗi hệ thống</h1>";
    echo "<p>Không thể kết nối đến máy chủ dữ liệu. Vui lòng liên hệ quản trị viên hoặc thử lại sau ít phút.</p>";
} catch (\Throwable $e) {
    // Bắt tất cả các lỗi khác chưa được xử lý
    http_response_code(500); // Internal Server Error
    echo "<h1>Đã có lỗi không mong muốn xảy ra</h1>";
    error_log("Unhandled Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
