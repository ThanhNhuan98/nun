<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

class NotificationController extends BaseController
{
    // Ham index: hien thi danh sach thong bao cua nguoi dung dang dang nhap.
    public function index(Request $request, Response $response)
    {
        $userId = $this->userId();

        $notificationModel = new Notification();
        $notificationModel->markAllAsRead($userId);

        $page = max(1, (int)($request->getBody()['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $total = $notificationModel->countForUser($userId);
        $totalPages = (int) ceil($total / $perPage);
        $allNotifications = $notificationModel->allForUser($userId, $perPage, $offset);

        return $response->render('notification/index', [
            'pageTitle' => 'Tất cả thông báo',
            'allNotifications' => $allNotifications,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
}
