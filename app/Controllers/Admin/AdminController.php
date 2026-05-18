<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\DashboardModel;
use App\Models\Wallet;
use App\Models\User;

class AdminController extends BaseController
{
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

    public function dashboard(Request $request, Response $response)
    {
        $dashboardModel = new DashboardModel();
        $stats = $dashboardModel->getStats();

        return $response->render('admin/dashboard', [
            'pageTitle' => 'Tổng quan hệ thống NUN Express',
            'stats' => $stats
        ]);
    }

    public function tasks(Request $request, Response $response)
    {
        $dashboardModel = new DashboardModel();
        $stats = $dashboardModel->getStats();

        return $response->render('admin/tasks/index', [
            'pageTitle' => 'Cần xử lý ngay',
            'stats' => $stats
        ]);
    }

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
                if (isset($data['status']) && $data['status'] !== $order['status']) {
                    $desc = "Admin cập nhật trạng thái đơn hàng.";
                    $orderModel->updateStatus($id, $data['status'], $desc);
                }
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

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();

        try {
            $walletModel = new Wallet();
            $deducted = $walletModel->deduct($driverId, $penaltyAmount, 'penalty', $reason);
            
            if (!$deducted) {
                $walletModel->forceDeduct($driverId, $penaltyAmount, 'penalty', $reason);
            }
            
            $currentBalance = $walletModel->getBalance($driverId);
            $userModel = new User();
            
            if ($currentBalance < 0) {
                $userModel->updateBlockStatus($driverId, 1);
            }

            $desc = "Admin đã phạt tài xế " . number_format($penaltyAmount, 0, ',', '.') . "đ. Lý do: " . $reason;
            if ($currentBalance < 0) {
                $desc .= " (Tài khoản tài xế đã tự động bị khóa do số dư ví âm).";
            }
            $db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")->execute([$orderId, $order['status'], $desc]);

            $userModel->createNotification($driverId, 'Trừ tiền - Phạt vi phạm', "Hệ thống đã trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ của bạn tại đơn #{$order['tracking_code']}. Lý do: {$reason}.", 'wallet', "/driver/orders/view/{$orderId}");

            $db->commit();
            $_SESSION['flash_success'] = 'Đã phạt tài xế thành công.';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Có lỗi xảy ra khi phạt tài xế: ' . $e->getMessage();
        }
        return $response->redirect("/admin/orders/view/{$orderId}");
    }
}
