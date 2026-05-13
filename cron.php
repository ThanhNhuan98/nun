<?php

/**
 * Tiện ích Cron-job chạy độc lập trên Server (Command Line)
 * Lệnh chạy trên Linux/Windows: php cron.php
 */

// 1. Khởi tạo môi trường hệ thống
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Helpers/helpers.php';

if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use App\Models\Order;
use App\Models\User;

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu chạy tiến trình nền Cron-job...\n";

$orderModel = new Order();
$userModel = new User();

// 2. Tự động hủy các đơn hàng chờ quá 24h không có tài xế nhận
if ($orderModel->autoCancelExpiredPendingOrders()) {
    echo "- [Thành công] Đã hủy các đơn hàng quá hạn 24h không có ai nhận.\n";
}

// 3. Tự động thu hồi đơn hàng tài xế giam quá lâu (15 phút không đi lấy)
$stuckOrders = $orderModel->getStuckAcceptedOrders(15);
if (!empty($stuckOrders)) {
    foreach ($stuckOrders as $stuckOrder) {
        if ($orderModel->autoReassignOrder($stuckOrder['id'])) {
            $userModel->createNotification($stuckOrder['driver_id'], 'Cảnh báo: Thu hồi đơn hàng', "Hệ thống đã tự động thu hồi đơn #{$stuckOrder['tracking_code']} do bạn không đi lấy hàng sau 15 phút. Bạn đã mất phí nhận đơn.", 'system', '/driver/history');
            echo "- [Cảnh báo] Đã thu hồi đơn hàng #{$stuckOrder['tracking_code']} từ tài xế ID {$stuckOrder['driver_id']}.\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Hoàn thành Cron-job.\n";