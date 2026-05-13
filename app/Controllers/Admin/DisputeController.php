<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Setting;

/**
 * Class DisputeController
 * Quản lý các chức năng liên quan đến khiếu nại.
 */
class DisputeController extends BaseController
{
    /**
     * Hiển thị danh sách khiếu nại.
     * Route: GET /admin/disputes
     */
    public function index(Request $request, Response $response)
    {
        // Chỉ Admin mới có quyền truy cập
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }
        
        $query = $request->getBody();
        $page = max(1, (int)($query['page'] ?? 1));
        $statusFilter = trim($query['status'] ?? '');
        $search = trim($query['search'] ?? '');
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $disputeModel = new Dispute();
        $totalDisputes = $disputeModel->countAll($statusFilter, $search);
        $totalPages = (int) ceil($totalDisputes / $perPage);

        $disputes = $disputeModel->getAll($perPage, $offset, $statusFilter, $search);

        // Dịch loại khiếu nại sang tiếng Việt
        foreach ($disputes as &$d) {
            $d['issue_type'] = Dispute::getIssueTypeLabel($d['issue_type'] ?? '');
            $d['status_label'] = Dispute::getStatusLabel($d['status'] ?? '');
            $d['reporter_role_label'] = User::getRoleLabel($d['reporter_role'] ?? '');
        }
        unset($d);

        return $response->render('admin/disputes/index', [
            'pageTitle' => 'Quản lý khiếu nại',
            'disputes' => $disputes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'statusFilter' => $statusFilter,
            'search' => $search
        ]);
    }

    /**
     * Xem chi tiết và xử lý khiếu nại
     * Route: GET/POST /admin/disputes/view/{id}
     */
    public function view(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $id = (int) $request->getRouteParam('id');
        $disputeModel = new Dispute();

        // Xử lý POST request để cập nhật kết quả
        if ($request->isPost()) {
            $data = $request->getBody();
            $status = $data['status'] ?? 'open';
            $resolutionNote = app_sanitize($data['resolution_note'] ?? ($data['admin_note'] ?? ''));
            $resolvedBy = $this->userId();
            $newOrderStatus = $data['order_status'] ?? ''; // Trạng thái đơn hàng mới Admin muốn set
            $fault = $data['fault'] ?? 'none';
            $penaltyAmount = (int) ($data['penalty_amount'] ?? 0);

            if ($fault === 'driver' && $penaltyAmount <= 0) {
                $_SESSION['flash_error'] = 'Vui lòng nhập số tiền phạt lớn hơn 0.';
                return $response->redirect("/admin/disputes/view/{$id}");
            }

            // Lấy ID đơn hàng liên quan đến khiếu nại này
            $disputeData = $disputeModel->findById($id);
            if (!$disputeData) {
                $_SESSION['flash_error'] = 'Khiếu nại không tồn tại.';
                return $response->redirect("/admin/disputes");
            }

            // Tự động thêm ghi chú và ngăn phạt 2 lần nếu Admin bấm lưu nhiều lần
            $oldNote = $disputeData['resolution_note'] ?? $disputeData['admin_note'] ?? '';
            $alreadyCustomerPenalized = strpos($oldNote, 'Đã phạt khách hàng') !== false;
            $alreadyDriverPenalized = strpos($oldNote, 'Đã phạt tài xế') !== false;

            $shouldPenalizeCustomer = ($fault === 'customer' && !$alreadyCustomerPenalized);
            $shouldPenalizeDriver = ($fault === 'driver' && $penaltyAmount > 0 && !$alreadyDriverPenalized);

            $autoNote = "";
            if ($shouldPenalizeCustomer) {
                $autoNote = "\n- Đã phạt khách hàng (Ghi nhận bom hàng).";
            } elseif ($shouldPenalizeDriver) {
                $autoNote = "\n- Đã phạt tài xế " . number_format($penaltyAmount, 0, ',', '.') . "đ.";
            }

            if ($autoNote !== '') {
                $resolutionNote = trim($resolutionNote . $autoNote);
            }

            $db = \App\Core\Database::getInstance();
            $db->beginTransaction();

            try {
                if ($disputeModel->updateStatus($id, $status, $resolutionNote, $resolvedBy)) {

                    if ($disputeData['order_id']) {
                        $orderModel = new Order();
                        $currentOrder = $orderModel->findByIdForAdmin($disputeData['order_id']);
                        
                        if ($currentOrder) {
                            // 1. Thay đổi trạng thái đơn hàng nếu có
                            if (!empty($newOrderStatus) && $currentOrder['status'] !== $newOrderStatus) {
                                $desc = "Admin đã xử lý khiếu nại. Quyết định: " . ($resolutionNote ?: 'Thay đổi trạng thái đơn hàng');
                                $orderModel->updateStatus($currentOrder['id'], $newOrderStatus, $desc);
                                
                                if ($newOrderStatus === 'completed' && ($currentOrder['payment_method'] ?? 'cash') === 'transfer' && !empty($currentOrder['driver_id'])) {
                                    $walletModel = new Wallet();
                                    $settingModel = new Setting();
                                    $feePerOrder = (int) ceil(($currentOrder['shipping_fee'] ?? 0) * (float) $settingModel->get('platform_fee_percent', 20) / 100);
                                    $driverEarnings = (int) ($currentOrder['shipping_fee'] ?? 0) - $feePerOrder;
                                    
                                    if ($driverEarnings > 0) {
                                        $walletModel->add($currentOrder['driver_id'], $driverEarnings, 'adjustment', "Cộng tiền cước đơn #{$currentOrder['tracking_code']} (Admin xử lý khiếu nại)", $currentOrder['id']);
                                        $userModel = new User();
                                        $userModel->createNotification($currentOrder['driver_id'], 'Cộng tiền cước', "Bạn đã được cộng " . number_format($driverEarnings, 0, ',', '.') . "đ cho đơn #{$currentOrder['tracking_code']} sau khi Admin xử lý khiếu nại.", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                                    }
                                }

                                if ($newOrderStatus === 'cancelled' && ($currentOrder['payment_method'] ?? 'cash') === 'cash' && !empty($currentOrder['driver_id'])) {
                                    $walletModel = new Wallet();
                                    $settingModel = new Setting();
                                    $feePerOrder = (int) ceil(($currentOrder['shipping_fee'] ?? 0) * (float) $settingModel->get('platform_fee_percent', 20) / 100);
                                    
                                    $walletModel->add($currentOrder['driver_id'], $feePerOrder, 'refund', "Hoàn phí nền tảng đơn #{$currentOrder['tracking_code']} (Khách hủy)", $currentOrder['id']);
                                    $userModel = new User();
                                    $userModel->createNotification($currentOrder['driver_id'], 'Cộng tiền - Hoàn phí', "Đơn #{$currentOrder['tracking_code']} đã bị hủy sau khi khiếu nại. Bạn được hoàn lại " . number_format($feePerOrder, 0, ',', '.') . "đ phí.", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                                }
                            }

                            // 2. Xử lý phạt vi phạm
                            $userModel = new User();
                            if ($shouldPenalizeCustomer) {
                                $userModel->recordNoShow($currentOrder['customer_id']);
                                $userModel->createNotification($currentOrder['customer_id'], "Cảnh báo vi phạm", "Bạn đã bị ghi nhận 1 lần vi phạm từ Admin sau khi giải quyết khiếu nại đơn hàng #{$currentOrder['tracking_code']}.", 'system', "/user/orders/track/{$currentOrder['tracking_code']}");
                            } elseif ($shouldPenalizeDriver && !empty($currentOrder['driver_id'])) {
                                $walletModel = new Wallet();
                                $deducted = $walletModel->deduct($currentOrder['driver_id'], $penaltyAmount, 'penalty', "Admin phạt lỗi khiếu nại đơn #{$currentOrder['tracking_code']}");
                                
                                // ÉP TRỪ: Nếu hàm deduct trả về false (do ví 0đ hoặc không đủ), gọi hàm forceDeduct của Model
                                if (!$deducted) {
                                    $walletModel->forceDeduct($currentOrder['driver_id'], $penaltyAmount, 'penalty', "Admin phạt lỗi khiếu nại đơn #{$currentOrder['tracking_code']}", $currentOrder['id']);
                                }
                                
                                $currentBalance = $walletModel->getBalance($currentOrder['driver_id']);
                                if ($currentBalance < 0) {
                                    $userModel->updateBlockStatus($currentOrder['driver_id'], 1); // Tự động khóa nếu ví âm
                                    $db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")->execute([$currentOrder['id'], $currentOrder['status'], "Tài khoản tài xế đã tự động bị khóa do số dư ví âm sau khi bị phạt."]);
                                }
                                $userModel->createNotification($currentOrder['driver_id'], 'Trừ tiền - Phạt vi phạm', "Hệ thống trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ phạt lỗi khiếu nại đơn #{$currentOrder['tracking_code']}.", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                            }
                        }
                    }

                    $db->commit();
                    $_SESSION['flash_success'] = 'Cập nhật trạng thái khiếu nại thành công!';
                } else {
                    $db->rollBack();
                    $_SESSION['flash_error'] = 'Có lỗi xảy ra khi cập nhật vào cơ sở dữ liệu.';
                }
            } catch (\Exception $e) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Lỗi hệ thống: ' . $e->getMessage();
            }
            return $response->redirect("/admin/disputes/view/{$id}");
        }

        // Lấy chi tiết khiếu nại
        $dispute = $disputeModel->findById($id);

        if (!$dispute) {
            $_SESSION['flash_error'] = 'Không tìm thấy khiếu nại này trên hệ thống.';
            return $response->redirect('/admin/disputes');
        }

        // Dịch loại khiếu nại sang tiếng Việt
        $dispute['issue_type'] = Dispute::getIssueTypeLabel($dispute['issue_type'] ?? '');
        $dispute['status_label'] = Dispute::getStatusLabel($dispute['status'] ?? '');
        $dispute['reporter_role_label'] = User::getRoleLabel($dispute['reporter_role'] ?? '');

        return $response->render('admin/disputes/view', [
            'pageTitle' => 'Xử lý khiếu nại #' . $dispute['id'],
            'dispute' => $dispute
        ]);
    }
}
