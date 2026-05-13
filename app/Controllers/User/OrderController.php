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

/**
 * Class OrderController
 * Xử lý các yêu cầu liên quan đến đơn hàng của người dùng.
 */
class OrderController extends BaseController
{
    /**
     * Helper: Tìm đơn hàng theo tracking_code của user hiện tại, hoặc trả về lỗi
     */
    private function getOrderOrFail(string $trackingCode): ?array
    {
        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $this->userId());
        if (!$order) {
            $_SESSION['flash_error'] = "Không tìm thấy đơn hàng.";
        }
        return $order ?: null;
    }

    /**
     * Hiển thị form để tạo đơn hàng mới.
     * Tương ứng với route: GET /user/orders/create
     */
    public function create(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        return $this->renderCreateForm($response);
    }

    /**
     * Lưu đơn hàng mới vào cơ sở dữ liệu.
     * Tương ứng với route: POST /user/orders/create
     */
    public function store(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        // 1. Lấy toàn bộ dữ liệu từ form
        // Làm sạch toàn bộ dữ liệu POST để chống XSS
        $data = app_sanitize($request->getBody());

        try {
            // 2. Validate dữ liệu
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

            // Yêu cầu thời gian hẹn lấy đối với đơn Hỏa tốc / Giao nhanh
            if (in_array($data['shipping_method'] ?? '', ['fast', 'express'])) {
                if (!empty($data['scheduled_at'])) {
                    $scheduledTime = strtotime($data['scheduled_at']);
                    if ($scheduledTime > time() + 15 * 60) { // 10 phút + 5 phút thao tác điền form
                        // Hệ thống tự động đặt lại giờ lấy hàng
                        $data['scheduled_at'] = date('Y-m-d\TH:i', time() + 10 * 60);
                        $_SESSION['flash_error'] = "Đơn hàng Giao nhanh/Siêu tốc yêu cầu thời gian hẹn lấy hàng không được quá 10 phút kể từ hiện tại. Hệ thống đã đặt lại giờ cho bạn.";
                        throw new ValidationException([], $data);
                    }
                }
            }

            // Kiểm tra giới hạn cân nặng từ Setting
            $settingModel = new Setting();
            $maxOrderWeight = (float) $settingModel->get('max_order_weight', $settingModel->get('default_max_total_weight', 100));
            
            if (isset($data['weight']) && (float) $data['weight'] > $maxOrderWeight) {
                $_SESSION['flash_error'] = "Cân nặng đơn hàng không được vượt quá {$maxOrderWeight}kg.";
                throw new ValidationException([], $data);
            }

            // 3. Nếu không có lỗi, gọi Service để xử lý nghiệp vụ
            return $this->createOrder($response, $data);
        } catch (ValidationException $e) {
            // 4. Nếu có lỗi, render lại form và gửi kèm lỗi + dữ liệu cũ
            return $this->renderCreateForm($response, ['errors' => $e->errors, 'old' => $e->old]);
        }
    }

    private function createOrder(Response $response, array $data)
    {
        $originalData = $data;

        try {
            $data['customer_id'] = $this->userId();
            $data['sender_name'] = $this->resolveSenderField($data['sender_name'] ?? '', 'name');
            $data['sender_phone'] = $this->resolveSenderField($data['sender_phone'] ?? '', 'phone');
            $data['pickup_address'] = $this->mergeDetailedAddress(
                $data['pickup_address'] ?? '',
                $data['pickup_address_detail'] ?? ''
            );
            $data['delivery_address'] = $this->mergeDetailedAddress(
                $data['delivery_address'] ?? '',
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
            // Nếu có lỗi từ service, hiển thị lại form với lỗi
            return $this->renderCreateForm($response, ['errors' => ['general' => $e->getMessage()], 'old' => $originalData]);
        }
    }

    private function renderCreateForm(Response $response, array $params = [])
    {
        $defaults = [
            'pageTitle' => 'Tạo đơn hàng mới',
        ];

        return $response->render('user/orders/create', array_merge($defaults, $params));
    }

    private function resolveSenderField(string $value, string $sessionKey): string
    {
        $value = trim($value);
        if ($value !== '') {
            return $value;
        }

        return (string) $this->currentUser($sessionKey, '');
    }

    private function mergeDetailedAddress(string $baseAddress, string $detailAddress): string
    {
        $baseAddress = trim($baseAddress);
        $detailAddress = trim($detailAddress);

        if ($detailAddress === '') {
            return $baseAddress;
        }

        if ($baseAddress === '') {
            return $detailAddress;
        }

        if (stripos($baseAddress, $detailAddress) !== false) {
            return $baseAddress;
        }

        return $detailAddress . ', ' . $baseAddress;
    }

    /**
     * Hiển thị danh sách đơn hàng của người dùng.
     * Tương ứng với route: GET /user/dashboard hoặc GET /user/orders
     */
    public function index(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'user')) {
            return $redirect;
        }

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

    /**
     * Hiển thị chi tiết theo dõi một đơn hàng.
     * Tương ứng với route: GET /user/orders/track/{code}
     */
    public function track(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

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

    /**
     * API: Lấy vị trí realtime của tài xế (Dùng cho AJAX Polling)
     * Route: GET /api/orders/driver-location/{code}
     */
    public function apiDriverLocation(Request $request, Response $response)
    {
        $trackingCode = $request->getRouteParam('code');
        $orderModel = new Order();
        
        $location = $orderModel->getDriverLocationByTrackingCode($trackingCode);
        
        if ($location && $location['current_lat'] !== null) {
            return $response->json([
                'success' => true,
                'lat' => (float) $location['current_lat'],
                'lng' => (float) $location['current_lng']
            ]);
        }

        return $response->json(['success' => false]);
    }

    /**
     * API: Tính trước cước phí bằng AI (Dùng cho AJAX Preview)
     * Route: POST /api/orders/calculate-fee
     */
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
            ]); // Xóa mã 400, để mặc định là 200 OK giúp JS đọc được chuỗi message
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

    /**
     * Hiển thị giao diện đánh giá tài xế
     * Route: GET /user/orders/review/{id}
     */
    public function review(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $orderId = (int) $request->getRouteParam('id');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByIdAndUserId($orderId, $userId);

        if (!$order) {
            $_SESSION['flash_error'] = 'Đơn hàng không tồn tại.';
            return $response->redirect('/user/orders');
        }

        // Validate điều kiện đánh giá
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

    /**
     * Xử lý lưu đánh giá
     * Route: POST /user/orders/review/{id}
     */
    public function storeReview(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $orderId = (int) $request->getRouteParam('id');
        $userId = $this->userId();
        $data = $request->getBody();

        // BẢO MẬT: Kiểm tra đơn hàng phải tồn tại, thuộc về user này, và phải ở trạng thái completed
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
        $comment = app_sanitize($data['comment'] ?? ''); // <-- LÀM SẠCH COMMENT
        
        // Nối mảng tags vào đầu comment (VD: "Tài xế thân thiện, Giao hàng nhanh. Tài xế rất vui vẻ")
        $tags = $data['tags'] ?? [];
        if (!empty($tags) && is_array($tags)) {
            $tagString = implode(', ', array_map('app_sanitize', $tags)); // <-- LÀM SẠCH CẢ TAGS
            $comment = $comment !== '' ? $tagString . '. ' . $comment : $tagString;
        }

        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = 'Vui lòng chọn số sao.';
            return $response->redirect("/user/orders/review/{$orderId}");
        }

        $reviewModel = new Review();
        
        // Chống đánh giá 2 lần
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

    /**
     * Hiển thị trang thanh toán Online (QR Code)
     * Route: GET /user/orders/payment/{code}
     */
    public function payment(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

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

    /**
     * Xử lý xác nhận thanh toán (Giả lập webhook ngân hàng)
     * Route: POST /user/orders/payment/{code}
     */
    public function processPayment(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $orderModel = new Order();
        $order = $orderModel->findByTrackingCodeForUser($trackingCode, $userId);

        if ($order && $order['status'] === 'awaiting_payment') {
            // Giả lập thanh toán thành công: Cập nhật DB
            $orderModel->updatePaymentStatus($order['id'], 'paid');
            $orderModel->updateStatus($order['id'], 'searching_driver', 'Khách hàng đã thanh toán thành công. Bắt đầu tìm tài xế.');
            
            $_SESSION['flash_success'] = "Thanh toán thành công! Hệ thống đang tìm tài xế cho đơn hàng của bạn.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }

    /**
     * Xử lý khách hàng tự hủy đơn
     * Route: POST /user/orders/cancel/{code}
     */
    public function cancel(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();
        $data = $request->getBody();
        $cancelReason = app_sanitize($data['cancel_reason'] ?? 'Không có lý do cụ thể');

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        // Chỉ cho phép hủy khi: Chờ thanh toán, Đang tìm tài xế, Tài xế mới nhận đơn, hoặc Đang lấy hàng (chưa lấy hàng thành công)
        $cancellableStatuses = ['awaiting_payment', 'searching_driver', 'accepted', 'picking_up'];
        if (!in_array($order['status'], $cancellableStatuses)) {
            $_SESSION['flash_error'] = "Đơn hàng đang trong quá trình giao hoặc đã xử lý xong, không thể tự hủy lúc này.";
            return $response->redirect("/user/orders/track/{$trackingCode}");
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $description = "Khách hàng đã tự hủy đơn hàng. Lý do: " . $cancelReason;

            // Nếu tài xế đã nhận đơn hoặc đang lấy hàng, phải hoàn lại phí nền tảng cho tài xế
            if (in_array($order['status'], ['accepted', 'picking_up']) && !empty($order['driver_id'])) {
                if (($order['payment_method'] ?? 'cash') === 'cash') {
                    $walletModel = new Wallet();
                    $settingModel = new Setting();
                    $platformFeePercent = (float) $settingModel->get('platform_fee_percent', 20);
                    $feePerOrder = (int) ceil(($order['shipping_fee'] ?? 0) * $platformFeePercent / 100);
                    $walletModel->add($order['driver_id'], $feePerOrder, 'refund', "Hoàn phí nền tảng do khách tự hủy đơn #{$order['tracking_code']}", $order['id']);

                    // GỬI THÔNG BÁO CHO TÀI XẾ
                    $userModel = new User();
                    $userModel->createNotification(
                        $order['driver_id'],
                        'Cộng tiền - Hoàn phí',
                        "Đơn hàng #{$order['tracking_code']} đã bị khách hủy. Ví của bạn được cộng " . number_format($feePerOrder, 0, ',', '.') . "đ (hoàn phí).",
                        'wallet',
                        "/driver/orders/view/{$order['id']}"
                    );
                } else {
                    $userModel = new User();
                    $userModel->createNotification(
                        $order['driver_id'],
                        'Khách hàng hủy đơn',
                        "Đơn hàng #{$order['tracking_code']} đã bị hủy bởi khách hàng.",
                        'order',
                        "/driver/orders/view/{$order['id']}"
                    );
                }
            }

            $orderModel = new Order();
            $orderModel->updateStatus($order['id'], 'cancelled', $description);
            $db->commit();
            $_SESSION['flash_success'] = "Đã hủy đơn hàng thành công.";
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Cancel Order Failed: ' . $e->getMessage());
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi hủy đơn hàng.";
        }

        return $response->redirect('/user/orders');
    }

    /**
     * Xử lý khách hàng khiếu nại đơn hàng
     * Route: POST /user/orders/dispute/{code}
     */
    public function dispute(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();
        $data = $request->getBody();
        $reason = app_sanitize($data['reason'] ?? '');

        if (empty($reason)) {
            $_SESSION['flash_error'] = "Vui lòng nhập lý do khiếu nại.";
            return $response->redirect('/user/orders/track/' . $trackingCode);
        }

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        // Cho phép khiếu nại đối với các đơn đã Giao xong, Đã hủy, Chuyển hoàn hoặc Đã hoàn
        $disputableStatuses = ['completed', 'returning', 'returned', 'cancelled'];
        if (!in_array($order['status'], $disputableStatuses)) {
            $_SESSION['flash_error'] = "Chỉ có thể khiếu nại đối với đơn hàng đã giao xong, chuyển hoàn hoặc đã bị hủy.";
            return $response->redirect("/user/orders/track/{$trackingCode}");
        }

        try {
            $description = "Khách hàng khiếu nại: " . $reason;
            $orderModel = new Order();
            $orderModel->updateStatus($order['id'], 'disputed', $description);

            // Lưu vào bảng order_disputes để Admin quản lý
            $disputeModel = new Dispute();
            $disputeModel->create($order['id'], $userId, $reason);

            // GỬI THÔNG BÁO CHO TẤT CẢ ADMIN
            (new Notification())->notifyAdmins(
                'Khiếu nại mới',
                "Đơn hàng #{$order['tracking_code']} vừa bị khách hàng khiếu nại.",
                'system',
                "/admin/orders/view/{$order['id']}"
            );

            $_SESSION['flash_success'] = "Đã gửi yêu cầu khiếu nại thành công. Quản trị viên sẽ sớm liên hệ giải quyết.";
        } catch (\Exception $e) {
            error_log('Dispute Order Failed: ' . $e->getMessage());
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi gửi khiếu nại.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }

    /**
     * Xử lý khách hàng rút lại khiếu nại
     * Route: POST /user/orders/withdraw-dispute/{code}
     */
    public function withdrawDispute(Request $request, Response $response)
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $trackingCode = $request->getRouteParam('code');
        $userId = $this->userId();

        $order = $this->getOrderOrFail($trackingCode);
        if (!$order) return $response->redirect('/user/orders');

        if ($order['status'] !== 'disputed') {
            $_SESSION['flash_error'] = "Đơn hàng không trong trạng thái khiếu nại.";
            return $response->redirect('/user/orders');
        }

        try {
            // Lấy trạng thái gần nhất trước khi chuyển sang disputed
            $orderModel = new Order();
            $prevStatus = $orderModel->getPreviousStatus($order['id']);

            // 1. Cập nhật trạng thái đơn hàng quay về trước đó
            $orderModel->updateStatus($order['id'], $prevStatus, "Khách hàng đã tự rút lại yêu cầu khiếu nại.");

            // 2. Đóng khiếu nại trong bảng order_disputes
            $disputeModel = new Dispute();
            $disputeModel->withdrawByOrderId($order['id']);

            $_SESSION['flash_success'] = "Đã rút yêu cầu khiếu nại thành công.";
        } catch (\Exception $e) {
            error_log('Withdraw Dispute Failed: ' . $e->getMessage());
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi rút khiếu nại.";
        }

        return $response->redirect('/user/orders/track/' . $trackingCode);
    }
}
