<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Dispute;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Services\ShippingFeeService;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class OrderController extends BaseController
{
    // Lấy thông tin chi tiết đơn hàng của khách hàng, báo lỗi nếu không tìm thấy.
    private function getOrderOrFail(string $trackingCode): ?array
    {
        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $this->userId());
        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
        }
        return $order ?: null;
    }

    // Hiển thị giao diện tạo đơn hàng mới cho khách hàng.
    public function create(Request $request, Response $response)
    {
        return $this->renderCreateForm($response);
    }

    // Xử lý kiểm tra dữ liệu đầu vào và lưu đơn hàng mới vào cơ sở dữ liệu.
    public function store(Request $request, Response $response)
    {
        $data = app_sanitize($request->getBody());

        try {
            (new Validator($data))->validate([
                'sender_name' => 'max:120',
                'sender_phone' => 'phone',
                'receiver_name' => 'required|max:120',
                'receiver_phone' => 'required|phone',
                'pickup_address' => 'required|max:500',
                'pickup_address_detail' => 'max:255',
                'delivery_address' => 'required|max:500',
                'delivery_address_detail' => 'max:255',
                'sender_lat' => 'required|numeric',
                'sender_lng' => 'required|numeric',
                'receiver_lat' => 'required|numeric',
                'receiver_lng' => 'required|numeric',
                'weight' => 'required|float|gt:0',
                'shipping_method' => 'required|in:standard,fast,express',
                'payment_method' => 'required|in:cash,transfer',
                'note' => 'max:1000',
                'scheduled_at' => 'required|after_now|before_one_week',
            ])->throw();

            // Yêu cầu 1: Không cho phép địa chỉ lấy và giao trùng nhau
            if (
                !empty($data['sender_lat']) && !empty($data['receiver_lat']) &&
                (float)$data['sender_lat'] == (float)$data['receiver_lat'] &&
                (float)$data['sender_lng'] == (float)$data['receiver_lng']
            ) {
                throw new ValidationException(['Địa chỉ lấy hàng và địa chỉ giao hàng không được trùng nhau.'], $data);
            }

            if (in_array($data['shipping_method'] ?? '', ['fast', 'express'])) {
                if (!empty($data['scheduled_at'])) {
                    $scheduledTime = strtotime($data['scheduled_at']);
                    if ($scheduledTime > time() + 15 * 60) {
                        $data['scheduled_at'] = date('Y-m-d\TH:i', time() + 10 * 60);
                        $_SESSION['flash_error'] = "Đơn hàng Giao nhanh/Siêu tốc yêu cầu thời gian hẹn lấy hàng không được quá 10 phút kể từ hiện tại. Hệ thống đã đặt lại giờ cho bạn.";
                        throw new ValidationException([], $data);
                    }
                }
            }

            $settingModel = new Setting();
            $maxOrderWeight = (float) $settingModel->get('max_order_weight', $settingModel->get('default_max_total_weight', 100));
            
            if (isset($data['weight']) && (float) $data['weight'] > $maxOrderWeight) {
                $_SESSION['flash_error'] = "Cân nặng đơn hàng không được vượt quá {$maxOrderWeight}kg.";
                throw new ValidationException([], $data);
            }

            return $this->createOrder($response, $data);
        } catch (ValidationException $e) {
            return $this->renderCreateForm($response, ['errors' => $e->errors, 'old' => $e->old]);
        }
    }

    // Thực hiện tính cước, tạo mã vận đơn và tiến hành lưu dữ liệu đơn hàng vào hệ thống.
    private function createOrder(Response $response, array $data)
    {
        $originalData = $data;

        try {
            $data['customer_id'] = $this->userId();
            $data['sender_name'] = $this->resolveSenderField($data['sender_name'] ?? '', 'name');
            $data['sender_phone'] = $this->resolveSenderField($data['sender_phone'] ?? '', 'phone');

            // Định dạng địa chỉ từ bản đồ trước khi ghép với địa chỉ chi tiết
            $formattedPickup = app_format_address($data['pickup_address'] ?? '');
            $formattedDelivery = app_format_address($data['delivery_address'] ?? '');

            $data['pickup_address'] = $this->mergeDetailedAddress(
                $formattedPickup,
                $data['pickup_address_detail'] ?? ''
            );
            $data['delivery_address'] = $this->mergeDetailedAddress(
                $formattedDelivery,
                $data['delivery_address_detail'] ?? ''
            );

            if (mb_strlen($data['pickup_address']) > 500 || mb_strlen($data['delivery_address']) > 500) {
                throw new \Exception('Địa chỉ đầy đủ không được vượt quá 500 ký tự.');
            }

            $quote = (new ShippingFeeService())->quote($data);
            $data['shipping_fee'] = (float) ($quote['shipping_fee'] ?? 25000);

            $orderModel = new Order();
            $trackingCode = $orderModel->create($data);

            if (!$trackingCode) {
                throw new \Exception("Lỗi hệ thống: Không thể lưu đơn hàng vào Cơ sở dữ liệu.");
            }

            // Lưu thông báo thành công
            $_SESSION['flash_success'] = "Tạo đơn hàng thành công! Mã đơn: #" . $trackingCode;
            
            if (($data['payment_method'] ?? 'cash') === 'transfer') {
                return $response->redirect('/user/orders/payment/' . $trackingCode);
            }
            return $response->redirect('/user/orders');

        } catch (\Exception $e) {
            return $this->renderCreateForm($response, ['errors' => ['general' => $e->getMessage()], 'old' => $originalData]);
        }
    }

    // Render giao diện form tạo đơn hàng kèm theo các dữ liệu lỗi hoặc nhập lại nếu có.
    private function renderCreateForm(Response $response, array $params = [])
    {
        $defaults = [
            'pageTitle' => 'Tạo đơn hàng mới',
        ];

        return $response->render('user/orders/create', array_merge($defaults, $params));
    }

    // Tự động lấy tên/SĐT của khách hàng đang đăng nhập nếu họ để trống form người gửi.
    private function resolveSenderField(string $value, string $sessionKey): string
    {
        $value = trim($value);
        if ($value !== '') {
            return $value;
        }

        return (string) $this->currentUser($sessionKey, '');
    }

    // Gộp địa chỉ chi tiết (số nhà, hẻm) với địa chỉ trên bản đồ thành một chuỗi hoàn chỉnh.
    private function mergeDetailedAddress(string $baseAddress, string $detailAddress): string
    {
        $baseAddress = trim(trim($baseAddress), ", ");
        $detailAddress = trim(trim($detailAddress), ", ");

        if ($detailAddress === '') {
            return $baseAddress;
        }

        if ($baseAddress === '') {
            return $detailAddress;
        }

        // Để tránh trùng lặp như "Số 1, Số 1 Hùng Vương...", kiểm tra nếu địa chỉ map đã bắt đầu bằng địa chỉ chi tiết (không phân biệt hoa thường).
        if (stripos($baseAddress, $detailAddress) === 0) {
            return $baseAddress;
        }

        return $detailAddress . ', ' . $baseAddress;
    }

    // Chuẩn hóa và sửa lỗi đảo ngược tọa độ Vĩ độ - Kinh độ (nếu có).
    private function normalizeCoordinates($lat, $lng): ?array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $normalizedLat = (float) $lat;
        $normalizedLng = (float) $lng;

        if (!$this->isValidCoordinates($normalizedLat, $normalizedLng)
            && $this->isValidCoordinates($normalizedLng, $normalizedLat)) {
            [$normalizedLat, $normalizedLng] = [$normalizedLng, $normalizedLat];
        }

        if (!$this->isValidCoordinates($normalizedLat, $normalizedLng)) {
            return null;
        }

        return ['lat' => $normalizedLat, 'lng' => $normalizedLng];
    }

    // Kiểm tra tính hợp lệ của tọa độ trên bản đồ thế giới.
    private function isValidCoordinates(float $lat, float $lng): bool
    {
        return is_finite($lat)
            && is_finite($lng)
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180
            && !($lat === 0.0 && $lng === 0.0);
    }

    // Hiển thị danh sách các đơn hàng của khách hàng (có hỗ trợ phân trang và lọc trạng thái).
    public function index(Request $request, Response $response)
    {
        $query = $request->getBody();
        $userId = $this->userId();
        $statusFilter = trim($query['status'] ?? '');
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $orderModel = new Order();
        $totalOrders = $orderModel->countAllByUserId($userId, $statusFilter);
        $orders = $orderModel->findAllByUserId($userId, $statusFilter, $perPage, $offset);
        $totalPages = (int) ceil($totalOrders / $perPage);

        return $response->render('user/orders/index', [
            'pageTitle' => 'Danh sách đơn hàng',
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    // Hiển thị trang theo dõi hành trình chi tiết của một đơn hàng thông qua mã vận đơn.
    public function track(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $userId);

        if (!$order) {
            return $this->notFound($response);
        }

        $history = $orderModel->getOrderHistory($order['id']);
        
        $reviewModel = new Review();
        $existingReview = $reviewModel->findByOrderAndCustomer($order['id'], $userId);

        $ratingInfo = ['avg' => 0, 'total' => 0];
        if (!empty($order['driver_id'])) {
            $userModel = new User();
            $ratingInfo = $userModel->getDriverRating((int) $order['driver_id']);
        }

        return $response->render('user/orders/track', [
            'pageTitle' => 'Theo dõi đơn hàng #' . $order['tracking_code'],
            'order' => $order,
            'history' => $history,
            'existingReview' => $existingReview,
            'ratingInfo' => $ratingInfo
        ]);
    }

    // Trả về tọa độ GPS hiện tại của tài xế thông qua API để hiển thị real-time trên bản đồ.
    public function apiDriverLocation(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $orderModel = new Order();
        
        $location = $orderModel->getDriverLocationByTrackingCode($trackingCode);
        
        $coordinates = $location
            ? $this->normalizeCoordinates($location['current_lat'] ?? null, $location['current_lng'] ?? null)
            : null;

        if ($coordinates) {
            return $response->json([
                'success' => true,
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng']
            ]);
        }

        return $response->json(['success' => false]);
    }

    // Tính toán cước phí vận chuyển dự kiến dựa trên AI OSRM và bảng giá hệ thống (API).
    public function apiCalculateFee(Request $request, Response $response)
    {
        $data = $request->getJsonBody() ?: $request->getBody();
        
        $settingModel = new Setting();
        $maxOrderWeight = (float) $settingModel->get('max_order_weight', $settingModel->get('default_max_total_weight', 100));
        if (isset($data['weight']) && (float) $data['weight'] > $maxOrderWeight) {
            return $response->json([
                'success' => false, 
                'message' => "Cân nặng đơn hàng không được vượt quá {$maxOrderWeight}kg.",
                'error' => "Cân nặng đơn hàng không được vượt quá {$maxOrderWeight}kg."
            ]);
        }

        $quote = (new ShippingFeeService())->quote($data);

        if ($quote['success']) {
            return $response->json([
                'success' => true, 
                'fee' => $quote['shipping_fee'],
                'base_fee' => $quote['base_fee'] ?? $quote['shipping_fee'],
                'surge_fee' => $quote['surge_fee'] ?? 0,
                'distance' => $quote['distance_km'],
                'duration_minutes' => $quote['duration_minutes'] ?? null,
                'surge_label' => $quote['surge_label'],
                'surge_multiplier' => $quote['surge_multiplier']
            ]);
        }

        return $response->json(['success' => false, 'message' => $quote['message']]);
    }

    // Hiển thị giao diện cho khách hàng đánh giá tài xế sau khi đơn hàng đã hoàn thành.
    public function review(Request $request, Response $response)
    {
        $orderId = (int) $request->getRouteParam('id');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByIdAndUserId($orderId, $userId);

        if (!$order) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại.';
            return $response->redirect('/user/orders');
        }

        $error = '';
        if ($order['status'] !== 'completed') {
            $error = 'Đơn hàng chưa hoàn thành nên chưa thể đánh giá.';
        } elseif (empty($order['driver_id'])) {
            $error = 'Đơn hàng này chưa có tài xế để đánh giá.';
        }

        $reviewModel = new Review();
        $existingReview = $reviewModel->findByOrderAndCustomer($orderId, $userId);

        $userModel = new User();
        $ratingInfo = ['avg' => 5, 'total' => 0];
        if (!empty($order['driver_id'])) {
            $ratingInfo = $userModel->getDriverRating((int) $order['driver_id']);
        }

        return $response->render('user/orders/review', [
            'pageTitle' => 'Đánh giá tài xế',
            'order' => $order,
            'error' => $error,
            'existingReview' => $existingReview,
            'ratingInfo' => $ratingInfo,
            'driverAvatar' => \app_avatar_url($order['driver_avatar'] ?? null, $order['driver_name'] ?? 'Driver'),
            'flashMessage' => $_SESSION['flash_success'] ?? ''
        ]);
    }

    // Lưu đánh giá (số sao, thẻ nhận xét, bình luận) của khách hàng dành cho tài xế vào CSDL.
    public function storeReview(Request $request, Response $response)
    {
        $orderId = (int) $request->getRouteParam('id');
        $userId = $this->userId();
        $data = $request->getBody();

        $orderModel = new Order();
        $order = $orderModel->findByIdAndUserId($orderId, $userId);
        if (!$order) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại hoặc bạn không có quyền đánh giá.';
            return $response->redirect('/user/orders');
        }
        if ($order['status'] !== 'completed') {
            $_SESSION['flash_error'] = 'Chỉ có thể đánh giá khi đơn hàng đã giao hoàn thành.';
            return $response->redirect("/user/orders/track/{$order['tracking_code']}");
        }

        $rating = (int) ($data['rating'] ?? 0);
        $driverId = (int) ($data['driver_id'] ?? 0);
        $comment = app_sanitize($data['comment'] ?? '');
        
        $tags = $data['tags'] ?? [];
        if (!empty($tags) && is_array($tags)) {
            $tagString = implode(', ', array_map('app_sanitize', $tags));
            $comment = $comment !== '' ? $tagString . '. ' . $comment : $tagString;
        }

        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = 'Vui lòng chọn số sao.';
            return $response->redirect("/user/orders/review/{$orderId}");
        }

        $reviewModel = new Review();
        
        if ($reviewModel->findByOrderAndCustomer($orderId, $userId)) {
            $_SESSION['flash_error'] = 'Bạn đã đánh giá đơn hàng này rồi.';
            return $response->redirect("/user/orders/review/{$orderId}");
        }

        if ($reviewModel->create($orderId, $userId, $driverId, $rating, $comment)) {
            $_SESSION['flash_success'] = 'Đánh giá tài xế thành công! Cảm ơn bạn.';
            return $response->redirect('/user/orders');
        }

        $_SESSION['flash_error'] = 'Không thể lưu đánh giá, vui lòng thử lại.';
        return $response->redirect("/user/orders/review/{$orderId}");
    }

    // Hiển thị giao diện thanh toán trực tuyến qua mã QR cho đơn hàng trả trước.
    public function payment(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $userId);

        if (!$order || $order['status'] !== 'awaiting_payment') {
            $_SESSION['flash_error'] = "Đơn hàng không tồn tại hoặc đã được thanh toán.";
            return $response->redirect('/user/orders');
        }

        return $response->render('user/orders/payment', [
            'pageTitle' => 'Thanh toán đơn hàng #' . $order['tracking_code'],
            'order' => $order
        ]);
    }

    // Xử lý ghi nhận đã thanh toán và chuyển trạng thái đơn sang tìm tài xế (mô phỏng).
    public function processPayment(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $userId);

        if ($order && $order['status'] === 'awaiting_payment') {
            $orderModel->updatePaymentStatus($order['id'], 'paid');
            $orderModel->updateStatus($order['id'], 'searching_driver', 'Khách hàng đã thanh toán thành công. Bắt đầu tìm tài xế.');
            
            $_SESSION['flash_success'] = "Thanh toán thành công! Hệ thống đang tìm tài xế cho đơn hàng của bạn.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }

    // Xử lý khách hàng tự hủy đơn hàng và hoàn tiền (nếu đã thanh toán) vào ví/tài khoản.
    public function cancel(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();
        $data = $request->getBody();
        $cancelReason = app_sanitize($data['cancel_reason'] ?? 'Không có lý do cụ thể');

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        $cancellableStatuses = ['awaiting_payment', 'searching_driver', 'accepted', 'picking_up'];
        if (!in_array($order['status'], $cancellableStatuses)) {
            $_SESSION['flash_error'] = "Đơn hàng đang trong quá trình giao hoặc đã xử lý xong, không thể tự hủy lúc này.";
            return $response->redirect("/user/orders/track/{$trackingCode}");
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $description = "Khách hàng đã tự hủy đơn hàng. Lý do: " . $cancelReason;

            if (in_array($order['status'], ['accepted', 'picking_up']) && !empty($order['driver_id'])) {
                if (($order['payment_method'] ?? 'cash') === 'cash') {
                    $walletModel = new Wallet();
                    $settingModel = new Setting();
                    $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
                    $feePerOrder = (int) ceil(($order['shipping_fee'] ?? 0) * $platformFeePercent / 100);
                    $walletModel->add($order['driver_id'], $feePerOrder, 'refund', "Hoàn phí nền tảng do khách tự hủy đơn #{$order['tracking_code']}", $order['id']);

                    $userModel = new User();
                    $userModel->createNotification(
                        $order['driver_id'],
                        'Hoàn phí nền tảng',
                        "Hệ thống đã hoàn lại " . number_format($feePerOrder, 0, ',', '.') . "đ phí nền tảng do khách hàng đã chủ động hủy đơn hàng #{$order['tracking_code']}.",
                        'wallet',
                        "/driver/orders/view/{$order['id']}"
                    );
                } else {
                    $userModel = new User();
                    $userModel->createNotification(
                        $order['driver_id'],
                        'Khách hàng hủy đơn',
                        "Khách hàng đã chủ động hủy đơn hàng #{$order['tracking_code']}. Rất mong bạn thông cảm.",
                        'order',
                        "/driver/orders/view/{$order['id']}"
                    );
                }
            }

            $orderModel = new Order();
            $orderModel->updateStatus($order['id'], 'cancelled', $description);
            $refunded = ($order['payment_status'] ?? '') === 'paid' && $orderModel->refundPaidOrder($order['id']);
            if ($refunded) {
                $orderModel->addStatusHistory($order['id'], 'cancelled', 'Hệ thống hoàn tiền cho khách do khách tự hủy đơn đã thanh toán.');
            }
            $db->commit();
            $_SESSION['flash_success'] = $refunded
                ? "Đã hủy đơn hàng thành công. Hệ thống đã ghi nhận hoàn tiền cho đơn đã thanh toán."
                : "Đã hủy đơn hàng thành công.";
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi hủy đơn hàng.";
        }

        return $response->redirect('/user/orders');
    }

    // Tạo yêu cầu khiếu nại đối với các đơn hàng đã hoàn thành, hoàn trả hoặc bị hủy.
    public function dispute(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();
        $data = $request->getBody();
        $issueType = app_sanitize($data['issue_type'] ?? '');
        $reason = app_sanitize($data['reason'] ?? '');

        if (empty($reason) || empty($issueType)) {
            $_SESSION['flash_error'] = "Vui lòng chọn loại sự cố và nhập chi tiết khiếu nại.";
            return $response->redirect('/user/orders/track/' . $trackingCode);
        }

        $fullReason = $issueType . " - Chi tiết: " . $reason;

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        $disputableStatuses = ['completed', 'returning', 'returned', 'cancelled'];
        if (!in_array($order['status'], $disputableStatuses)) {
            $_SESSION['flash_error'] = "Chỉ có thể khiếu nại đối với đơn hàng đã giao xong, chuyển hoàn hoặc đã bị hủy.";
            return $response->redirect("/user/orders/track/{$trackingCode}");
        }

        try {
            $dbReason = $fullReason;
            $proofImagePath = '';
            
            // Tận dụng SDK Cloudinary để upload file an toàn
            if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
                $validation = app_validate_uploaded_image($_FILES['proof_image']);
                if ($validation['valid']) {
                    Configuration::instance($_ENV['CLOUDINARY_URL']);
                    $uploadApi = new UploadApi();
                    $result = $uploadApi->upload($_FILES['proof_image']['tmp_name'], [
                        'folder' => 'nun_express/proofs',
                        'public_id' => 'dispute_' . $order['id'] . '_' . time()
                    ]);
                    $proofImagePath = $result['secure_url'];
                    $dbReason .= "||PROOF||" . $proofImagePath;
                }
            }

            $description = "Khách hàng khiếu nại: " . $fullReason;
            if (!empty($proofImagePath)) {
                $description .= "<br><br><div class='proof-image-wrapper'><strong>Ảnh đính kèm:</strong><br><a href='" . $proofImagePath . "' target='_blank' title='Nhấn để xem ảnh lớn'><img src='" . $proofImagePath . "' alt='Ảnh minh chứng' class='proof-image' style='max-width: 200px; margin-top: 8px; border-radius: 4px;'></a></div>";
            }

            $orderModel = new Order();
            $orderModel->updateStatus($order['id'], 'disputed', $description);

            $disputeModel = new Dispute();
            $disputeModel->create($order['id'], $userId, $dbReason);

            (new Notification())->notifyAdmins(
                'Khiếu nại mới',
                "Khách hàng vừa gửi yêu cầu khiếu nại đối với đơn hàng #{$order['tracking_code']}.",
                'system',
                "/admin/orders/view/{$order['id']}"
            );

            $_SESSION['flash_success'] = "Đã gửi yêu cầu khiếu nại thành công. Quản trị viên sẽ sớm liên hệ giải quyết.";
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi gửi khiếu nại.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }

    // Khách hàng tự rút lại yêu cầu khiếu nại, đóng tranh chấp và khôi phục trạng thái cũ.
    public function withdrawDispute(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        if ($order['status'] !== 'disputed') {
            $_SESSION['flash_error'] = "Đơn hàng không trong trạng thái khiếu nại.";
            return $response->redirect('/user/orders');
        }

        try {
            $orderModel = new Order();
            $prevStatus = $orderModel->getPreviousStatus($order['id']);

            $orderModel->updateStatus($order['id'], $prevStatus, "Khách hàng đã tự rút lại yêu cầu khiếu nại.");

            // 2. Đóng khiếu nại trong bảng order_disputes
            $disputeModel = new Dispute();
            $disputeModel->withdrawByOrderId($order['id']);

            $_SESSION['flash_success'] = "Đã rút lại khiếu nại thành công.";
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi rút lại khiếu nại.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }
}
