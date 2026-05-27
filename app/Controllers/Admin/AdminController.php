<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\DashboardModel;
use App\Models\Wallet;
use App\Models\User;
use App\Models\DriverPenalty;

class AdminController extends BaseController
{
    // Lấy thông tin chi tiết đơn hàng
    private function getOrderOrFail(int $id)
    {
        $orderModel = new Order();
        $order = $orderModel->findByIdForAdmin($id);

        if (!$order) {
            $_SESSION['flash_error'] = 'Không tìm thấy đơn hàng trên hệ thống.';
            return null;
        }
        
        return $order;
    }

    // Hiển thị trang Tổng quan 
    public function dashboard(Request $request, Response $response)
    {
        $dashboardModel = new DashboardModel();
        $stats = $dashboardModel->getStats();

        return $response->render('admin/dashboard', [
            'pageTitle' => 'Tổng quan hệ thống NUN Express',
            'stats' => $stats
        ]);
    }

    // Hiển thị danh sách các công việc 
    public function tasks(Request $request, Response $response)
    {
        $dashboardModel = new DashboardModel();
        $stats = $dashboardModel->getStats();

        return $response->render('admin/tasks/index', [
            'pageTitle' => 'Cần xử lý ngay',
            'stats' => $stats
        ]);
    }

    // Hiển thị danh sách đơn hàng 
    public function orders(Request $request, Response $response)
    {
        $query = $request->getBody();
        $statusFilter = trim($query['status'] ?? '');
        $search = trim($query['search'] ?? '');
        
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $orderModel = new Order();
        $totalOrders = $orderModel->countAllForAdmin($statusFilter, $search);
        $orders = $orderModel->getAllForAdmin($statusFilter, $search, $perPage, $offset);
        $totalPages = (int) ceil($totalOrders / $perPage);

        return $response->render('admin/orders/index', [
            'pageTitle' => 'Quản lý đơn hàng',
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    // Xem chi tiết một đơn hàng
    public function viewOrder(Request $request, Response $response)
    {
        $id = (int) $request->getRouteParam('id');
        $order = $this->getOrderOrFail($id);
        if (!$order) {
            return $response->redirect('/admin/orders');
        }

        $orderModel = new Order();

        if ($request->isPost()) {
            $data = $request->getBody();
            
            if ($orderModel->updateForAdmin($id, $data)) {
                $_SESSION['flash_success'] = 'Cập nhật thông tin đơn hàng thành công!';
                return $response->redirect("/admin/orders/view/{$id}");
            } else {
                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi cập nhật đơn hàng.';
            }
        }

        $history = $orderModel->getOrderHistory($id);

        $ratingInfo = ['avg' => 0, 'total' => 0];
        if (!empty($order['driver_id'])) {
            $userModel = new User();
            $ratingInfo = $userModel->getDriverRating((int) $order['driver_id']);
        }

        return $response->render('admin/orders/view', [
            'pageTitle' => 'Chi tiết & Cập nhật đơn hàng #' . $order['tracking_code'],
            'order' => $order,
            'history' => $history,
            'ratingInfo' => $ratingInfo
        ]);
    }

    // Hiển thị giao diện chỉnh sửa 
    public function editOrder(Request $request, Response $response)
    {
        $id = (int) $request->getRouteParam('id');
        $order = $this->getOrderOrFail($id);
        if (!$order) {
            return $response->redirect('/admin/orders');
        }

        if ($request->isPost()) {
            $data = $request->getBody();
            
            $orderModel = new Order();
            if ($orderModel->updateForAdmin($id, $data)) {
                $_SESSION['flash_success'] = 'Cập nhật đơn hàng thành công!';
                return $response->redirect('/admin/orders/view/' . $id);
            } else {
                $order['error'] = 'Có lỗi xảy ra, không thể cập nhật đơn hàng. Vui lòng kiểm tra lại.';
            }
        }

        return $response->render('admin/orders/edit', [
            'pageTitle' => 'Chỉnh sửa đơn hàng #' . $order['tracking_code'],
            'order' => $order
        ]);
    }

    // Phạt tiền tài xế 
    public function penalizeDriver(Request $request, Response $response)
    {
        $orderId = (int) $request->getRouteParam('id');
        $order = $this->getOrderOrFail($orderId);
        if (!$order) {
            return $response->redirect('/admin/orders');
        }

        $data = $request->getBody();
        $penaltyAmount = (float) ($data['penalty_amount'] ?? 50000);
        $reason = app_sanitize($data['reason'] ?? 'Báo cáo/Hủy đơn sai sự thật');
        $driverId = $order['driver_id'];

        if (!$driverId) {
            $_SESSION['flash_error'] = 'Đơn hàng này chưa có tài xế.';
            return $response->redirect("/admin/orders/view/{$orderId}");
        }

        $penaltyModel = new DriverPenalty();
        
        if ($penaltyModel->applyPenalty($driverId, 'admin_penalty', $penaltyAmount, $reason, $this->userId())) {
            $currentBalance = (new Wallet())->getBalance($driverId);
            $desc = "Admin đã phạt tài xế " . number_format($penaltyAmount, 0, ',', '.') . "đ. Lý do: " . $reason;
            
            if ($currentBalance < 0) {
                $desc .= " (Tài khoản tài xế đã tự động bị khóa do số dư ví âm).";
                (new User())->updateBlockStatus($driverId, 1);
            }
            
            $db = \App\Core\Database::getInstance();
            $db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")->execute([$orderId, $order['status'], $desc]);
            (new User())->createNotification($driverId, 'Thông báo chế tài', "Hệ thống đã khấu trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ từ ví của bạn tại đơn #{$order['tracking_code']} (Lý do: {$reason}).", 'wallet', "/driver/orders/view/{$orderId}");

            $_SESSION['flash_success'] = 'Đã phạt tài xế thành công.';
        } else {
            $_SESSION['flash_error'] = 'Có lỗi xảy ra khi phạt tài xế.';
        }
        return $response->redirect("/admin/orders/view/{$orderId}");
    }
}
