<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\DriverPenalty;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

class CronController extends BaseController
{
    // Chạy nghiệp vụ chống ôm đơn hàng
    public function autoReassign(Request $request, Response $response)
    {
        $secretKey = 'NUN_CRON_SECRET_2024';
        $data = $request->getBody();
        if (($data['key'] ?? '') !== $secretKey) {
            return $response->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $orderModel = new Order();
        $userModel = new User();
        $penaltyModel = new DriverPenalty();
        $settingModel = new Setting();
        $pickupTimeoutMinutes = max(1, (int) $settingModel->get('driver_pickup_timeout_minutes', 15));
        
        $stuckOrders = $orderModel->getStuckAcceptedOrders($pickupTimeoutMinutes);
        $processedCount = 0;

        foreach ($stuckOrders as $order) {
            $orderId = $order['id'];
            $driverId = $order['driver_id'];
            
            if ($orderModel->autoReassignOrder($orderId)) {
                $penaltyAmount = 10000;
                $penaltyApplied = $penaltyModel->applyPenalty(
                    $driverId,
                    'no_response',
                    $penaltyAmount,
                    "Phạt giam đơn hàng #{$order['tracking_code']}"
                );
                
                $userModel->createNotification(
                    $driverId,
                    'Thông báo chế tài',
                    "Hệ thống đã thu hồi đơn hàng #{$order['tracking_code']} do quá thời gian quy định không lấy hàng" . ($penaltyApplied ? ", đồng thời ghi nhận 1 vi phạm và khấu trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ từ ví của bạn." : ". Hệ thống chưa thể ghi nhận khoản phạt, vui lòng liên hệ quản trị viên."),
                    'wallet',
                    "/driver/history"
                );
                $processedCount++;
            }
        }

        return $response->json([
            'success' => true,
            'message' => 'Auto-reassign executed',
            'processed_count' => $processedCount
        ]);
    }
}
