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

    // Lấy danh sách các thông báo .
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

    // Đếm tổng số thông báo chưa đọc.
    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    // Đếm tổng số thông báo của một người dùng trong toàn hệ thống.
    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    // Lấy toàn bộ danh sách thông báo của người dùng (hỗ trợ phân trang).
    public function allForUser(int $userId, int $limit = 15, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, title, message, type, link, is_read, created_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Tạo mới một thông báo và lưu vào cơ sở dữ liệu.
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

    // Cập nhật trạng thái tất cả thông báo của người dùng thành "Đã đọc".
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

    // Lấy danh sách ID của tất cả quản trị viên (Admin) trong hệ thống.
    public function adminIds(): array
    {
        // Ủy quyền qua Model User để chuẩn hóa logic lấy ID Admin
        return (new User())->getAdminIds();
    }

    // Gửi thông báo đồng loạt đến tất cả các Quản trị viên (Admin).
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
                // Tối ưu hóa: Dùng toán tử append [] thay cho array_push trong vòng lặp để giảm overhead
                $params[] = $adminId;
                $params[] = $title;
                $params[] = $message;
                $params[] = $type;
                $params[] = $link;
            }
            
            $sql = "INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES " . implode(', ', $values);
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute($params) && class_exists('\App\Services\PusherService')) {
                //  thông báo đẩy (Real-time) qua WebSockets cho tất cả Admin
                try {
                    $pusher = new \App\Services\PusherService();
                    // TỐI ƯU HÓA: Gộp mạng (Network Batching) thay vì gọi API Pusher riêng lẻ N lần trong vòng lặp
                    $channels = array_map(fn($id) => 'notify-user-' . $id, $adminIds);
                    $pusher->trigger($channels, 'new_notification', [
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'link' => $link
                    ]);
                } catch (\Throwable $e) {
                    // Bỏ qua nếu cấu hình Pusher có lỗi
                }
            }
        } catch (\Throwable $e) {
            error_log('Batch notification create failed: ' . $e->getMessage());
        }
    }
}
