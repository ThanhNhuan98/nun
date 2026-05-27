<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Chat
{
    protected PDO $db;
    // Khởi tạo model Chat và kết nối cơ sở dữ liệu PDO.
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    // Kiểm tra xem người dùng.
    public function userCanAccessOrder(int $orderId, int $userId, string $role = 'user'): bool
    {
        if ($role === 'admin') {
            $stmt = $this->db->prepare("SELECT 1 FROM orders WHERE id = ? AND is_archived = 0 LIMIT 1");
            $stmt->execute([$orderId]);
            return (bool) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM orders o
            LEFT JOIN order_deliveries od ON od.order_id = o.id
            WHERE o.id = ?
              AND o.is_archived = 0
              AND (o.customer_id = ? OR od.driver_id = ?)
            LIMIT 1
        ");
        $stmt->execute([$orderId, $userId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    // Lấy danh sách ID của những người tham gia (Khách hàng, Tài xế) trong đơn hàng.
    public function getOrderParticipantIds(int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT o.customer_id, od.driver_id
            FROM orders o
            LEFT JOIN order_deliveries od ON od.order_id = o.id
            WHERE o.id = ? AND o.is_archived = 0
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }

        return array_values(array_unique(array_filter([
            (int) ($row['customer_id'] ?? 0),
            (int) ($row['driver_id'] ?? 0),
        ])));
    }

    // Lấy danh sách tin nhắn
    public function getMessages(int $orderId, int $limit = 200): array
    {
        $stmt = $this->db->prepare("SELECT id, sender_id, receiver_id, message, created_at FROM order_chats WHERE order_id = ? ORDER BY created_at ASC LIMIT ?");
        $stmt->bindValue(1, $orderId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Gửi tin nhắn mới 
    public function sendMessage(int $orderId, int $senderId, int $receiverId, string $message): bool
    {
        $stmt = $this->db->prepare("INSERT INTO order_chats (order_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$orderId, $senderId, $receiverId, $message]);
        
        if ($result && class_exists('\App\Services\PusherService')) {
            try {
                $pusher = new \App\Services\PusherService();
                $pusher->trigger('notify-user-' . $receiverId, 'new_notification', [
                    'title' => 'Tin nhắn mới',
                    'message' => 'Bạn có tin nhắn mới: "' . mb_substr($message, 0, 30) . '..."',
                    'type' => 'chat',
                    'link' => ''
                ]);
                $pusher->trigger('notify-user-' . $receiverId, 'new_chat_message', [
                    'order_id' => $orderId
                ]);
            } catch (\Throwable $e) {}
        }
        return $result;
    }
}
