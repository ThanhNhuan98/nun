<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Review
{
    protected PDO $db;

    // Khởi tạo model Review và kết nối cơ sở dữ liệu.
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Tìm kiếm đánh giá của một khách hàng cho một đơn hàng cụ thể.
    public function findByOrderAndCustomer(int $orderId, int $customerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM driver_reviews WHERE order_id = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$orderId, $customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tạo đánh giá mới (số sao, nhận xét) cho tài xế từ khách hàng.
    public function create(int $orderId, int $customerId, int $driverId, int $rating, string $comment): bool
    {
        $stmt = $this->db->prepare("INSERT INTO driver_reviews (order_id, customer_id, driver_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$orderId, $customerId, $driverId, $rating, $comment]);
    }
}
