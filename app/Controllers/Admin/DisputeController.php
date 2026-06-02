<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\DriverPenalty;
use App\Models\Setting;

class DisputeController extends BaseController
{
    // Hiển thị danh sách khiếu nại của hệ thống (có hỗ trợ phân trang và tìm kiếm).
    public function index(Request $request, Response $response)
    {
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

        foreach ($disputes as &$d) {
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

    // Hiển thị chi tiết và xử lý một khiếu nại (cập nhật trạng thái đơn, phạt tiền tài xế).
    public function view(Request $request, Response $response)
    {
        $id = (int) $request->getRouteParam('id');
        $disputeModel = new Dispute();

        if ($request->isPost()) {
            $data = $request->getBody();
            $status = $data['status'] ?? 'open';
            $resolutionNote = app_sanitize($data['resolution_note'] ?? ($data['admin_note'] ?? ''));
            $resolvedBy = $this->userId();
            $newOrderStatus = $data['target_order_status'] ?? ($data['order_status'] ?? '');
            $fault = $data['fault'] ?? 'none';
            $penaltyAmount = (int) ($data['penalty_amount'] ?? 0);

            if ($fault === 'driver' && $penaltyAmount < 0) {
                $_SESSION['flash_error'] = 'Số tiền phạt không được âm.';
                return $response->redirect("/admin/disputes/view/{$id}");
            }

            $disputeData = $disputeModel->findById($id);
            if (!$disputeData) {
                $_SESSION['flash_error'] = 'Khiếu nại không tồn tại.';
                return $response->redirect("/admin/disputes");
            }

            $oldNote = $disputeData['resolution_note'] ?? $disputeData['admin_note'] ?? '';
            $alreadyCustomerPenalized = str_contains($oldNote, 'Đã phạt khách hàng');
            $alreadyDriverPenalized = str_contains($oldNote, 'Đã phạt tài xế');

            $shouldPenalizeCustomer = ($fault === 'customer' && !$alreadyCustomerPenalized);
            $shouldPenalizeDriver = ($fault === 'driver' && !$alreadyDriverPenalized);

            $autoNote = "";
            if ($shouldPenalizeCustomer) {
                $autoNote = "\n- Đã phạt khách hàng (Ghi nhận vi phạm giao nhận).";
            } elseif ($shouldPenalizeDriver) {
                $autoNote = "\n- Đã ghi nhận vi phạm cho tài xế" . ($penaltyAmount > 0 ? " và phạt " . number_format($penaltyAmount, 0, ',', '.') . "đ." : " (Cảnh cáo 0đ).");
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
                            if (!empty($newOrderStatus) && $currentOrder['status'] !== $newOrderStatus) {
                                $desc = "Admin đã xử lý khiếu nại. Quyết định: " . ($resolutionNote ?: 'Thay đổi trạng thái đơn hàng');
                                $orderModel->updateStatus($currentOrder['id'], $newOrderStatus, $desc);
                                
                                if ($newOrderStatus === 'completed' && ($currentOrder['payment_method'] ?? 'cash') === 'transfer' && !empty($currentOrder['driver_id'])) {
                                    $driverEarnings = app_calculate_driver_earnings((float)($currentOrder['shipping_fee'] ?? 0));
                                    $walletModel = new Wallet();
                                    
                                    if ($driverEarnings > 0) {
                                        $walletModel->add($currentOrder['driver_id'], $driverEarnings, 'adjustment', "Cộng tiền cước đơn #{$currentOrder['tracking_code']} (Admin xử lý khiếu nại)", $currentOrder['id']);
                                        $userModel = new User();
                                        $userModel->createNotification($currentOrder['driver_id'], 'Cộng tiền cước vận chuyển', "Hệ thống đã cộng " . number_format($driverEarnings, 0, ',', '.') . "đ vào ví của bạn cho đơn #{$currentOrder['tracking_code']} (Quyết định từ Quản trị viên).", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                                    }
                                }

                                if ($newOrderStatus === 'cancelled' && ($currentOrder['payment_method'] ?? 'cash') === 'cash' && !empty($currentOrder['driver_id'])) {
                                    $feePerOrder = app_calculate_platform_fee((float)($currentOrder['shipping_fee'] ?? 0));
                                    $walletModel = new Wallet();
                                    $walletModel->add($currentOrder['driver_id'], $feePerOrder, 'refund', "Hoàn phí nền tảng đơn #{$currentOrder['tracking_code']} (Khách hủy)", $currentOrder['id']);
                                    $userModel = new User();
                                    $userModel->createNotification($currentOrder['driver_id'], 'Hoàn phí nền tảng', "Đơn #{$currentOrder['tracking_code']} đã được hủy. Hệ thống hoàn lại " . number_format($feePerOrder, 0, ',', '.') . "đ phí nền tảng vào ví của bạn.", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                                }

                                // NGHIỆP VỤ HOÀN TIỀN CƯỚC ONLINE TỰ ĐỘNG CHƯA TỪNG CÓ:
                                // Nếu khách hàng trả trước bằng ví/Online mà đơn bị lỗi/hủy/hoàn trả, Admin kết luận trả tiền cho khách
                                if (in_array($currentOrder['payment_method'] ?? '', ['transfer', 'online']) && ($currentOrder['payment_status'] ?? '') === 'paid' && in_array($newOrderStatus, ['cancelled', 'returning', 'returned'])) {
                                    
                                    // Cập nhật trạng thái tiền tệ sang 'refunded' và cập nhật 'refunded_at' qua Model đã sửa đổi
                                    $orderModel->updatePaymentStatus($currentOrder['id'], 'refunded');

                                    // Ghi nhận lịch sử luồng tiền tệ
                                    $db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")
                                       ->execute([$currentOrder['id'], $currentOrder['status'], "Hệ thống tự động kích hoạt hoàn trả tiền cước online (" . number_format($currentOrder['shipping_fee'] ?? 0, 0, ',', '.') . "đ) về phương thức thanh toán gốc của Khách hàng."]);

                                    // Bắn thông báo đẩy Toast / Notification chính thức trực quan cho Khách hàng
                                    $userModel->createNotification(
                                        $currentOrder['customer_id'],
                                        'Hoàn tiền đơn hàng thành công',
                                        "Số tiền cước phí " . number_format($currentOrder['shipping_fee'] ?? 0, 0, ',', '.') . "đ của đơn #{$currentOrder['tracking_code']} đã được hoàn duyệt thành công. Vui lòng kiểm tra tài khoản của bạn.",
                                        'system',
                                        "/user/orders/track/{$currentOrder['tracking_code']}"
                                    );
                                }
                            }

                            $userModel = new User();
                            if ($shouldPenalizeCustomer) {
                                $userModel->recordNoShow($currentOrder['customer_id']);
                                $userModel->createNotification($currentOrder['customer_id'], "Cảnh báo vi phạm", "Tài khoản của bạn đã bị ghi nhận vi phạm sau quá trình Quản trị viên xử lý khiếu nại đơn hàng #{$currentOrder['tracking_code']}.", 'system', "/user/orders/track/{$currentOrder['tracking_code']}");
                            } elseif ($shouldPenalizeDriver && !empty($currentOrder['driver_id'])) {
                                $penaltyModel = new DriverPenalty();
                                $reason = "Admin " . ($penaltyAmount > 0 ? "phạt" : "cảnh cáo") . " lỗi khiếu nại đơn #{$currentOrder['tracking_code']}";
                                
                                if ($penaltyModel->applyPenalty($currentOrder['driver_id'], 'customer_complaint', $penaltyAmount, $reason, $resolvedBy)) {
                                    if ($penaltyAmount > 0) {
                                        $currentBalance = (new Wallet())->getBalance($currentOrder['driver_id']);
                                        if ($currentBalance < 0) {
                                            $userModel->updateBlockStatus($currentOrder['driver_id'], 1);
                                            $db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")->execute([$currentOrder['id'], $currentOrder['status'], "Tài khoản tài xế đã tự động bị khóa do số dư ví âm sau khi bị phạt."]);
                                        }
                                        $userModel->createNotification($currentOrder['driver_id'], 'Thông báo chế tài', "Hệ thống đã khấu trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ do vi phạm quy định tại đơn #{$currentOrder['tracking_code']}.", 'wallet', "/driver/orders/view/{$currentOrder['id']}");
                                    } else {
                                        $userModel->createNotification($currentOrder['driver_id'], 'Cảnh cáo vi phạm', "Hệ thống đã ghi nhận 1 lần vi phạm (Cảnh cáo) tại đơn #{$currentOrder['tracking_code']} do có khiếu nại từ khách hàng.", 'system', "/driver/orders/view/{$currentOrder['id']}");
                                    }
                                }
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
                error_log('Dispute Handle Error: ' . $e->getMessage());
                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi xử lý khiếu nại. Vui lòng thử lại sau.';
            }
            return $response->redirect("/admin/disputes/view/{$id}");
        }

        $dispute = $disputeModel->findById($id);

        if (!$dispute) {
            $_SESSION['flash_error'] = 'Không tìm thấy khiếu nại này trên hệ thống.';
            return $response->redirect('/admin/disputes');
        }

        $dispute['status_label'] = Dispute::getStatusLabel($dispute['status'] ?? '');
        $dispute['reporter_role_label'] = User::getRoleLabel($dispute['reporter_role'] ?? '');

        return $response->render('admin/disputes/view', [
            'pageTitle' => 'Xử lý khiếu nại #' . $dispute['id'],
            'dispute' => $dispute
        ]);
    }
}
