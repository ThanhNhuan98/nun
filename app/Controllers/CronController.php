<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\User;

class CronController extends BaseController
{
    // Chạy nghiệp vụ chống ôm đơn
    public function autoReassign(Request $request, Response $response)
    {
        $secretKey = 'NUN_CRON_SECRET_2024';
        $data = $request->getBody();
        if (($data['key'] ?? '') !== $secretKey) {
            return $response->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $orderModel = new Order();
        $walletModel = new Wallet();
        $userModel = new User();
        
        $stuckOrders = $orderModel->getStuckAcceptedOrders(15);
        $processedCount = 0;

        foreach ($stuckOrders as $order) {
            $orderId = $order['id'];
            $driverId = $order['driver_id'];
            
            if ($orderModel->autoReassignOrder($orderId)) {
                $penaltyAmount = 10000;
                $walletModel->deduct($driverId, $penaltyAmount, 'penalty', "Phạt giam đơn hàng #{$order['tracking_code']}");
                
                $userModel->createNotification(
                    $driverId,
                    'Thông báo chế tài',
                    "Hệ thống đã thu hồi đơn hàng #{$order['tracking_code']} do quá thời gian quy định không lấy hàng, đồng thời khấu trừ " . number_format($penaltyAmount, 0, ',', '.') . "đ từ ví của bạn.",
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
