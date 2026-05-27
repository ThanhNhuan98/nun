<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    // Ngăn chặn khởi tạo đối tượng bằng từ khóa new bên ngoài (Singleton Pattern).
    private function __construct() {}
    // Ngăn chặn clone đối tượng (Singleton Pattern).
    private function __clone() {}

    // Tạo và trả về duy nhất một instance kết nối cơ sở dữ liệu PDO.
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Không bao giờ die() trong ứng dụng. Ném ngoại lệ để tầng cao hơn (index.php) bắt và xử lý.
                // Điều này cho phép hiển thị một trang lỗi thân thiện với người dùng.
                // Ghi lại lỗi chi tiết cho lập trình viên.
                error_log("Database Connection Error: " . $e->getMessage());
                throw new PDOException("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.", (int)$e->getCode());
            }
        }
        return self::$instance;
    }
}
