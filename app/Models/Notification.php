<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function latestForUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare("
            SELECT id, user_id, title, message, type, link, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public function create(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");

            return $stmt->execute([$userId, $title, $message, $type, $link]);
        } catch (\Throwable $e) {
            error_log('Notification create failed: ' . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");

            return $stmt->execute([$userId]);
        } catch (\Throwable $e) {
            error_log('Notification mark read failed: ' . $e->getMessage());
            return false;
        }
    }

    public function adminIds(): array
    {
        try {
            $stmt = $this->db->query("SELECT id FROM users WHERE role = 'admin' AND is_blocked = 0");

            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        } catch (\Throwable $e) {
            error_log('Notification admin ids failed: ' . $e->getMessage());
            return [];
        }
    }

    public function notifyAdmins(string $title, string $message, string $type = 'system', ?string $link = null): void
    {
        $adminIds = $this->adminIds();
        if (empty($adminIds)) {
            return;
        }

        try {
            $values = [];
            $params = [];
            foreach ($adminIds as $adminId) {
                $values[] = "(?, ?, ?, ?, ?, 0, NOW())";
                array_push($params, $adminId, $title, $message, $type, $link);
            }
            
            $sql = "INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES " . implode(', ', $values);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log('Batch notification create failed: ' . $e->getMessage());
        }
    }
}
