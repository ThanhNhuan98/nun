<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;
use PDO;

class NotificationController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        // Yêu cầu đăng nhập, ai cũng có thể xem thông báo của chính mình
        $userId = $this->userId();
        
        // Đánh dấu tất cả là đã đọc khi vào trang này
        $notificationModel = new Notification();
        $notificationModel->markAllAsRead($userId);

        $db = Database::getInstance();
        
        // Phân trang
        $page = max(1, (int)($request->getBody()['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Đếm tổng số thông báo của User này
        $stmtCount = $db->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ?");
        $stmtCount->execute([$userId]);
        $total = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($total / $perPage);

        // Lấy danh sách thông báo
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $allNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $response->render('notification/index', [
            'pageTitle' => 'Tất cả thông báo',
            'allNotifications' => $allNotifications,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
}