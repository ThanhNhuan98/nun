<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\User;

class CronController extends BaseController
{
    /**
     * Chạy nghiệp vụ chống giam đơn (Auto-Reassignment)
     * Route: GET /cron/auto-reassign
     */
    public function autoReassign(Request $request, Response $response)
    {
        // Bảo mật: Chỉ cho phép chạy khi có mã khóa bí mật
        $secretKey = 'NUN_CRON_SECRET_2024';
        $data = $request->getBody();
        if (($data['key'] ?? '') !== $secretKey) {
            return $response->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $orderModel = new Order();
        $walletModel = new Wallet();
        $userModel = new User();
        
        // Lấy các đơn hàng đã được nhận quá 15 phút
        $stuckOrders = $orderModel->getStuckAcceptedOrders(15);
        $processedCount = 0;

        foreach ($stuckOrders as $order) {
            $orderId = $order['id'];
            $driverId = $order['driver_id'];
            
            if ($orderModel->autoReassignOrder($orderId)) {
                // 1. Phạt tiền tài xế (VD: 10.000đ)
                $penaltyAmount = 10000;
                $walletModel->deduct($driverId, $penaltyAmount, 'penalty', "Phạt giam đơn hàng #{$order['tracking_code']}");
                
                // 2. Gửi thông báo cảnh cáo cho tài xế
                $userModel->createNotification(
                    $driverId,
                    'Trừ tiền - Phạt giam đơn',
                    "Đơn #{$order['tracking_code']} bị thu hồi do bạn quá hạn không lấy hàng. Bạn bị trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ phạt.",
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