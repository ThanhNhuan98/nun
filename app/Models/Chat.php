<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Chat
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getMessages(int $orderId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM order_chats WHERE order_id = ? ORDER BY created_at ASC");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendMessage(int $orderId, int $senderId, int $receiverId, string $message): bool
    {
        $stmt = $this->db->prepare("INSERT INTO order_chats (order_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        return $stmt->execute([$orderId, $senderId, $receiverId, $message]);
    }
}