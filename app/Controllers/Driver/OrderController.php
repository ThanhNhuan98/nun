<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OsrmService;
use App\Models\User;
use App\Models\Wallet;

class OrderController extends BaseController
{
    private const PREPAID_PAYMENT_METHODS = ['transfer', 'bank_transfer', 'wallet'];

    private function normalizeFailureReason(string $reason): string
    {
        return $reason === 'banned_goods' ? 'Phát hiện Hàng cấm' : $reason;
    }

    private function isBannedGoodsReason(string $reason): bool
    {
        return $reason === 'banned_goods'
            || str_contains($reason, 'Hàng cấm')
            || str_contains($reason, 'Hang cam');
    }

    // Tải ảnh minh chứng giao nhận trực tiếp lên Cloudinary SDK theo cam kết trong báo cáo.
    private function uploadProofImage(int $orderId, string $prefix = ''): string
    {
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $validation = app_validate_uploaded_image($_FILES['proof_image']);
            if (!$validation['valid']) {
                throw new \RuntimeException($validation['error'] ?? 'Ảnh minh chứng không hợp lệ.');
            }
            
            // Nén ảnh cục bộ bằng GD Library trước khi đẩy qua mạng tới Cloudinary
            if (function_exists('app_compress_image_before_upload')) {
                app_compress_image_before_upload($_FILES['proof_image']['tmp_name']);
            }

            return $this->uploadToCloudinary($_FILES['proof_image'], 'nun_express/proofs', 'proof_' . $orderId . '_' . ($prefix ?: 'delivery') . '_' . time());
        }
        return '';
    }

    // Hiển thị màn hình Radar và phân bổ danh sách đơn hàng/chuyến ghép cho tài xế dựa trên AI.
    public function receiveOrders(Request $request, Response $response)
    {
        $query = $request->getBody();
        $driverId = $this->userId();
        $orderModel = new Order();
        $userModel = new User();

        // Xử lý nhanh lệnh Bật/Tắt trạng thái Online mà không cần cấu hình thêm Route
        if (isset($query['toggle_online'])) {
            $isOnlineToggle = (int) $query['toggle_online'];
            $userModel->updateOnlineStatus($driverId, $isOnlineToggle);
            $_SESSION['flash_success'] = $isOnlineToggle ? 'Đã BẬT trạng thái Sẵn sàng nhận đơn!' : 'Đã TẮT nhận đơn!';
            return $response->redirect('/driver/receive-orders');
        }

        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $dp = $userModel->getDriverProfile($driverId);
        $isVerified = !empty($dp['is_verified']);
        $isOnline = !empty($dp['is_online']);

        if (!$isVerified && !isset($_SESSION['flash_error'])) {
            $_SESSION['flash_error'] = "LƯU Ý: Tài khoản của bạn chưa được Admin xác thực giấy tờ. Hệ thống sẽ không phân bổ đơn hàng cho đến khi bạn được phê duyệt.";
        }

        $routingResult = ($isVerified && $isOnline) ? $this->processAIRouting($driverId) : ['batches' => [], 'message' => !$isVerified ? 'Tài khoản chưa được xác thực.' : 'Bạn đang TẮT trạng thái nhận đơn. Hãy bật công tắc "Sẵn sàng" để hệ thống quét chuyến đi.'];

        $allBatches = $routingResult['batches'] ?? [];
        $totalBatches = count($allBatches);
        $totalPages = (int) ceil($totalBatches / $perPage);
        $paginatedBatches = array_slice($allBatches, $offset, $perPage);

        $walletModel = new Wallet();
        $walletBalance = $walletModel->getBalance($driverId);

        return $response->render('driver/orders/receive', [
            'pageTitle' => 'Nhận đơn hàng mới',
            'walletBalance' => $walletBalance,
            'batches' => $paginatedBatches,
            'message' => $routingResult['message'] ?? '',
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'isOnline' => $isOnline
        ]);
    }

    // Gọi kịch bản Python AI (Route Optimizer) để gom cụm và tối ưu lộ trình các đơn hàng cho tài xế.
    private function processAIRouting(int $driverId): array
    {
        $orderModel = new Order();
        $userModel = new User();

        $driverLocation = $userModel->getLocation($driverId);
        if (!$driverLocation || empty($driverLocation['lat']) || empty($driverLocation['lng'])) {
            return ['batches' => [], 'message' => 'Không tìm thấy vị trí của bạn. Vui lòng bật định vị GPS và cấp quyền cho trình duyệt để hệ thống có thể ghép chuyến.'];
        }

        // Truyền tọa độ tài xế để Database chặn đứng các đơn hàng quá xa (>20km)
        $orders = $orderModel->getPendingForDriver($driverId, $driverLocation, 20);
        
        $batches = [];
        
        if (empty($orders)) {
            return ['batches' => [], 'message' => 'Hiện tại khu vực của bạn không có đơn hàng mới nào đang chờ.'];
        }

        $settingModel = new Setting();
        $globalMaxOrdersPerBatch = (int) $settingModel->get('max_orders_per_batch', 5);
        
        $dp = $userModel->getDriverProfile($driverId);
        
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $driverMaxOrders = $dp ? (int)$dp['max_concurrent_orders'] : $defaultMaxOrders;
        
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $driverMaxWeight = $dp ? (float)$dp['max_total_weight'] : $defaultMaxWeight;
        $fastMaxOrders = (int) $settingModel->get('fast_max_orders', 3);
        
        // Thay thế 3 truy vấn SQL rời rạc bằng 1 truy vấn tổng hợp duy nhất để giảm tải kết nối DB
        $activeStats = $orderModel->getDriverActiveStats($driverId);
        $activeCount = (int) $activeStats['active_count'];
        $hasExpress = (bool) $activeStats['has_express'];
        $hasFast = (bool) ($activeStats['has_fast'] ?? 0);
        $currentWeight = (float) $activeStats['current_weight'];
        
        if ($hasExpress) {
            return ['batches' => [], 'message' => 'Bạn đang thực hiện đơn Siêu tốc (độc quyền). Radar tự động ẩn các đơn khác để đảm bảo chất lượng dịch vụ.'];
        }

        // Nếu tài xế đang giữ đơn Giao nhanh, tự động siết số lượng đơn tối đa trên Radar.
        if ($hasFast) {
            $driverMaxOrders = min($driverMaxOrders, $fastMaxOrders);
        }
        
        $availableCapacity = max(0, $driverMaxOrders - $activeCount);
        $availableWeightCapacity = max(0, $driverMaxWeight - $currentWeight);
        
        $vehicleSpeed = (float) $settingModel->get('vehicle_speed_kmh', 28.0);
        
        $maxOrdersPerBatch = min($globalMaxOrdersPerBatch, $availableCapacity);

        if ($maxOrdersPerBatch <= 0) {
            return ['batches' => [], 'message' => "Bạn đã đạt giới hạn nhận đơn ({$activeCount}/{$driverMaxOrders} đơn). Vui lòng hoàn thành bớt các đơn đang chạy."];
        }
        if ($availableWeightCapacity <= 0) {
            return ['batches' => [], 'message' => "Xe của bạn đã đầy tải trọng ({$currentWeight}/{$driverMaxWeight}kg). Vui lòng giao bớt hàng trước khi nhận thêm."];
        }

        $validOrders = [];
        $skippedExpressCount = 0;
        $skippedWeightCount = 0;
        foreach ($orders as $o) {
            $isExpress = ($o['shipping_method'] ?? 'standard') === 'express';
            if ($activeCount > 0 && $isExpress) {
                $skippedExpressCount++;
                continue;
            }
            
            if ((float)($o['weight'] ?? 1.0) <= $availableWeightCapacity) {
                $validOrders[] = $o;
            } else {
                $skippedWeightCount++;
            }
        }
        $orders = $validOrders;

        if (empty($orders)) {
            $msg = 'Không có đơn hàng mới nào phù hợp với xe của bạn lúc này.';
            if ($skippedExpressCount > 0 && $skippedWeightCount == 0) {
                $msg = "Có {$skippedExpressCount} đơn Siêu tốc xung quanh, nhưng bạn đang giữ đơn Tiêu chuẩn nên Radar ẩn đi để tránh làm trễ giờ khách hàng.";
            } elseif ($skippedWeightCount > 0) {
                $msg = "Có {$skippedWeightCount} đơn hàng xung quanh nhưng khối lượng vượt quá tải trọng còn trống của bạn ({$availableWeightCapacity}kg).";
            }
            return ['batches' => [], 'message' => $msg];
        }

        $aiData = [
            'driver_location' => $driverLocation,
            'orders' => $orders,
            'max_orders_per_batch' => $maxOrdersPerBatch,
            'max_weight_capacity' => $availableWeightCapacity,
            'vehicle_speed' => $vehicleSpeed,
            'fast_max_orders' => $fastMaxOrders
        ];

        // Thay thế shell_exec bằng Microservice API (FastAPI)
        // Điều này ngăn chặn việc tràn bộ nhớ và nghẽn I/O ổ đĩa
        $aiServiceUrl = $_ENV['AI_SERVICE_URL'] ?? 'http://127.0.0.1:8000/api/v1/optimize-routes';
        
        $ch = curl_init($aiServiceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aiData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Nếu server Python sập, báo lỗi ngay lập tức sau 2s thay vì chờ đợi
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Tăng Timeout lên 15s để AI có đủ thời gian xử lý nhiều cụm đơn
        
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Lỗi gọi AI Microservice: ' . curl_error($ch));
        } else {
            // Ghi log phản hồi từ Python để dễ dàng debug nếu Radar không ghép đơn
            error_log('Phản hồi từ AI Microservice: ' . $output);
        }
        curl_close($ch);
        
        $result = $output ? json_decode($output, true) : null;

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $orderMap = [];
            foreach ($orders as $o) {
                $o['formatted_scheduled_at'] = !empty($o['scheduled_at']) 
                    ? date('H:i d/m/Y', strtotime($o['scheduled_at'])) 
                    : 'Càng sớm càng tốt';
                $o['shipping_method_label'] = \App\Models\Order::getShippingMethodLabel($o['shipping_method'] ?? null);
                $o['shipping_method_color'] = \App\Models\Order::getShippingMethodColor($o['shipping_method'] ?? null);
                $orderMap[$o['id']] = $o;
            }
            
            $osrmService = new OsrmService();

            foreach ($result['batches'] as &$batch) {
                $totalFee = 0;
                $totalWeight = 0;
                $orderDetails = [];
                foreach ($batch['order_ids'] as $oid) {
                    if (isset($orderMap[$oid])) {
                        $order = $orderMap[$oid];
                        $totalFee += (float) ($order['shipping_fee'] ?? 0);
                        $totalWeight += (float) ($order['weight'] ?? 1.0);
                        $orderDetails[] = $order;
                    }
                }
                $batch['total_fee'] = $totalFee;
                $batch['total_weight'] = $totalWeight;
                $batch['order_details'] = $orderDetails;

                // Cập nhật thêm thông tin phương thức giao hàng vào route_details để View dễ hiển thị
                foreach ($batch['route_details'] as &$route) {
                    if (isset($orderMap[$route['order_id']])) {
                        $route['shipping_method_label'] = $orderMap[$route['order_id']]['shipping_method_label'];
                        $route['shipping_method_color'] = $orderMap[$route['order_id']]['shipping_method_color'];
                    }
                }
                unset($route);

                // Tính thời gian tiếp cận (access_duration_s) dựa trên route_details
                $access_duration_s = 0;
                $firstStep = $batch['route_details'][0] ?? null;

                if ($driverLocation && $firstStep) {
                    $firstOrderId = $firstStep['order_id'];
                    $firstOrderData = $orderMap[$firstOrderId] ?? null;

                    if ($firstOrderData) {
                        $firstPointLat = ($firstStep['type'] === 'pickup') 
                            ? $firstOrderData['sender_lat'] 
                            : $firstOrderData['receiver_lat'];
                        $firstPointLng = ($firstStep['type'] === 'pickup') 
                            ? $firstOrderData['sender_lng'] 
                            : $firstOrderData['receiver_lng'];

                        // Dùng công thức Haversine tính toán đường chim bay
                        // tránh gọi API OSRM nhiều lần gây giật lag
                        $distance_km = $osrmService->haversineDistance(
                            (float)$driverLocation['lat'], (float)$driverLocation['lng'],
                            (float)$firstPointLat, (float)$firstPointLng
                        );
                        $access_duration_s = (int) (($distance_km / $vehicleSpeed) * 3600);
                    }
                }
                $batch['access_duration_s'] = $access_duration_s;

                $totalTripDurationS = ($batch['total_duration_s'] ?? 0) + $access_duration_s;
                $totalTripDurationMinutes = $totalTripDurationS > 0 ? $totalTripDurationS / 60 : 0;
                
                $efficiencyScore = 0;
                if ($totalTripDurationMinutes > 1) {
                    $efficiencyScore = $totalFee / $totalTripDurationMinutes;
                }
                $batch['total_trip_duration_minutes'] = round($totalTripDurationMinutes);
                $batch['access_duration_minutes'] = round($access_duration_s / 60);
                $batch['efficiency_score'] = round($efficiencyScore);

                $urgentTime = $batch['most_urgent_time'] ?? '';
                $batch['formatted_urgent_time'] = ($urgentTime && $urgentTime !== '9999-12-31 23:59:59') 
                    ? date('H:i d/m/Y', strtotime($urgentTime)) 
                    : 'Càng sớm càng tốt';

                $batches[] = $batch;
            }
            unset($batch);

            // Sắp xếp đa tầng các chuyến ghép để hiển thị cho tài xế
            usort($batches, function($a, $b) {
                // Cấp 1: Ưu tiên theo loại hình dịch vụ (Express > Fast > Standard)
                $prioA = $a['priority'] ?? 2;
                $prioB = $b['priority'] ?? 2;
                if ($prioA !== $prioB) {
                    return $prioA <=> $prioB; // Số nhỏ ưu tiên hơn (0=express, 1=fast, 2=standard)
                }
                // Cấp 2: Ưu tiên theo độ khẩn cấp (Đơn hàng nào hẹn giờ sớm hơn thì lên trước để đảm bảo SLA)
                $timeA = $a['most_urgent_time'] ?? '9999-12-31 23:59:59';
                $timeB = $b['most_urgent_time'] ?? '9999-12-31 23:59:59';
                if ($timeA !== $timeB) {
                    return strcmp($timeA, $timeB); // So sánh chuỗi thời gian, cái nào nhỏ hơn (sớm hơn) thì lên trước
                }
                // Cấp 3: Nếu cùng loại và cùng độ khẩn cấp, mới xét đến hiệu quả kinh tế (Điểm hiệu quả cao hơn lên trước)
                return ($b['efficiency_score'] ?? 0) <=> ($a['efficiency_score'] ?? 0);
            });
        } else {
            // CẬP NHẬT: Trích xuất lỗi thực tế từ Python AI để hiển thị lên Radar
            $aiErrorMsg = '';
            if ($output === false) {
                $aiErrorMsg = 'Không thể kết nối đến Máy chủ AI (FastAPI). Lỗi: ' . curl_error($ch) . '. Vui lòng kiểm tra xem cửa sổ Terminal chạy `python main.py` đã bật chưa.';
            } elseif ($result && isset($result['detail'])) {
                $aiErrorMsg = 'Lỗi dữ liệu đầu vào (FastAPI 422/500): ' . json_encode($result['detail']);
            } elseif ($result && isset($result['message'])) {
                $aiErrorMsg = 'Lỗi bên trong thuật toán Python: ' . $result['message'];
            }

            // Fallback logic for single orders if AI fails
            $osrmService = new OsrmService();
            foreach ($orders as $o) {
                $o['formatted_scheduled_at'] = !empty($o['scheduled_at']) 
                    ? date('H:i d/m/Y', strtotime($o['scheduled_at'])) 
                    : 'Càng sớm càng tốt';
                $o['shipping_method_label'] = \App\Models\Order::getShippingMethodLabel($o['shipping_method'] ?? null);
                $o['shipping_method_color'] = \App\Models\Order::getShippingMethodColor($o['shipping_method'] ?? null);
                
                $distance_km = $osrmService->haversineDistance(
                    (float)$driverLocation['lat'], (float)$driverLocation['lng'],
                    (float)$o['sender_lat'], (float)$o['sender_lng']
                );
                $access_duration_s = (int) (($distance_km / $vehicleSpeed) * 3600);

                // CẬP NHẬT: Tính thời gian giao hàng thực tế để không bị 0 phút / 0 điểm hiệu quả
                $trip_dist_km = $osrmService->haversineDistance(
                    (float)$o['sender_lat'], (float)$o['sender_lng'],
                    (float)$o['receiver_lat'], (float)$o['receiver_lng']
                );
                $trip_duration_s = (int) (($trip_dist_km / $vehicleSpeed) * 3600);
                $totalTripDurationMinutes = ($trip_duration_s + $access_duration_s) / 60;
                $efficiencyScore = $totalTripDurationMinutes > 1 ? (float)($o['shipping_fee'] ?? 0) / $totalTripDurationMinutes : 0;

                $batches[] = [
                    'batch_id' => 'ĐƠN LẺ',
                    'order_ids' => [$o['id']],
                    'route_details' => [
                        [
                            'type' => 'pickup', 'order_id' => $o['id'], 'address' => $o['pickup_address'],
                            'shipping_method_label' => $o['shipping_method_label'], 'shipping_method_color' => $o['shipping_method_color']
                        ], 
                        [
                            'type' => 'delivery', 'order_id' => $o['id'], 'address' => $o['delivery_address'],
                            'shipping_method_label' => $o['shipping_method_label'], 'shipping_method_color' => $o['shipping_method_color']
                        ]
                    ],
                    'total_orders' => 1,
                    'total_fee' => (float)($o['shipping_fee'] ?? 0),
                    'total_weight' => (float)($o['weight'] ?? 1.0),
                    'order_details' => [$o],
                    'total_trip_duration_minutes' => round($totalTripDurationMinutes),
                    'access_duration_minutes' => round($access_duration_s / 60),
                    'efficiency_score' => round($efficiencyScore),
                    'most_urgent_time' => $o['scheduled_at'] ?? $o['created_at'] ?? '9999-12-31 23:59:59',
                    'formatted_urgent_time' => !empty($o['scheduled_at']) ? date('H:i d/m/Y', strtotime($o['scheduled_at'])) : 'Càng sớm càng tốt',
                    'priority' => ($o['shipping_method'] ?? 'standard') === 'express' ? 0 : (($o['shipping_method'] ?? 'standard') === 'fast' ? 1 : 2)
                ];
            }
            
            usort($batches, function($a, $b) {
                $prioA = $a['priority'] ?? 2;
                $prioB = $b['priority'] ?? 2;
                if ($prioA !== $prioB) {
                    return $prioA <=> $prioB;
                }
                $timeA = $a['most_urgent_time'] ?? '9999-12-31 23:59:59';
                $timeB = $b['most_urgent_time'] ?? '9999-12-31 23:59:59';
                return strcmp($timeA, $timeB);
            });
        }

        return ['batches' => $batches, 'message' => isset($aiErrorMsg) && $aiErrorMsg ? $aiErrorMsg : ''];
    }

    // Xử lý sự kiện tài xế bấm nhận một cụm đơn hàng (Batch) và thực hiện trừ phí nền tảng.
    public function acceptOrder(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $data = $request->getBody();
        
        $orderIds = $data['order_ids'] ?? [];
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }
        $orderIds = array_values(array_unique(array_filter(array_map('intval', (array)$orderIds))));
        
        $batchCode = $data['batch_code'] ?? 'B' . $driverId . time();
        $routeDetailsJson = $data['route_details'] ?? null;
        $routeDetails = $routeDetailsJson ? json_decode($routeDetailsJson, true) : [];

        if (empty($orderIds)) {
            $_SESSION['flash_error'] = "Chưa có đơn hàng nào được chọn.";
            return $response->redirect('/driver/receive-orders');
        }

        $orderModel = new Order();

        $validationResult = $orderModel->canDriverAcceptMultipleOrders($driverId, $orderIds);
        $validOrderIds = $validationResult['valid_orders'];
        $rejectedOrders = $validationResult['rejected_orders'];

        if (!empty($rejectedOrders)) {
            $errorMsg = "Không thể nhận chuyến ghép này do có đơn hàng không đủ điều kiện: " . (array_values($rejectedOrders)[0] ?? 'Lỗi không xác định');
            $_SESSION['flash_error'] = $errorMsg;
            return $response->redirect('/driver/receive-orders');
        }

        $walletModel = new Wallet();
        $shippingFees = $orderModel->getShippingFees($validOrderIds);

        $totalDeduction = 0;
        foreach ($shippingFees as $row) {
            $fee = (int) $row['shipping_fee'];
            if (($row['payment_method'] ?? 'cash') === 'cash') {
                $deduction = app_calculate_platform_fee($fee);
            } else {
                $deduction = 0;
            }
            $totalDeduction += $deduction;
        }

        if ($totalDeduction > 0) {
            if (!$walletModel->deduct($driverId, $totalDeduction, 'platform_fee', 'Phí nhận chuyến ghép')) {
                $_SESSION['flash_error'] = "Số dư ví không đủ để nhận chuyến này (Cần " . number_format($totalDeduction, 0, ',', '.') . "đ phí nền tảng). Vui lòng nạp thêm tiền.";
                return $response->redirect('/driver/receive-orders');
            }
        }

        $assignedIds = $orderModel->assignMultipleOrdersToDriver($validOrderIds, $driverId, $batchCode, $routeDetails);
        $successCount = count($assignedIds);

        if ($successCount === count($validOrderIds) && $successCount > 0) {
            if ($totalDeduction > 0) {
                $_SESSION['flash_success'] = "Đã nhận thành công cụm $successCount đơn hàng! Hệ thống đã trừ " . number_format($totalDeduction, 0, ',', '.') . "đ phí nhận đơn.";
                
                $userModel = new User();
                $userModel->createNotification($driverId, 'Khấu trừ phí nền tảng', "Hệ thống đã khấu trừ " . number_format($totalDeduction, 0, ',', '.') . "đ phí nền tảng cho tác vụ nhận cụm " . $successCount . " đơn hàng.", 'wallet', '/driver/active-orders');
            } else {
                $_SESSION['flash_success'] = "Đã nhận thành công cụm $successCount đơn hàng!";
            }

            return $response->redirect('/driver/active-orders');
        } else {
            if ($totalDeduction > 0) {
                $walletModel->add($driverId, $totalDeduction, 'refund', 'Refund failed order acceptance fee');
                $userModel = new User();
                $userModel->createNotification($driverId, 'Hoàn phí nền tảng', "Hệ thống đã hoàn lại " . number_format($totalDeduction, 0, ',', '.') . "đ vào ví của bạn do giao dịch nhận chuyến ghép không thành công.", 'wallet', '/driver/receive-orders');
            }
            $_SESSION['flash_error'] = "Nhận chuyến ghép thất bại! Có thể một đơn hàng trong cụm ghép này đã bị tài xế khác nhận trước.";
            return $response->redirect('/driver/receive-orders');
        }
    }

    // Hiển thị lịch sử các chuyến đi đã hoàn thành hoặc đã hủy của tài xế.
    public function history(Request $request, Response $response)
    {
        $query = $request->getBody();
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 9;
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

    // Hiển thị danh sách các đơn hàng tài xế đang thực hiện (chưa hoàn tất).
    public function activeOrders(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $orderModel = new Order();
        
        $activeOrders = $orderModel->getActiveOrdersForDriver($driverId);

        $groupedByBatch = [];
        foreach ($activeOrders as $order) {
            $batchCode = $order['batch_code'] ?? 'single_' . $order['id'];
            if (!isset($groupedByBatch[$batchCode])) {
                $groupedByBatch[$batchCode] = [
                    'orders' => [],
                    'route_details' => [],
                    'accepted_at' => $order['accepted_at']
                ];
            }
            $groupedByBatch[$batchCode]['orders'][] = $order;
            if (!empty($order['batch_route_details'])) {
                $route = json_decode($order['batch_route_details'], true);
                if (is_array($route)) {
                    $groupedByBatch[$batchCode]['route_details'] = $route;
                }
            }
        }

        // Sắp xếp các đơn hàng bên trong mỗi chuyến ghép theo lộ trình AI
        foreach ($groupedByBatch as &$batch) {
            if (!empty($batch['route_details'])) {
                // hàm tính toán các điểm còn lại để lấy thứ tự hành động tiếp theo
                $pointsData = app_build_driver_route_points($batch['orders'], $batch['route_details']);
                $sequenceMap = [];
                foreach ($pointsData as $index => $step) {
                    if (!isset($sequenceMap[$step['tracking_code']])) {
                        $sequenceMap[$step['tracking_code']] = $index;
                    }
                }
                usort($batch['orders'], function($a, $b) use ($sequenceMap) {
                    $seqA = $sequenceMap[$a['tracking_code']] ?? 999;
                    $seqB = $sequenceMap[$b['tracking_code']] ?? 999;
                    return $seqA <=> $seqB;
                });
            }
        }
        unset($batch);

        return $response->render('driver/orders/active', [
            'pageTitle' => 'Đơn hàng đang chạy',
            'groupedOrders' => $groupedByBatch,
            'totalActive' => count($activeOrders)
        ]);
    }

    // Hiển thị giao diện chi tiết của một đơn hàng cụ thể mà tài xế đang thực hiện.
    public function viewOrder(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.";
            return $response->redirect('/driver/history');
        }

        $history = $orderModel->getOrderHistory($orderId);
        
        $ratingInfo = ['avg' => '0.0', 'total' => '0'];
        try {
            $stmt = \App\Core\Database::getInstance()->prepare("SELECT AVG(rating) as avg, COUNT(id) as total FROM driver_reviews WHERE driver_id = ?");
            $stmt->execute([$driverId]);
            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['total'] > 0) {
                    $ratingInfo['avg'] = number_format((float)$row['avg'], 1);
                    $ratingInfo['total'] = $row['total'];
                }
            }
        } catch (\Throwable $e) {}

        return $response->render('driver/orders/view', [
            'pageTitle' => 'Chi tiết chuyến đi #' . $order['tracking_code'],
            'order' => $order,
            'history' => $history,
            'ratingInfo' => $ratingInfo
        ]);
    }

    // Xử lý cập nhật trạng thái đơn hàng (đang đi lấy, đang giao, hoàn thành, hủy) và lưu ảnh minh chứng.
    public function updateStatus(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $data = $request->getBody(); // Sử dụng nhất quán Request object
        $newStatus = $data['status'] ?? '';
        $cancelReason = $this->normalizeFailureReason(trim($data['cancel_reason'] ?? '')); // Bỏ app_sanitize, chỉ cần trim
        $deliveryPin = trim($data['delivery_pin'] ?? '');
        $redirectTo = $data['redirect_to'] ?? '';
        $redirectUrl = $redirectTo === 'active' ? "/driver/active-orders" : "/driver/orders/view/$orderId";

        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
            return $response->redirect('/driver/history');
        }

        $allowedTransitions = [
            'accepted' => ['picking_up', 'cancelled'],
            'picking_up' => ['in_transit', 'cancelled'],
            'in_transit' => ['completed', 'returning'],
            'returning' => ['returned', 'disputed'],
            'shipping' => ['completed', 'returning'] // Phòng hờ nếu DB đang dùng chữ shipping
        ];

        $currentStatus = $order['status'];
        
        // Kiểm tra xem có phải là sự cố nghiêm trọng (Hàng cấm) hay không
        $isBannedGoods = $this->isBannedGoodsReason($cancelReason);
        
        if (isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus])) {
            
            try {
                $proofImagePath = $this->uploadProofImage($orderId);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                return $response->redirect("/driver/orders/view/$orderId");
            }
            
            if (in_array($newStatus, ['completed', 'cancelled', 'returning', 'returned', 'disputed']) && empty($proofImagePath)) {
                $_SESSION['flash_error'] = "Lỗi: Bạn bắt buộc phải chụp và tải lên ảnh minh chứng!";
                return $response->redirect($redirectUrl);
            }

            // BẢO MẬT: Kiểm tra Mã PIN giao hàng khi hoàn thành đơn
            if ($newStatus === 'completed' && !empty($order['delivery_pin'])) {
                if ($deliveryPin !== $order['delivery_pin']) {
                    $_SESSION['flash_error'] = "Mã PIN không hợp lệ! Vui lòng yêu cầu người nhận cung cấp đúng Mã PIN 4 số.";
                    return $response->redirect($redirectUrl);
                }
            }

            $description = "Tài xế đã cập nhật trạng thái đơn hàng thành: " . Order::getStatusLabel($newStatus);
            if ($newStatus === 'completed') {
                $description = "Tài xế đã giao hàng thành công.";
            } elseif ($newStatus === 'cancelled') {
                if (in_array($currentStatus, ['accepted', 'picking_up'])) {
                    if ($isBannedGoods) {
                        $description = "🚨 TÀI XẾ PHÁT HIỆN HÀNG CẤM. Hệ thống tự động hủy đơn và khóa tài khoản Khách hàng.";
                    } else {
                        $description = "Tài xế báo cáo lấy hàng thất bại / Hủy đơn. Lý do: " . $cancelReason;
                    }
                } else {
                    $description = "Tài xế báo cáo giao thất bại / Hủy đơn. Lý do: " . $cancelReason;
                }
            } elseif ($newStatus === 'returning') {
                $description = "Tài xế báo cáo giao thất bại. Đơn hàng đang được chuyển hoàn. Lý do: " . $cancelReason;
            } elseif ($newStatus === 'returned') {
                $description = "Tài xế xác nhận đã hoàn trả hàng về người gửi.";
            } elseif ($newStatus === 'disputed') {
                $description = "Người gửi từ chối nhận hoàn hàng / Sự cố hoàn hàng. Lý do: " . $cancelReason;
            }
            
            if (!empty($proofImagePath)) {
                $description .= "<br><br><div class='proof-image-wrapper'><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image'></a></div>";
            }

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                if (!empty($proofImagePath)) {
                    $db->prepare("UPDATE order_deliveries SET proof_image = ? WHERE order_id = ?")->execute([$proofImagePath, $orderId]);
                }
                if ($cancelReason !== '') {
                    $db->prepare("UPDATE order_deliveries SET cancel_reason = ? WHERE order_id = ?")->execute([$cancelReason, $orderId]);
                }

                if ($orderModel->updateStatus($orderId, $newStatus, $description)) {
                    $successMessage = "Cập nhật trạng thái thành công!";

                    if ($newStatus === 'completed' && in_array($order['payment_method'] ?? 'cash', self::PREPAID_PAYMENT_METHODS, true)) {
                        $driverEarnings = app_calculate_driver_earnings((float)($order['shipping_fee'] ?? 0));
                        $walletModel = new Wallet();
                        
                        if ($driverEarnings > 0) {
                            $walletModel->add(
                                $driverId,
                                $driverEarnings,
                                'adjustment',
                                "Cộng tiền cước đơn #{$order['tracking_code']} (khách thanh toán chuyển khoản)",
                                $orderId
                            );
                            $successMessage = "Giao hàng thành công. Bạn được cộng " . number_format($driverEarnings, 0, ',', '.') . "đ (tiền cước - phí) vào ví.";
                            
                            $userModel = new User();
                            $userModel->createNotification(
                                $driverId,
                                'Cộng tiền cước vận chuyển',
                                "Hệ thống đã cộng " . number_format($driverEarnings, 0, ',', '.') . "đ vào ví của bạn (Cước vận chuyển đơn hàng #{$order['tracking_code']}).",
                                'wallet',
                                "/driver/orders/view/$orderId"
                            );
                        }
                    }

                    // THƯỞNG GIAO SIÊU TỐC: Giảm 1 lần vi phạm cho tài xế nếu hoàn thành xuất sắc
                    if ($newStatus === 'completed' && ($order['shipping_method'] ?? '') === 'express') {
                        $stmtReduceViolation = $db->prepare("UPDATE users SET violation_count = violation_count - 1 WHERE id = ? AND violation_count > 0");
                        $stmtReduceViolation->execute([$driverId]);
                        if ($stmtReduceViolation->rowCount() > 0) {
                            $successMessage .= " Thưởng giao Siêu tốc: Bạn đã được giảm 1 lần vi phạm!";
                            $userModel = new User();
                            $userModel->createNotification(
                                $driverId,
                                'Thưởng giao Siêu tốc',
                                "Chúc mừng! Vì đã hoàn thành xuất sắc đơn hàng Siêu tốc #{$order['tracking_code']}, hệ thống đã xóa 1 lỗi vi phạm cho bạn.",
                                'system',
                                "/driver/orders/view/$orderId"
                            );
                        }
                    }

                    if ($newStatus === 'cancelled') {
                        $successMessage = "Đã hủy đơn.";
                        if (($order['payment_method'] ?? 'cash') === 'cash') {
                            $feePerOrder = app_calculate_platform_fee((float)($order['shipping_fee'] ?? 0));
                            $walletModel = new Wallet();
                            $walletModel->add($driverId, $feePerOrder);
                            $successMessage .= " Phí nhận đơn " . number_format($feePerOrder, 0, ',', '.') . "đ đã được hoàn lại vào ví của bạn.";

                            $userModel = new User();
                            $userModel->createNotification($driverId, 'Hoàn phí nhận đơn', "Bạn đã được cộng " . number_format($feePerOrder, 0, ',', '.') . "đ (hoàn phí nền tảng) cho đơn hàng #{$order['tracking_code']} do đơn bị hủy.", 'wallet', "/driver/orders/view/$orderId");
                        }
                        
                        // KÍCH HOẠT AUTO-BAN NẾU LÀ HÀNG CẤM
                        if ($isBannedGoods) {
                            $userModel = new User();
                            $userModel->updateBlockStatus($order['customer_id'], 1);
                            $db->prepare("UPDATE users SET blocked_reason = 'Vi phạm An ninh: Gửi hàng cấm / Vi phạm pháp luật (Tài xế báo cáo)' WHERE id = ?")->execute([$order['customer_id']]);
                            
                            $adminIds = $userModel->getAdminIds();
                            foreach ($adminIds as $adminId) {
                                $userModel->createNotification(
                                    $adminId,
                                    "🚨 Cảnh báo An ninh (Hàng cấm)",
                                    "Tài xế phát hiện hàng cấm tại đơn #{$order['tracking_code']}. Khách hàng đã bị khóa vĩnh viễn. Vui lòng kiểm tra hình ảnh minh chứng!",
                                    'system',
                                    "/admin/orders/view/{$orderId}"
                                );
                            }
                        } elseif (in_array($currentStatus, ['accepted', 'picking_up'], true)) {
                            $userModel = new User();
                            $userModel->recordNoShow($order['customer_id']);
                        }
                    }

                    $userModel = new User();
                    
                    if ($newStatus === 'disputed') {
                        $userModel->createNotification(
                            $order['customer_id'],
                            "Sự cố hoàn trả hàng",
                            "Tài xế đã báo cáo sự cố khi hoàn trả đơn hàng #{$order['tracking_code']} (Lý do: " . ($cancelReason ?: 'Khách từ chối nhận') . "). Hệ thống đã ghi nhận khiếu nại để Ban quản trị xem xét.",
                            'order',
                            "/user/orders/track/{$order['tracking_code']}"
                        );
                    } else {
                        $notiTitle = "Cập nhật đơn hàng #{$order['tracking_code']}";
                        $notiMsg = "Tài xế đã cập nhật trạng thái thành: " . Order::getStatusLabel($newStatus);

                        if ($newStatus === 'picking_up') {
                            $notiTitle = "Tài xế đang đến lấy hàng";
                            $notiMsg = "Tài xế đang trên đường đến điểm lấy đơn hàng #{$order['tracking_code']}. Vui lòng chuẩn bị sẵn gói hàng để việc giao nhận diễn ra thuận lợi.";
                        } elseif ($newStatus === 'in_transit' || $newStatus === 'shipping') {
                            $notiTitle = "Đơn hàng đang được giao";
                            $notiMsg = "Tài xế đã lấy hàng thành công và đang trên đường giao đơn #{$order['tracking_code']} đến người nhận.";
                            if (!empty($order['delivery_pin'])) {
                                $notiMsg .= " MÃ PIN NHẬN HÀNG LÀ: " . $order['delivery_pin'] . ". Vui lòng báo người nhận đọc mã này cho tài xế.";
                            }
                        } elseif ($newStatus === 'completed') {
                            $notiTitle = "Giao hàng thành công";
                            $notiMsg = "Đơn hàng #{$order['tracking_code']} đã được giao đến tay người nhận an toàn. Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của NUN Express.";
                        } elseif ($newStatus === 'returning') {
                            $notiTitle = "Đơn hàng chuyển hoàn";
                            $notiMsg = "Rất tiếc, đơn hàng #{$order['tracking_code']} không thể giao thành công. Tài xế đang tiến hành chuyển hoàn gói hàng lại cho bạn.";
                        } elseif ($newStatus === 'returned') {
                            $notiTitle = "Hoàn trả hàng thành công";
                            $notiMsg = "Đơn hàng #{$order['tracking_code']} đã được hoàn trả lại cho bạn. Vui lòng kiểm tra lại tình trạng gói hàng.";
                        } elseif ($newStatus === 'cancelled') {
                            if ($isBannedGoods) {
                                $notiTitle = "Tài khoản bị khóa";
                                $notiMsg = "Tài khoản của bạn đã bị khóa do vi phạm chính sách gửi Hàng cấm tại đơn #{$order['tracking_code']}.";
                            } elseif (in_array($currentStatus, ['accepted', 'picking_up'], true)) {
                                $notiTitle = "Cảnh báo vi phạm giao nhận";
                                $notiMsg = "Đơn hàng #{$order['tracking_code']} đã bị hủy do tài xế báo cáo lấy hàng thất bại. Hệ thống đã ghi nhận 1 lần vi phạm trên tài khoản của bạn.";
                            }
                        }

                        $userModel->createNotification(
                            $order['customer_id'],
                            $notiTitle,
                            $notiMsg,
                            'order',
                            "/user/orders/track/{$order['tracking_code']}"
                        );
                    }
                    
                    if ($newStatus === 'returning') {
                        $adminIds = $userModel->getAdminIds();
                        foreach ($adminIds as $adminId) {
                            $userModel->createNotification(
                                $adminId,
                                "Cảnh báo chuyển hoàn",
                                "Đơn hàng #{$order['tracking_code']} đang được tài xế chuyển hoàn. Lý do: " . ($cancelReason ?: 'Giao hàng thất bại'),
                                'system',
                                "/admin/orders/view/{$orderId}"
                            );
                        }
                    }
                    
                    if ($newStatus === 'disputed') {
                        $disputeModel = new \App\Models\Dispute();
                        $disputeModel->create($orderId, $driverId, "Sự cố khi hoàn hàng: " . $cancelReason);
                        
                        $adminIds = $userModel->getAdminIds();
                        foreach ($adminIds as $adminId) {
                            $userModel->createNotification(
                                $adminId,
                                "Tranh chấp hoàn hàng",
                                "Đơn hàng #{$order['tracking_code']} gặp sự cố khi trả hàng về người gửi. Lý do: " . $cancelReason,
                                'system',
                                "/admin/orders/view/{$orderId}"
                            );
                        }
                    }

                    if ($newStatus === 'cancelled' && ($order['payment_status'] ?? '') === 'paid') {
                        if ($isBannedGoods) {
                            $successMessage .= " Tịch thu tiền cước (Không hoàn trả) do vi phạm gửi hàng cấm.";
                            $orderModel->addStatusHistory($orderId, 'cancelled', 'Hệ thống từ chối hoàn tiền do Khách hàng vi phạm chính sách gửi hàng cấm.');
                        } elseif ($orderModel->refundPaidOrder($orderId)) {
                            $successMessage .= " Hệ thống đã ghi nhận hoàn tiền cho khách.";
                            $orderModel->addStatusHistory($orderId, 'cancelled', 'Hệ thống hoàn tiền cho khách do đơn đã thanh toán nhưng bị hủy.');
                        }
                    }

                    $db->commit();
                    $_SESSION['flash_success'] = $successMessage;
                } else {
                    $db->rollBack();
                    $_SESSION['flash_error'] = "Cập nhật trạng thái thất bại. Hệ thống đang bận, vui lòng thử lại.";
                }
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Update Status Error: " . $e->getMessage());
                $_SESSION['flash_error'] = "Có lỗi xảy ra, không thể cập nhật trạng thái.";
            }
        } else {
            $_SESSION['flash_error'] = "Chuyển đổi trạng thái không hợp lệ.";
        }

        return $response->redirect($redirectUrl);
    }

    // Xử lý báo cáo sự cố giao/nhận hàng (khách không nhận, không liên lạc được) kèm tải ảnh minh chứng.
    public function reportNoShow(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $orderId = (int) $request->getRouteParam('id');
        $data = $request->getBody(); // Sử dụng nhất quán Request object
        $reason = $this->normalizeFailureReason(trim($data['reason'] ?? 'Sự cố giao/nhận hàng')); // Bỏ app_sanitize

        $orderModel = new Order();
        $order = $orderModel->findByIdForDriver($orderId, $driverId);

        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
            return $response->redirect('/driver/history');
        }

        try {
            $proofImagePath = $this->uploadProofImage($orderId, 'noshow');
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            return $response->redirect("/driver/orders/view/$orderId");
        }

        if (empty($proofImagePath)) {
            $_SESSION['flash_error'] = "Lỗi: Bạn bắt buộc phải chụp và tải lên ảnh minh chứng tại hiện trường để báo cáo thất bại!";
            return $response->redirect("/driver/orders/view/$orderId");
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $db->prepare("UPDATE order_deliveries SET proof_image = ?, cancel_reason = ? WHERE order_id = ?")
                ->execute([$proofImagePath, $reason, $orderId]);

            $userModel = new User();

            // =========================================================
            // NGHIỆP VỤ 1: LẤY HÀNG THẤT BẠI (LỖI DO NGƯỜI GỬI TẠO APP)
            // =========================================================
            if (in_array($order['status'], ['accepted', 'picking_up'])) {
                $isBannedGoods = $this->isBannedGoodsReason($reason);

                if ($isBannedGoods) {
                    $description = "🚨 TÀI XẾ PHÁT HIỆN HÀNG CẤM. Hệ thống tự động hủy đơn và khóa tài khoản Khách hàng.";
                } else {
                    $description = "Tài xế báo cáo lấy hàng thất bại (Lỗi từ Người gửi). Lý do: " . $reason;
                }
                $description .= "<br><br><div class='proof-image-wrapper'><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image'></a></div>";

                if ($orderModel->updateStatus($orderId, 'cancelled', $description)) {
                    if ($isBannedGoods) {
                        $userModel->updateBlockStatus($order['customer_id'], 1);
                        $db->prepare("UPDATE users SET blocked_reason = 'Vi phạm An ninh: Gửi hàng cấm / Vi phạm pháp luật (Tài xế báo cáo)' WHERE id = ?")->execute([$order['customer_id']]);
                        
                        $adminIds = $userModel->getAdminIds();
                        foreach ($adminIds as $adminId) {
                            $userModel->createNotification(
                                $adminId,
                                "🚨 Cảnh báo An ninh (Hàng cấm)",
                                "Tài xế phát hiện hàng cấm tại đơn #{$order['tracking_code']}. Khách hàng đã bị khóa vĩnh viễn. Vui lòng kiểm tra hình ảnh minh chứng!",
                                'system',
                                "/admin/orders/view/{$orderId}"
                            );
                        }
                    } else {
                        // Phạt người gửi (Cộng 1 lần vi phạm giao nhận)
                        $userModel->recordNoShow($order['customer_id']);
                    }
                    
                    // Hoàn lại phí nền tảng cho tài xế
                    $feePerOrder = app_calculate_platform_fee((float)($order['shipping_fee'] ?? 0));
                    $walletModel = new Wallet();
                    if (($order['payment_method'] ?? 'cash') === 'cash') {
                        $walletModel->add($driverId, $feePerOrder);

                        $userModel->createNotification(
                            $driverId,
                            'Hoàn phí nền tảng',
                            "Hệ thống đã hoàn lại " . number_format($feePerOrder, 0, ',', '.') . "đ phí nền tảng cho đơn hàng #{$order['tracking_code']} do sự cố giao nhận từ phía người gửi.",
                            'wallet',
                            "/driver/orders/view/$orderId"
                        );
                        $_SESSION['flash_success'] = "Đã báo cáo lấy hàng thất bại thành công! Phí " . number_format($feePerOrder, 0, ',', '.') . "đ đã được hoàn lại ví.";
                    } else {
                        $_SESSION['flash_success'] = "Đã báo cáo lấy hàng thất bại thành công. Đơn hàng đã bị hủy.";
                    }

                    // Xử lý hoàn tiền nếu đã thanh toán
                    if (($order['payment_status'] ?? '') === 'paid') {
                        if ($isBannedGoods) {
                            $orderModel->addStatusHistory($orderId, 'cancelled', 'Hệ thống từ chối hoàn tiền do Khách hàng vi phạm chính sách gửi hàng cấm.');
                        } elseif ($orderModel->refundPaidOrder($orderId)) {
                            $orderModel->addStatusHistory($orderId, 'cancelled', 'Hệ thống hoàn tiền cho khách do đơn đã thanh toán nhưng bị hủy.');
                        }
                    }

                    if ($isBannedGoods) {
                        $userModel->createNotification(
                            $order['customer_id'],
                            "Tài khoản bị khóa",
                            "Tài khoản của bạn đã bị khóa do vi phạm chính sách gửi Hàng cấm tại đơn #{$order['tracking_code']}.",
                            'system',
                            "/user/orders/track/{$order['tracking_code']}"
                        );
                    } else {
                        $userModel->createNotification(
                            $order['customer_id'],
                            "Cảnh báo vi phạm giao nhận",
                            "Đơn hàng #{$order['tracking_code']} đã bị hủy do tài xế báo cáo lấy hàng thất bại. Hệ thống đã ghi nhận 1 lần vi phạm trên tài khoản của bạn.",
                            'system',
                            "/user/orders/track/{$order['tracking_code']}"
                        );
                    }
                } else {
                    $db->rollBack();
                    $_SESSION['flash_error'] = "Có lỗi xảy ra, không thể cập nhật trạng thái đơn hàng.";
                    return $response->redirect("/driver/orders/view/$orderId");
                }
            }
            // =========================================================
            // NGHIỆP VỤ 2: GIAO HÀNG THẤT BẠI (LỖI DO NGƯỜI NHẬN)
            // =========================================================
            elseif (in_array($order['status'], ['in_transit', 'shipping'])) {
                $description = "Tài xế báo cáo GIAO HÀNG THẤT BẠI (Lỗi do Người nhận). Lý do: " . $reason;
                $description .= "<br><br><div class='proof-image-wrapper'><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image'></a></div>";
                
                // KHÔNG GHI NHẬN LỖI (NO-SHOW) CHO NGƯỜI GỬI
                // Chuyển trạng thái sang Chuyển hoàn (Returning)
                if ($orderModel->updateStatus($orderId, 'returning', $description)) {
                    $userModel->createNotification(
                        $order['customer_id'],
                        "Giao hàng không thành công",
                        "Giao hàng không thành công. Tài xế đang tiến hành chuyển hoàn gói hàng #{$order['tracking_code']} về lại địa chỉ của bạn.",
                        'order',
                        "/user/orders/track/{$order['tracking_code']}"
                    );

                    $adminIds = $userModel->getAdminIds();
                    foreach ($adminIds as $adminId) {
                        $userModel->createNotification(
                            $adminId,
                            "Cảnh báo chuyển hoàn",
                            "Đơn hàng #{$order['tracking_code']} đang được tài xế chuyển hoàn. Lý do: " . $reason,
                            'system',
                            "/admin/orders/view/{$orderId}"
                        );
                    }

                    $_SESSION['flash_success'] = "Đã báo cáo Giao hàng thất bại. Vui lòng mang hàng hoàn trả về cho Người gửi.";
                } else {
                    $db->rollBack();
                    $_SESSION['flash_error'] = "Có lỗi xảy ra, không thể cập nhật trạng thái đơn hàng.";
                    return $response->redirect("/driver/orders/view/$orderId");
                }
            } else {
                $_SESSION['flash_error'] = "Trạng thái đơn hàng không hợp lệ để báo cáo sự cố.";
                $db->rollBack();
                return $response->redirect("/driver/orders/view/$orderId");
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi gửi báo cáo: " . $e->getMessage();
        }

        return $response->redirect("/driver/orders/view/$orderId");
    }

    // Cập nhật tọa độ GPS mới nhất của tài xế và phát sóng tự động (WebSockets) tới màn hình theo dõi.
    public function updateLocation(Request $request, Response $response)
    {
        $data = $request->getJsonBody();
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;
        $accuracy = isset($data['accuracy']) && is_numeric($data['accuracy']) ? (float) $data['accuracy'] : null;
        $coordinates = app_normalize_coordinates($lat, $lng);

        if ($coordinates !== null) {
            if ($accuracy !== null && $accuracy > 10000) {
                return $response->json([
                    'success' => false,
                    'message' => 'Toa do GPS chua du chinh xac, vui long bat dinh vi chinh xac.'
                ], 422);
            }

            $driverId = $this->userId();
            $userModel = new User();
            $lat = $coordinates['lat'];
            $lng = $coordinates['lng'];
            
            // 1. Lưu tọa độ vào Database để dự phòng
            $success = $userModel->updateLocation($driverId, $lat, $lng);
            
            // 2. PHÁT SÓNG TỌA ĐỘ QUA WEBSOCKETS (PUSHER) CHO KHÁCH HÀNG VÀ ADMIN
            // Giúp bản đồ theo dõi xe máy di chuyển mượt mà ngay lập tức (Real-time Tracking)
            if ($success && class_exists('\App\Services\PusherService')) {
                try {
                    $orderModel = new Order();
                    $activeTrackingCodes = $orderModel->getActiveTrackingCodesForDriver($driverId);
                    
                    if (!empty($activeTrackingCodes)) {
                        $pusher = new \App\Services\PusherService();
                        
                        //  Áp dụng hàm map nội tại của PHP để duyệt dữ liệu nhanh hơn vòng lặp foreach
                        $channels = array_map(fn($code) => 'tracking-' . $code, $activeTrackingCodes);
                        
                        //  Truyền một MẢNG các kênh để gọi API 1 lần duy nhất (Batching I/O)
                        $pusher->trigger($channels, 'location_update', [
                            'lat' => $lat,
                            'lng' => $lng
                        ]);
                        
                        $pusher->trigger('admin-global-tracking', 'driver_location_update', [
                            'driver_id' => $driverId,
                            'lat' => $lat,
                            'lng' => $lng
                        ]);
                    }
                } catch (\Throwable $e) {
                    error_log('Pusher Update Location Error: ' . $e->getMessage());
                }
            }

            return $response->json(['success' => true]);
        }

        return $response->json(['success' => false, 'message' => 'Tọa độ không hợp lệ'], 400);
    }
}
