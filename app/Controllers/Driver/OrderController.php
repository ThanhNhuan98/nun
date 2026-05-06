<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;

class OrderController extends BaseController
{
    /**
     * Helper xử lý upload ảnh minh chứng
     */
    private function uploadProofImage(int $orderId, string $prefix = ''): string
    {
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/proofs/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
            $prefixStr = $prefix ? "_{$prefix}" : '';
            $filename = 'proof_' . $orderId . $prefixStr . '_' . time() . '_' . uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $targetPath)) {
                $scriptName = dirname($_SERVER['SCRIPT_NAME']);
                $basePath = ($scriptName === '/' || $scriptName === '\\') ? '' : $scriptName;
                return $basePath . '/uploads/proofs/' . $filename;
            }
        }
        return '';
    }

    public function receiveOrders(Request $request, Response $response)
    {
        // Kiểm tra quyền (Nếu chưa đăng nhập hoặc không phải driver thì đẩy ra login)
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $orderModel = new Order();
        $userModel = new User();

        $dp = $userModel->getDriverProfile($driverId);
        $isVerified = !empty($dp['is_verified']);

        // Chỉ thực hiện AI Radar khi có request AJAX để tránh lỗi trang load quá lâu lúc đăng nhập
        if ($request->isGet() && isset($_GET['ajax'])) {
            // Tắt chức năng phân bổ đơn của AI nếu chưa xác thực giấy tờ
            if (!$isVerified) {
                return $response->json(['success' => true, 'batches' => []]);
            }

            // TỐI ƯU HÓA (DRY - Đừng lặp lại code):
            // Tái sử dụng lại hàm gọi AI đã được tách ra để chuẩn hóa kiến trúc
            $batches = $this->processAIRouting($driverId);
            
            return $response->json(['success' => true, 'batches' => $batches]);
        }

        if (!$isVerified && !isset($_SESSION['flash_error'])) {
            $_SESSION['flash_error'] = "LƯU Ý: Tài khoản của bạn chưa được Admin xác thực giấy tờ. Hệ thống sẽ không phân bổ đơn hàng cho đến khi bạn được phê duyệt.";
        }

        // Lấy số dư ví thực tế của tài xế
        $walletModel = new Wallet();
        $walletBalance = $walletModel->getBalance($driverId);

        return $response->render('driver/orders/receive', [
            'pageTitle' => 'Nhận đơn hàng mới',
            'walletBalance' => $walletBalance,
            'walletBanner' => null,
            'batches' => null // Khởi tạo null để render loading trên View, dữ liệu thật sẽ được fetch qua AJAX
        ]);
    }

    /**
     * Trích xuất logic gọi AI tối ưu hóa lộ trình ra một phương thức riêng
     */
    private function processAIRouting(int $driverId): array
    {
        $orderModel = new Order();
        $userModel = new User();

        $driverLocation = $userModel->getLocation($driverId);
        $orders = $orderModel->getPendingForDriver($driverId);
        
        $batches = [];
        
        if (empty($orders)) {
            return $batches;
        }

        $settingModel = new Setting();
        $globalMaxOrdersPerBatch = (int) $settingModel->get('max_orders_per_batch', 5);
        
        $dp = $userModel->getDriverProfile($driverId);
        
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $driverMaxOrders = $dp ? (int)$dp['max_concurrent_orders'] : $defaultMaxOrders;
        
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $driverMaxWeight = $dp ? (float)$dp['max_total_weight'] : $defaultMaxWeight;
        
        $activeCount = $orderModel->getDriverActiveOrderCount($driverId);
        $availableCapacity = max(0, $driverMaxOrders - $activeCount);
        
        $currentWeight = $orderModel->getDriverCurrentWeight($driverId);
        $availableWeightCapacity = max(0, $driverMaxWeight - $currentWeight);
        
        $maxOrdersPerBatch = min($globalMaxOrdersPerBatch, $availableCapacity);

        if ($maxOrdersPerBatch <= 0 || $availableWeightCapacity <= 0) {
            return [];
        }

        $validOrders = [];
        foreach ($orders as $o) {
            if ((float)($o['weight'] ?? 1.0) <= $availableWeightCapacity) {
                $validOrders[] = $o;
            }
        }
        $orders = $validOrders;

        if (empty($orders)) {
            return [];
        }

        $ordersData = array_map(function($o) {
            return [
                'id' => $o['id'],
                'lat' => (float)($o['sender_lat'] ?? 0),
                'lng' => (float)($o['sender_lng'] ?? 0),
                'weight' => (float)($o['weight'] ?? 1.0),
                'scheduled_at' => $o['scheduled_at'] ?? null,
                'created_at' => $o['created_at'] ?? null
            ];
        }, $orders);

        $aiData = [
            'driver_location' => $driverLocation,
            'orders' => $ordersData,
            'max_orders_per_batch' => $maxOrdersPerBatch,
            'max_weight_capacity' => $availableWeightCapacity
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'ai_batch_');
        file_put_contents($tempFile, json_encode($aiData));

        $scriptPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'ai' . DIRECTORY_SEPARATOR . 'route_optimizer.py';
        $command = escapeshellcmd("python " . $scriptPath . " --batch-file") . " " . escapeshellarg($tempFile) . " 2>&1";
        
        try {
            $output = shell_exec($command);
        } finally {
            @unlink($tempFile);
        }

        $result = json_decode($output, true);

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $orderMap = [];
            foreach ($orders as $o) $orderMap[$o['id']] = $o;

            foreach ($result['batches'] as $b) {
                $batchOrders = [];
                $totalFee = 0;
                $totalWeight = 0;
                foreach ($b['optimized_route'] as $oid) {
                    if (isset($orderMap[$oid])) {
                        $order = $orderMap[$oid];
                        $order['formatted_scheduled_at'] = !empty($order['scheduled_at']) 
                            ? date('H:i d/m/Y', strtotime($order['scheduled_at'])) 
                            : 'Càng sớm càng tốt';
                        $batchOrders[] = $order;
                        $totalFee += (float) ($order['shipping_fee'] ?? 0);
                        $totalWeight += (float) ($order['weight'] ?? 1.0);
                    }
                }
                $b['order_details'] = $batchOrders;
                $b['total_fee'] = $totalFee;
                $b['total_weight'] = $totalWeight;

                $totalTripDurationS = ($b['total_duration_s'] ?? 0) + ($b['access_duration_s'] ?? 0);
                $totalTripDurationMinutes = $totalTripDurationS > 0 ? $totalTripDurationS / 60 : 0;
                
                $efficiencyScore = 0;
                if ($totalTripDurationMinutes > 1) {
                    $efficiencyScore = $totalFee / $totalTripDurationMinutes;
                }

                $b['total_trip_duration_minutes'] = round($totalTripDurationMinutes);
                $b['access_duration_minutes'] = round(($b['access_duration_s'] ?? 0) / 60);
                $b['efficiency_score'] = round($efficiencyScore);

                $urgentTime = $b['most_urgent_time'] ?? '';
                $b['formatted_urgent_time'] = ($urgentTime && $urgentTime !== '9999-12-31 23:59:59') 
                    ? date('H:i d/m/Y', strtotime($urgentTime)) 
                    : 'Càng sớm càng tốt';

                $batches[] = $b;
            }
            usort($batches, function($a, $b) {
                $timeA = $a['most_urgent_time'] ?? '9999-12-31 23:59:59';
                $timeB = $b['most_urgent_time'] ?? '9999-12-31 23:59:59';
                if ($timeA !== $timeB) {
                    return strcmp($timeA, $timeB);
                }
                return ($b['efficiency_score'] ?? 0) <=> ($a['efficiency_score'] ?? 0);
            });
        } else {
            error_log("AI Route Optimizer Error: " . print_r($output, true));
            foreach ($orders as $o) {
                $o['formatted_scheduled_at'] = !empty($o['scheduled_at']) 
                    ? date('H:i d/m/Y', strtotime($o['scheduled_at'])) 
                    : 'Càng sớm càng tốt';
                $batches[] = [
                    'batch_id' => 'ĐƠN LẺ',
                    'order_ids' => [$o['id']],
                    'optimized_route' => [$o['id']],
                    'total_orders' => 1,
                    'total_fee' => (float)($o['shipping_fee'] ?? 0),
                    'total_weight' => (float)($o['weight'] ?? 1.0),
                    'order_details' => [$o],
                    'total_trip_duration_minutes' => 'N/A',
                    'access_duration_minutes' => 'N/A',
                    'efficiency_score' => 0,
                    'most_urgent_time' => $o['scheduled_at'] ?? $o['created_at'] ?? '9999-12-31 23:59:59',
                    'formatted_urgent_time' => !empty($o['scheduled_at']) ? date('H:i d/m/Y', strtotime($o['scheduled_at'])) : 'Càng sớm càng tốt'
                ];
            }
            
            usort($batches, function($a, $b) {
                $timeA = $a['most_urgent_time'] ?? '9999-12-31 23:59:59';
                $timeB = $b['most_urgent_time'] ?? '9999-12-31 23:59:59';
                return strcmp($timeA, $timeB);
            });
        }
        return $batches;
    }

    public function acceptOrder(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();

        $data = $request->getBody();
        
        // Nhận vào mảng các order_ids thay vì 1 id
        // Dùng $_POST trực tiếp để tránh lỗi hàm getBody() tự động lọc mất mảng
        $orderIds = $_POST['order_ids'] ?? $data['order_ids'] ?? [];
        if (isset($data['order_id'])) {
            $orderIds[] = $data['order_id'];
        }
        $orderIds = array_values(array_unique(array_filter(array_map('intval', (array) $orderIds))));
        
        if (empty($orderIds)) {
            $_SESSION['flash_error'] = "Chưa có đơn hàng nào được chọn.";
            return $response->redirect('/driver/receive-orders');
        }

        $orderModel = new Order();

        // === TỐI ƯU HÓA: KIỂM TRA GIỚI HẠN TÀI XẾ BẰNG BATCH (Xóa N+1 Query) ===
        $validationResult = $orderModel->canDriverAcceptMultipleOrders($driverId, $orderIds);
        $validOrderIds = $validationResult['valid_orders'];
        $rejectedOrders = $validationResult['rejected_orders'];

        if (!empty($rejectedOrders)) {
            $errorMsg = "Không thể nhận chuyến ghép này do có đơn hàng không đủ điều kiện: " . (array_values($rejectedOrders)[0] ?? 'Lỗi không xác định');
            $_SESSION['flash_error'] = $errorMsg;
            return $response->redirect('/driver/receive-orders');
        }

        // --- LOGIC KIỂM TRA VÀ TRỪ VÍ ---
        $walletModel = new Wallet();
        $settingModel = new Setting();
        $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);

        $shippingFees = $orderModel->getShippingFees($validOrderIds);

        $orderFees = [];
        $totalDeduction = 0;
        foreach ($shippingFees as $row) {
            $fee = (int) $row['shipping_fee'];
            // Chỉ thu trước phí nền tảng nếu khách trả Tiền mặt
            if (($row['payment_method'] ?? 'cash') === 'cash') {
                $deduction = (int) ceil($fee * $platformFeePercent / 100);
            } else {
                $deduction = 0;
            }
            $orderFees[$row['order_id']] = $deduction;
            $totalDeduction += $deduction;
        }

        if ($totalDeduction > 0) {
            if (!$walletModel->deduct($driverId, $totalDeduction, 'platform_fee', 'Order acceptance fee')) {
                $_SESSION['flash_error'] = "Số dư ví không đủ để nhận chuyến này (Cần " . number_format($totalDeduction, 0, ',', '.') . "đ phí nền tảng). Vui lòng nạp thêm tiền.";
                return $response->redirect('/driver/receive-orders');
            }
        }

        // === TỐI ƯU HÓA: GÁN NHIỀU ĐƠN HÀNG TRONG 1 TRANSACTION ===
        $assignedIds = $orderModel->assignMultipleOrdersToDriver($validOrderIds, $driverId);
        $successCount = count($assignedIds);

        if ($successCount === count($validOrderIds) && $successCount > 0) {
            if ($totalDeduction > 0) {
                $_SESSION['flash_success'] = "Đã nhận thành công cụm $successCount đơn hàng! Hệ thống đã trừ " . number_format($totalDeduction, 0, ',', '.') . "đ phí nhận đơn.";
                
                // Gửi thông báo trừ tiền ví cho tài xế
                $userModel = new User();
                $userModel->createNotification($driverId, 'Trừ phí nhận đơn', "Bạn đã bị trừ " . number_format($totalDeduction, 0, ',', '.') . "đ phí nền tảng khi nhận " . $successCount . " đơn hàng.", 'wallet', '/driver/active-orders');
            } else {
                $_SESSION['flash_success'] = "Đã nhận thành công cụm $successCount đơn hàng!";
            }

            // Sau khi nhận đơn thành công, chuyển hướng đến trang "Đơn đang chạy"
            return $response->redirect('/driver/active-orders');
        } else {
            // Hoàn tiền lại toàn bộ nếu quá trình gán đơn thất bại (VD: 1 đơn đã bị người khác giành mất)
            if ($totalDeduction > 0) {
                $walletModel->add($driverId, $totalDeduction, 'refund', 'Refund failed order acceptance fee');
                $userModel = new User();
                $userModel->createNotification($driverId, 'Cộng tiền - Hoàn phí', "Hệ thống hoàn lại " . number_format($totalDeduction, 0, ',', '.') . "đ do gán chuyến ghép thất bại.", 'wallet', '/driver/receive-orders');
            }
            $_SESSION['flash_error'] = "Nhận chuyến ghép thất bại! Có thể một đơn hàng trong cụm ghép này đã bị tài xế khác nhận trước.";
            return $response->redirect('/driver/receive-orders');
        }
    }

    public function history(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $query = $request->getBody();
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $startDate = trim($query['start_date'] ?? '');
        $endDate = trim($query['end_date'] ?? '');

        $driverId = $this->userId();
        $orderModel = new Order();
        
        $totalOrders = $orderModel->countDriverHistory($driverId, $startDate, $endDate);
        $orders = $orderModel->getDriverHistory($driverId, $perPage, $offset, $startDate, $endDate);
        $totalPages = (int) ceil($totalOrders / $perPage);

        return $response->render('driver/orders/history', [
            'pageTitle' => 'Lịch sử chuyến đi',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function activeOrders(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $orderModel = new Order();
        
        $activeOrders = $orderModel->getActiveOrdersForDriver($driverId);

        // Nhóm các đơn hàng theo thời gian nhận (accepted_at) để phân loại Đơn ghép / Đơn lẻ
        $groupedOrders = [];
        foreach ($activeOrders as $order) {
            $orderTimeStr = $order['accepted_at'] ?? $order['scheduled_at'] ?? $order['created_at'];
            $orderTime = strtotime($orderTimeStr);
            
            $foundGroupKey = null;
            foreach (array_keys($groupedOrders) as $existingKeyStr) {
                $existingTime = strtotime($existingKeyStr);
                // Gộp các đơn hàng được nhận cách nhau không quá 5 giây thành 1 chuyến ghép
                if (abs($orderTime - $existingTime) <= 5) {
                    $foundGroupKey = $existingKeyStr;
                    break;
                }
            }

            if ($foundGroupKey !== null) {
                $groupedOrders[$foundGroupKey][] = $order;
            } else {
                $groupedOrders[$orderTimeStr] = [$order];
            }
        }

        // Hiện tại, trang này sẽ hiển thị danh sách các đơn đang chạy theo thứ tự ưu tiên.
        // "Lộ trình hoàn hảo" được thể hiện qua việc sắp xếp các công việc cần làm.
        // Việc tích hợp bản đồ và tối ưu hóa lộ trình real-time sẽ được phát triển trong giai đoạn sau.

        return $response->render('driver/orders/active', [
            'pageTitle' => 'Đơn hàng đang chạy',
            'groupedOrders' => $groupedOrders,
            'totalActive' => count($activeOrders)
        ]);
    }

    public function viewOrder(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.";
            return $response->redirect('/driver/history');
        }

        return $response->render('driver/orders/view', [
            'pageTitle' => 'Chi tiết chuyến đi #' . $order['tracking_code'],
            'order' => $order
        ]);
    }

    public function updateStatus(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $data = $request->getBody();
        $newStatus = $_POST['status'] ?? $data['status'] ?? '';
        $cancelReason = app_sanitize($_POST['cancel_reason'] ?? $data['cancel_reason'] ?? '');

        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
            return $response->redirect('/driver/history');
        }

        // Xác định luồng trạng thái hợp lệ
        $allowedTransitions = [
            'accepted' => ['picking_up', 'cancelled'],
            'picking_up' => ['in_transit', 'cancelled'],
            'in_transit' => ['completed', 'cancelled', 'returning'],
            'shipping' => ['completed', 'cancelled', 'returning'] // Phòng hờ nếu DB đang dùng chữ shipping
        ];

        $currentStatus = $order['status'];
        
        if (isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus])) {
            
            // Xử lý upload ảnh minh chứng (nếu có)
            $proofImagePath = $this->uploadProofImage($orderId);
            
            // Bắt buộc phải có ảnh khi Giao xong, Hủy đơn hoặc Chuyển hoàn
            if (in_array($newStatus, ['completed', 'cancelled', 'returning']) && empty($proofImagePath)) {
                $_SESSION['flash_error'] = "Lỗi: Bạn bắt buộc phải chụp và tải lên ảnh minh chứng!";
                return $response->redirect("/driver/orders/view/$orderId");
            }

            $description = "Tài xế đã cập nhật trạng thái đơn hàng thành: " . $newStatus;
            if ($newStatus === 'completed') {
                $description = "Tài xế đã giao hàng thành công.";
            } elseif ($newStatus === 'cancelled') {
                if (in_array($currentStatus, ['accepted', 'picking_up'])) {
                    $description = "Tài xế báo cáo lấy hàng thất bại / Hủy đơn. Lý do: " . $cancelReason;
                } else {
                    $description = "Tài xế báo cáo giao thất bại / Hủy đơn. Lý do: " . $cancelReason;
                }
            } elseif ($newStatus === 'returning') {
                $description = "Tài xế báo cáo giao thất bại. Đơn hàng đang được chuyển hoàn. Lý do: " . $cancelReason;
            }
            
            // Đính kèm ảnh vào dòng Lịch sử để Admin / Khách hàng thấy được
            if (!empty($proofImagePath)) {
                $description .= "<br><br><div class='proof-image-wrapper'><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image'></a></div>";
            }

            // Bắt đầu transaction để đảm bảo tính toàn vẹn
            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                if ($orderModel->updateStatus($orderId, $newStatus, $description)) {
                    $successMessage = "Cập nhật trạng thái thành công!";

                    // Thanh toán tiền công cho tài xế nếu đơn chuyển khoản giao thành công
                    if ($newStatus === 'completed' && ($order['payment_method'] ?? 'cash') === 'transfer') {
                        $walletModel = new Wallet();
                        $settingModel = new Setting();
                        $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
                        $feePerOrder = (int) ceil(($order['shipping_fee'] ?? 0) * $platformFeePercent / 100);
                        $driverEarnings = (int) ($order['shipping_fee'] ?? 0) - $feePerOrder;
                        
                        if ($driverEarnings > 0) {
                            $walletModel->add($driverId, $driverEarnings);
                            $successMessage = "Giao hàng thành công. Bạn được cộng " . number_format($driverEarnings, 0, ',', '.') . "đ (tiền cước - phí) vào ví.";
                            
                            $userModel = new User();
                            $userModel->createNotification(
                                $driverId,
                                'Cộng tiền cước',
                                "Bạn đã được cộng " . number_format($driverEarnings, 0, ',', '.') . "đ cho đơn hàng #{$order['tracking_code']} (Khách thanh toán chuyển khoản).",
                                'wallet',
                                "/driver/orders/view/$orderId"
                            );
                        }
                    }

                    // Hoàn lại phí nền tảng nếu tài xế hủy đơn
                    if ($newStatus === 'cancelled') {
                        $successMessage = "Đã hủy đơn.";
                        // Chỉ hoàn phí nếu trước đó đơn là Tiền mặt (đã bị trừ)
                        if (($order['payment_method'] ?? 'cash') === 'cash') {
                            $walletModel = new Wallet();
                            $settingModel = new Setting();
                            $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
                            $feePerOrder = (int) ceil(($order['shipping_fee'] ?? 0) * $platformFeePercent / 100);
                            $walletModel->add($driverId, $feePerOrder);
                            $successMessage .= " Phí nhận đơn " . number_format($feePerOrder, 0, ',', '.') . "đ đã được hoàn lại vào ví của bạn.";

                            $userModel = new User();
                            $userModel->createNotification($driverId, 'Hoàn phí nhận đơn', "Bạn đã được cộng " . number_format($feePerOrder, 0, ',', '.') . "đ (hoàn phí nền tảng) cho đơn hàng #{$order['tracking_code']} do đơn bị hủy.", 'wallet', "/driver/orders/view/$orderId");
                        }
                    }

                    // GỬI THÔNG BÁO CHO KHÁCH HÀNG
                    $userModel = new User();
                    $userModel->createNotification(
                        $order['customer_id'],
                        "Cập nhật đơn hàng #{$order['tracking_code']}",
                        "Tài xế đã cập nhật trạng thái thành: " . Order::getStatusLabel($newStatus),
                        'order',
                        "/user/orders/track/{$order['tracking_code']}"
                    );
                    
                    $db->commit();
                    $_SESSION['flash_success'] = $successMessage;
                }
            } catch (\Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // Ghi log lỗi để debug: error_log($e->getMessage());
                $_SESSION['flash_error'] = "Có lỗi xảy ra, không thể cập nhật trạng thái.";
            }
        } else {
            $_SESSION['flash_error'] = "Chuyển đổi trạng thái không hợp lệ.";
        }

        return $response->redirect("/driver/orders/view/$orderId");
    }

    /**
     * Xử lý tài xế báo cáo khách bom hàng
     * Route: POST /driver/orders/report-noshow/{id}
     */
    public function reportNoShow(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $data = $request->getBody();
        $reason = app_sanitize($_POST['reason'] ?? $data['reason'] ?? 'Khách hàng không xuất hiện (Bom hàng)');

        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
            return $response->redirect('/driver/history');
        }

        // Bắt buộc phải có ảnh khi báo cáo bom hàng
        $proofImagePath = $this->uploadProofImage($orderId, 'noshow');

        if (empty($proofImagePath)) {
            $_SESSION['flash_error'] = "Lỗi: Bạn bắt buộc phải chụp và tải lên ảnh minh chứng tại địa điểm giao/nhận để báo cáo khách bom hàng!";
            return $response->redirect("/driver/orders/view/$orderId");
        }

        $description = "Tài xế báo cáo KHÁCH BOM HÀNG. Lý do: " . $reason;
        $description .= "<br><br><div class='proof-image-wrapper'><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image'></a></div>";

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Nếu đang đi lấy hàng (accepted/picking_up) -> Hủy đơn
            // Nếu đang đi giao hàng (in_transit/shipping) -> Chuyển hoàn (returning)
            $newStatus = in_array($order['status'], ['accepted', 'picking_up']) ? 'cancelled' : 'returning';

            if ($orderModel->updateStatus($orderId, $newStatus, $description)) {
                // 1. Kích hoạt hàm phạt khách hàng (viết sẵn trong Model)
                $userModel = new User();
                $userModel->recordNoShow($order['customer_id']);

                // 2. Hoàn lại phí nền tảng cho tài xế
                $walletModel = new Wallet();
                $settingModel = new Setting();
                $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
                $feePerOrder = (int) ceil(($order['shipping_fee'] ?? 0) * $platformFeePercent / 100);
                
                if (($order['payment_method'] ?? 'cash') === 'cash') {
                    $walletModel->add($driverId, $feePerOrder);

                    $userModel->createNotification(
                        $driverId,
                        'Cộng tiền - Hoàn phí',
                        "Bạn đã được cộng " . number_format($feePerOrder, 0, ',', '.') . "đ (hoàn phí nền tảng) cho đơn hàng #{$order['tracking_code']} do khách bom hàng.",
                        'wallet',
                        "/driver/orders/view/$orderId"
                    );
                    $_SESSION['flash_success'] = "Đã báo cáo khách bom hàng thành công! Phí " . number_format($feePerOrder, 0, ',', '.') . "đ đã được hoàn lại ví.";
                } else {
                    $_SESSION['flash_success'] = "Đã báo cáo khách bom hàng thành công!";
                }

                // 3. Gửi thông báo cảnh cáo cho khách hàng
                $userModel->createNotification(
                    $order['customer_id'],
                    "Cảnh báo: Báo cáo bom hàng",
                    "Tài xế đã báo cáo bạn không giao/nhận hàng cho đơn #{$order['tracking_code']}. Hệ thống đã tự động ghi nhận 1 lần vi phạm của bạn.",
                    'system',
                    "/user/orders/track/{$order['tracking_code']}"
                );
                
                if ($db->inTransaction()) {
                    $db->commit();
                }
            }
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Có lỗi xảy ra, không thể gửi báo cáo.";
        }

        return $response->redirect("/driver/orders/view/$orderId");
    }

    /**
     * API: Cập nhật vị trí GPS liên tục của tài xế
     * Route: POST /api/driver/update-location
     */
    public function updateLocation(Request $request, Response $response)
    {
        // Chỉ cho phép tài xế đã đăng nhập
        if (!\app_has_role('driver')) {
            return $response->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Đọc dữ liệu JSON từ body của request
        $data = $request->getJsonBody();
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;

        if ($lat !== null && $lng !== null) {
            $userModel = new User();
            $success = $userModel->updateLocation($this->userId(), $lat, $lng);
            
            return $response->json(['success' => $success]);
        }

        return $response->json(['success' => false, 'message' => 'Thiếu tọa độ.'], 400);
    }

    /**
     * API: Kiểm tra có đơn hàng mới không (Dùng cho AJAX Polling)
     * Route: GET /api/driver/check-new-orders
     */
    public function apiCheckNewOrders(Request $request, Response $response)
    {
        if (!\app_has_role('driver')) {
            return $response->json(['success' => false], 403);
        }

        $orderModel = new Order();

        // Tự động dọn dẹp/hủy các đơn hàng đã treo quá 1 ngày không có tài xế nhận
        $orderModel->autoCancelExpiredPendingOrders();

        // TỐI ƯU HÓA: Dùng hàm COUNT trực tiếp trong SQL thay vì kéo toàn bộ array về PHP
        $count = $orderModel->countPendingOrders();

        return $response->json(['success' => true, 'count' => $count]);
    }
}
