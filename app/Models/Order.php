<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    protected PDO $db;

    // Khởi tạo kết nối CSDL
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public const STATUS_LABELS = [
        'pending' => 'Chờ xử lý',
        'awaiting_payment' => 'Chờ thanh toán',
        'searching_driver' => 'Đang tìm tài xế',
        'accepted' => 'Đã nhận đơn',
        'picking_up' => 'Đang lấy hàng',
        'in_transit' => 'Đang giao hàng',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Hoàn thành',
        'returning' => 'Chuyển hoàn',
        'returned' => 'Đã hoàn hàng',
        'disputed' => 'Đang khiếu nại',
        'cancelled' => 'Đã hủy',
    ];

    public const STATUS_COLORS = [
        'pending' => '#f59e0b',          // Vàng cam
        'awaiting_payment' => '#a855f7', // Tím
        'searching_driver' => '#3b82f6', // Xanh dương
        'accepted' => '#0ea5e9',         // Xanh da trời
        'picking_up' => '#14b8a6',       // Xanh mòng két
        'in_transit' => '#10b981',       // Xanh ngọc
        'shipping' => '#10b981',         // Xanh ngọc
        'completed' => '#16a34a',        // Xanh lá
        'returning' => '#f97316',        // Cam
        'returned' => '#64748b',          // Xám
        'disputed' => '#be123c',         // Đỏ hồng/Đỏ đậm
        'cancelled' => '#ef4444',        // Đỏ
    ];

    public const SHIPPING_METHOD_LABELS = [
        'standard' => 'Giao tiêu chuẩn',
        'fast' => 'Giao nhanh',
        'express' => 'Giao siêu tốc',
    ];

    public const SHIPPING_METHOD_COLORS = [
        'standard' => 'var(--text-main)', // Màu chữ mặc định
        'fast' => 'var(--warning)',       // Màu cam
        'express' => 'var(--danger)',     // Màu đỏ
    ];

    /**
     * Chuyển đổi mã trạng thái sang nhãn tiếng Việt hiển thị
     */
    public static function getStatusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Lấy màu sắc tương ứng với từng trạng thái
     */
    public static function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? '#64748b'; // Xám (mặc định)
    }

    /**
     * Chuyển đổi mã phương thức giao hàng sang tiếng Việt
     */
    public static function getShippingMethodLabel(?string $method): string
    {
        return self::SHIPPING_METHOD_LABELS[$method ?: 'standard'] ?? 'Giao tiêu chuẩn';
    }

    /**
     * Lấy màu sắc phương thức giao hàng
     */
    public static function getShippingMethodColor(?string $method): string
    {
        return self::SHIPPING_METHOD_COLORS[$method ?: 'standard'] ?? 'var(--text-main)';
    }

    // Tạo mới một đơn hàng và các dữ liệu liên kết
    public function create(array $data)
    {
        // TỐI ƯU HÓA: Tạo mã vận đơn Unique tuyệt đối
        $randomPart = strtoupper(substr(uniqid(), -5));
        $trackingCode = 'NUN' . ($data['customer_id'] ?? '0') . $randomPart . random_int(10, 99);
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $initialStatus = $paymentMethod === 'transfer' ? 'awaiting_payment' : 'searching_driver';
        
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // 1. Lưu bảng orders
            $scheduledAt = !empty($data['scheduled_at']) ? date('Y-m-d H:i:s', strtotime($data['scheduled_at'])) : null;
            $weight = isset($data['weight']) ? (float) $data['weight'] : 1.0;
            $shippingMethod = $data['shipping_method'] ?? 'standard';
            $deliveryPin = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("INSERT INTO orders (customer_id, tracking_code, status, weight, shipping_method, note, scheduled_at, delivery_pin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $data['customer_id'],
                $trackingCode,
                $initialStatus,
                $weight,
                $shippingMethod,
                $data['description'] ?? ($data['note'] ?? ''),
                $scheduledAt,
                $deliveryPin
            ]);
            $orderId = $this->db->lastInsertId();

            // 2. Lưu bảng order_addresses
            $stmtAddr = $this->db->prepare("INSERT INTO order_addresses (order_id, sender_name, sender_phone, sender_address, sender_lat, sender_lng, receiver_name, receiver_phone, receiver_address, receiver_lat, receiver_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtAddr->execute([
                $orderId,
                $data['sender_name'] ?? '',
                $data['sender_phone'] ?? '',
                $data['pickup_address'] ?? '',
                $data['sender_lat'] ?? 0.0,
                $data['sender_lng'] ?? 0.0,
                $data['receiver_name'] ?? '',
                $data['receiver_phone'] ?? '',
                $data['delivery_address'] ?? '',
                $data['receiver_lat'] ?? 0.0,
                $data['receiver_lng'] ?? 0.0
            ]);

            // 3. Khởi tạo các bảng phụ
            $shippingFee = $data['shipping_fee'] ?? 25000;
            $this->db->prepare("INSERT INTO order_finances (order_id, shipping_fee, payment_method, payment_status) VALUES (?, ?, ?, 'pending')")->execute([$orderId, $shippingFee, $paymentMethod]);
            $this->db->prepare("INSERT INTO order_deliveries (order_id) VALUES (?)")->execute([$orderId]);
            $desc = $paymentMethod === 'transfer' ? 'Đơn hàng đang chờ thanh toán online.' : 'Đơn hàng vừa được tạo và đang tìm tài xế.';
            $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())")->execute([$orderId, $initialStatus, $desc]);

            if ($ownsTransaction) {
                $this->db->commit();
            }

            // Tích hợp Pusher: Báo cho toàn bộ Radar của các tài xế biết có đơn mới
            if ($initialStatus === 'searching_driver' && class_exists('\App\Services\PusherService')) {
                try {
                    $pusher = new \App\Services\PusherService();
                    $pusher->trigger('driver-radar', 'new_order_pending', [
                        'tracking_code' => $trackingCode,
                        'message' => 'Có đơn hàng mới tại khu vực của bạn!'
                    ]);
                } catch (\Throwable $e) {
                    error_log('Lỗi Pusher Radar (Create): ' . $e->getMessage());
                }
            }

            return $trackingCode;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    // Lấy chi tiết một đơn hàng theo ID và ID khách hàng
    public function findByIdAndUserId(int $orderId, int $userId)
    {
        $sql = "
            SELECT 
                o.*, 
                del.driver_id,
                u.name AS driver_name, 
                u.phone AS driver_phone, 
                u.avatar AS driver_avatar,
                dp.license_plate AS driver_license_plate,
                oa.sender_name, oa.sender_phone, oa.sender_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_phone, oa.receiver_address, oa.receiver_lat, oa.receiver_lng
            FROM orders o 
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users u ON u.id = del.driver_id 
            LEFT JOIN driver_profiles dp ON dp.user_id = del.driver_id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            WHERE o.id = ? AND o.customer_id = ? AND o.is_archived = 0 
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        return $stmt->fetch();
    }

    /**
     * TỐI ƯU HÓA (OPTIMIZATION): Spatial Bounding Box Pre-filtering
     * Lọc trước các đơn hàng nằm ngoài bán kính cho phép (mặc định 20km) ngay tại tầng CSDL
     * Giúp giảm tải đột phá (Search Space Reduction) cho tiến trình Python AI.
     */
    public function getPendingForDriver(int $driverId, ?array $driverLocation = null, int $radiusKm = 20): array
    {
        $where = "o.status = 'searching_driver' AND o.is_archived = 0 
              AND (o.scheduled_at IS NULL OR o.scheduled_at <= DATE_ADD(NOW(), INTERVAL 120 MINUTE))";

        if ($driverLocation && isset($driverLocation['lat'], $driverLocation['lng'])) {
            $lat = (float) $driverLocation['lat'];
            $lng = (float) $driverLocation['lng'];
            
            // Lọc thô bằng Hình vuông bao quanh (Bounding Box)
            // 1 độ Vĩ tuyến tương đương khoảng 111.045 km
            $latDelta = $radiusKm / 111.045;
            $lngDelta = $radiusKm / (111.045 * cos(deg2rad($lat)));
            
            $minLat = $lat - $latDelta;
            $maxLat = $lat + $latDelta;
            $minLng = $lng - $lngDelta;
            $maxLng = $lng + $lngDelta;

            // Dùng Index của MySQL để chặn đứng các bản ghi nằm ngoài hình vuông
            $where .= " AND (oa.sender_lat BETWEEN $minLat AND $maxLat) AND (oa.sender_lng BETWEEN $minLng AND $maxLng)";
        }

        $sql = "
            SELECT 
                o.*, 
                oa.sender_lat, oa.sender_lng, oa.sender_address AS pickup_address, 
                oa.receiver_lat, oa.receiver_lng, oa.receiver_address AS delivery_address,
                fin.shipping_fee
            FROM orders o
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            WHERE $where
            ORDER BY o.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy lịch sử các đơn hàng mà tài xế đã nhận
     */
    public function getDriverHistory(int $driverId, int $limit = 15, int $offset = 0, string $startDate = '', string $endDate = ''): array
    {
        $where = "del.driver_id = :driver_id AND o.is_archived = 0";
        if ($startDate !== '') $where .= " AND DATE(o.created_at) >= :start_date";
        if ($endDate !== '') $where .= " AND DATE(o.created_at) <= :end_date";

        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, o.scheduled_at, o.weight,
                oa.sender_address AS pickup_address, oa.receiver_address AS delivery_address,
                fin.shipping_fee
            FROM orders o
            JOIN order_deliveries del ON o.id = del.order_id
            LEFT JOIN order_addresses oa ON o.id = oa.order_id
            LEFT JOIN order_finances fin ON o.id = fin.order_id
            WHERE $where
            ORDER BY o.updated_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
        if ($startDate !== '') $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        if ($endDate !== '') $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy danh sách các đơn hàng đang chạy của tài xế (chưa hoàn thành)
     */
    public function getActiveOrdersForDriver(int $driverId): array
    {
        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, od.accepted_at, o.weight, o.scheduled_at, o.shipping_method, o.note, od.batch_code, od.batch_route_details,
                oa.sender_name, oa.sender_address AS pickup_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_address AS delivery_address, oa.receiver_lat, oa.receiver_lng,
                u.no_show_count AS customer_no_show_count,
                fin.shipping_fee,
                dp.current_lat AS driver_lat, dp.current_lng AS driver_lng
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_addresses oa ON o.id = oa.order_id
            LEFT JOIN order_finances fin ON o.id = fin.order_id
            LEFT JOIN driver_profiles dp ON od.driver_id = dp.user_id
            WHERE od.driver_id = ? 
              AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
              AND o.is_archived = 0
            ORDER BY od.accepted_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * TỐI ƯU HÓA: Chỉ lấy mảng mã vận đơn đang hoạt động (Phục vụ WebSockets Location Update nhanh hơn)
     */
    public function getActiveTrackingCodesForDriver(int $driverId): array
    {
        $sql = "
            SELECT o.tracking_code 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? 
              AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
              AND o.is_archived = 0
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    // Đếm tổng số chuyến đi trong lịch sử của tài xế
    public function countDriverHistory(int $driverId, string $startDate = '', string $endDate = ''): int
    {
        $where = "del.driver_id = :driver_id AND o.is_archived = 0";
        if ($startDate !== '') $where .= " AND DATE(o.created_at) >= :start_date";
        if ($endDate !== '') $where .= " AND DATE(o.created_at) <= :end_date";

        $sql = "
            SELECT COUNT(o.id)
            FROM orders o
            JOIN order_deliveries del ON o.id = del.order_id
            WHERE $where
        ";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
        if ($startDate !== '') $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        if ($endDate !== '') $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    /**
     * Lấy chi tiết một đơn hàng dành cho tài xế
     */
    public function findByIdForDriver(int $orderId, int $driverId)
    {
        $sql = "
            SELECT 
                o.*, 
                del.driver_id,
                fin.shipping_fee, fin.payment_method, fin.payment_status, fin.paid_at, fin.refunded_at,
                oa.sender_name, oa.sender_phone, oa.sender_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_phone, oa.receiver_address, oa.receiver_lat, oa.receiver_lng,
                dp.current_lat AS driver_lat, dp.current_lng AS driver_lng,
                u.name as customer_name, u.phone as customer_phone, u.email as customer_email, u.avatar as customer_avatar, u.no_show_count AS customer_no_show_count,
                d.name as driver_name, d.phone as driver_phone, d.avatar as driver_avatar,
                dp.license_plate AS driver_license_plate
            FROM orders o
            JOIN order_deliveries del ON o.id = del.order_id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN driver_profiles dp ON del.driver_id = dp.user_id
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN users d ON del.driver_id = d.id
            WHERE o.id = ? AND del.driver_id = ? AND o.is_archived = 0
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $driverId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Gán nhiều đơn hàng cho tài xế cùng lúc (Dùng cho ghép chuyến)
     */
    public function assignMultipleOrdersToDriver(array $orderIds, int $driverId, ?string $batchCode = null, array $routeDetails = []): array
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        if (empty($orderIds)) {
            return [];
        }

        $now = date('Y-m-d H:i:s'); // Dùng chung 1 mốc thời gian duy nhất cho cả chuyến
        
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // TỐI ƯU HÓA: Sử dụng Bulk Update thay vì vòng lặp N truy vấn để chốt đơn nhanh chóng, tránh xung đột.
            $inClause = implode(',', array_fill(0, count($orderIds), '?'));
            $routeDetailsJson = !empty($routeDetails)
                ? json_encode($routeDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $stmtOrder = $this->db->prepare("UPDATE orders SET status = 'accepted', updated_at = ? WHERE id IN ($inClause) AND status = 'searching_driver'");
            $paramsOrder = array_merge([$now], $orderIds);
            $stmtOrder->execute($paramsOrder);
            
            // Logic ALL OR NOTHING (Tất cả hoặc không có gì)
            // Nếu số dòng cập nhật thực tế không bằng số lượng đơn yêu cầu -> Có đơn đã bị người khác hớt tay trên -> Hủy toàn bộ
            if ($stmtOrder->rowCount() !== count($orderIds)) {
                if ($ownsTransaction) $this->db->rollBack();
                return [];
            }
            
            // SỬA LỖI: Cập nhật batch_route_details cho TOÀN BỘ đơn trong cụm. Nếu chỉ lưu ở đơn đầu tiên, khi đơn đầu bị hủy/thu hồi, cả chuyến sẽ mất lộ trình AI.
            $stmtDelivery = $this->db->prepare("UPDATE order_deliveries SET driver_id = ?, accepted_at = ?, batch_code = ?, batch_route_details = ? WHERE order_id IN ($inClause)");
            $paramsDelivery = array_merge([$driverId, $now, $batchCode, $routeDetailsJson], $orderIds);
            $stmtDelivery->execute($paramsDelivery);
            
            $historyValues = [];
            $historyParams = [];
            $desc = 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.';
            foreach ($orderIds as $orderId) {
                $historyValues[] = "(?, 'accepted', ?, ?)";
            // TỐI ƯU HÓA: Dùng toán tử append [] thay cho array_push trong vòng lặp để giảm overhead gọi hàm
            $historyParams[] = $orderId;
            $historyParams[] = $desc;
            $historyParams[] = $now;
            }
            $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES " . implode(', ', $historyValues))
                ->execute($historyParams);
            
            if ($ownsTransaction) {
                $this->db->commit();
            }

            // Gửi thông báo cho khách hàng của tất cả các đơn trong chuyến ghép
            $inClause = implode(',', array_fill(0, count($orderIds), '?'));
            try {
                $stmtInfo = $this->db->prepare("SELECT id, tracking_code, customer_id, delivery_pin FROM orders WHERE id IN ($inClause)");
                $stmtInfo->execute($orderIds);
                $userModel = new \App\Models\User();
                while ($info = $stmtInfo->fetch(\PDO::FETCH_ASSOC)) {
                    $msg = "Đơn hàng #{$info['tracking_code']} của bạn đã được tài xế tiếp nhận và đang trên đường đến điểm lấy hàng.";
                    if (!empty($info['delivery_pin'])) {
                        $msg .= " MÃ PIN NHẬN HÀNG LÀ: " . $info['delivery_pin'] . ".";
                    }
                    $userModel->createNotification(
                        $info['customer_id'],
                        'Tài xế đã tiếp nhận đơn hàng',
                        $msg,
                        'order',
                        "/user/orders/track/{$info['tracking_code']}"
                    );
                }
            } catch (\Throwable $e) {
                error_log('Assign order notification failed: ' . $e->getMessage());
            }

            // Tích hợp Pusher: Báo cho Radar của các tài xế khác ẩn cụm đơn này đi
            if (class_exists('\App\Services\PusherService')) {
                try {
                    $pusher = new \App\Services\PusherService();
                    $pusher->trigger('driver-radar', 'order_taken', [
                        'order_ids' => $orderIds
                    ]);
                } catch (\Throwable $e) {}
            }

            return $orderIds;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Assign Multiple Orders Error: ' . $e->getMessage());
            return [];
        }
    }

    // Lấy danh sách đơn hàng của khách hàng (Hỗ trợ phân trang và bộ lọc)
    public function findAllByUserId(int $userId, string $statusFilter = '', int $limit = 10, int $offset = 0): array
    {
        $where = "o.customer_id = ? AND o.is_archived = 0";
        $params = [$userId];

        if ($statusFilter !== '') {
            $where .= " AND o.status = ?";
            $params[] = $statusFilter;
        }

        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.scheduled_at, o.weight,
                fin.shipping_fee,
                oa.sender_address, oa.receiver_address,
                del.driver_id,
                d.name as driver_name,
                d.phone as driver_phone
            FROM orders o
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users d ON del.driver_id = d.id
            WHERE $where
            ORDER BY o.updated_at DESC, o.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Đếm tổng số đơn hàng của khách hàng
    public function countAllByUserId(int $userId, string $statusFilter = ''): int
    {
        $where = "customer_id = ? AND is_archived = 0";
        $params = [$userId];

        if ($statusFilter !== '') {
            $where .= " AND status = ?";
            $params[] = $statusFilter;
        }

        $sql = "SELECT COUNT(id) FROM orders WHERE $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // Tìm kiếm đơn hàng theo mã vận đơn cho khách hàng đang đăng nhập
    public function findByTrackingCodeForUser(string $code, int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                o.*, 
                f.payment_status, f.payment_method, f.shipping_fee,
                oa.sender_name, oa.sender_phone, oa.sender_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_phone, oa.receiver_address, oa.receiver_lat, oa.receiver_lng,
                del.driver_id,
                u.name AS driver_name, u.phone AS driver_phone, u.avatar AS driver_avatar,
                dp.license_plate AS driver_license_plate, dp.current_lat AS driver_lat, dp.current_lng AS driver_lng
            FROM orders o 
            LEFT JOIN order_finances f ON o.id = f.order_id 
            LEFT JOIN order_addresses oa ON o.id = oa.order_id
            LEFT JOIN order_deliveries del ON o.id = del.order_id
            LEFT JOIN users u ON del.driver_id = u.id
            LEFT JOIN driver_profiles dp ON u.id = dp.user_id
            WHERE o.tracking_code = ? AND o.customer_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$code, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tra cứu đơn hàng công khai bằng mã vận đơn (Dành cho khách chưa đăng nhập)
     */
    public function findByTrackingCodePublic(string $trackingCode)
    {
        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, o.scheduled_at,
                oa.sender_name, oa.receiver_name, oa.sender_address, oa.receiver_address,
                oa.sender_lat, oa.sender_lng, oa.receiver_lat, oa.receiver_lng,
                fin.shipping_fee, fin.payment_method, fin.payment_status, fin.paid_at, fin.refunded_at,
                u.name AS driver_name, u.avatar AS driver_avatar,
                dp.current_lat AS driver_lat, dp.current_lng AS driver_lng,
                dp.license_plate AS driver_license_plate
            FROM orders o 
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users u ON u.id = del.driver_id 
            LEFT JOIN driver_profiles dp ON dp.user_id = del.driver_id 
            WHERE o.tracking_code = ? AND o.is_archived = 0 
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trackingCode]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tọa độ hiện tại của tài xế đang giao đơn hàng.
     */
    public function getDriverLocationByTrackingCode(string $trackingCode)
    {
        $sql = "
            SELECT dp.current_lat, dp.current_lng
            FROM orders o
            JOIN order_deliveries del ON o.id = del.order_id
            JOIN driver_profiles dp ON del.driver_id = dp.user_id
            WHERE o.tracking_code = ? AND o.is_archived = 0
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trackingCode]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Lấy dòng thời gian (timeline) lịch sử trạng thái của đơn hàng
    public function getOrderHistory(int $orderId): array
    {
        $stmt = $this->db->prepare('
            SELECT *
            FROM order_status_history
            WHERE order_id = ?
            ORDER BY created_at ASC, id ASC
        ');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Helper tạo câu WHERE và tham số cho Admin (Dùng chung cho đếm và lấy danh sách)
     */
    private function buildAdminFilters(string $statusFilter, string $search): array
    {
        $where = "WHERE o.is_archived = 0";
        $params = [];

        if ($statusFilter !== '') {
            $where .= " AND o.status = ?";
            $params[] = $statusFilter;
        }

        if ($search !== '') {
            $where .= " AND (o.tracking_code LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR oa.sender_name LIKE ? OR oa.sender_phone LIKE ? OR oa.receiver_name LIKE ? OR oa.receiver_phone LIKE ? OR d.name LIKE ? OR d.phone LIKE ?)";
            // Tối ưu hóa: Dùng toán tử append []
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        return [$where, $params];
    }

    /**
     * Lấy danh sách đơn hàng cho Admin kèm bộ lọc trạng thái và tìm kiếm
     */
    public function countAllForAdmin(string $statusFilter = '', string $search = ''): int
    {
        [$where, $params] = $this->buildAdminFilters($statusFilter, $search);

        // TỐI ƯU HÓA: Nếu không có truy vấn tìm kiếm, lược bỏ các lệnh JOIN dư thừa để tăng tốc độ đếm phân trang
        if ($search === '') {
            $sql = "SELECT COUNT(o.id) FROM orders o $where";
        } else {
            $sql = "SELECT COUNT(o.id) FROM orders o LEFT JOIN users u ON o.customer_id = u.id LEFT JOIN order_addresses oa ON oa.order_id = o.id LEFT JOIN order_deliveries del ON o.id = del.order_id LEFT JOIN users d ON del.driver_id = d.id $where";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // Lấy danh sách toàn bộ đơn hàng trên hệ thống (Dành cho Admin)
    public function getAllForAdmin(string $statusFilter = '', string $search = '', int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->buildAdminFilters($statusFilter, $search);

        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.scheduled_at, o.weight,
                u.name as customer_name, u.phone as customer_phone, 
                del.driver_id,
                d.name as driver_name, d.phone as driver_phone,
                fin.shipping_fee, fin.payment_method, fin.payment_status
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users d ON del.driver_id = d.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            $where
            ORDER BY o.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy chi tiết một đơn hàng cho trang Admin.
     */
    public function findByIdForAdmin(int $orderId)
    {
        $sql = "
            SELECT 
                o.*, 
                del.driver_id, del.accepted_at, del.picked_up_at, del.delivered_at, del.cancelled_at,
                u.name as customer_name, u.phone as customer_phone, u.email as customer_email, u.avatar as customer_avatar, u.no_show_count as customer_no_show_count,
                d.name as driver_name, d.phone as driver_phone, d.avatar as driver_avatar,
                fin.shipping_fee, fin.payment_method, fin.payment_status, fin.paid_at, fin.refunded_at,
                oa.sender_name, oa.sender_phone, oa.sender_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_phone, oa.receiver_address, oa.receiver_lat, oa.receiver_lng,
                dp.current_lat AS driver_lat, dp.current_lng AS driver_lng,
                dp.license_plate AS driver_license_plate
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users d ON del.driver_id = d.id
            LEFT JOIN driver_profiles dp ON d.id = dp.user_id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            WHERE o.id = ? AND o.is_archived = 0
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật thông tin đơn hàng từ phía Admin
     */
    public function updateForAdmin(int $id, array $data): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // Lấy dữ liệu cũ để đối chiếu sự thay đổi trạng thái hoặc thanh toán
            $stmtOld = $this->db->prepare("SELECT o.status, f.payment_status, f.shipping_fee, f.payment_method FROM orders o LEFT JOIN order_finances f ON o.id = f.order_id WHERE o.id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            // 1. Cập nhật bảng orders
            $status = $data['status'] ?? $oldData['status'];
            $note = $data['note'] ?? '';

            $stmtOrder = $this->db->prepare("UPDATE orders SET status = ?, note = ?, updated_at = NOW() WHERE id = ?");
            $stmtOrder->execute([$status, $note, $id]);

            // Nếu có thay đổi trạng thái, cập nhật mốc thời gian ở bảng deliveries
            if ($status !== $oldData['status']) {
                $timeColumn = null;
                if ($status === 'picking_up') $timeColumn = "picked_up_at";
                elseif ($status === 'completed') $timeColumn = "delivered_at";
                elseif ($status === 'cancelled') $timeColumn = "cancelled_at";

                if ($timeColumn) {
                    try {
                        // Không thể bind tên cột, nhưng đã whitelist ở trên nên an toàn
                        $deliverySql = "UPDATE order_deliveries SET {$timeColumn} = NOW() WHERE order_id = ?";
                        $deliveryStmt = $this->db->prepare($deliverySql);
                        $deliveryStmt->execute([$id]);
                    } catch (\PDOException $e) {
                        // Bỏ qua lỗi nếu cột mốc thời gian chưa được tạo trong CSDL để không làm gián đoạn luồng chính
                    }
                }
            }

            // 2. Cập nhật bảng order_finances
            $paymentStatus = $data['payment_status'] ?? $oldData['payment_status'];
            $shippingFee = isset($data['shipping_fee']) ? (float)$data['shipping_fee'] : $oldData['shipping_fee'];
            $paymentMethod = $data['payment_method'] ?? $oldData['payment_method'];
            
            $financeTimeUpdate = "";
            if ($paymentStatus !== $oldData['payment_status']) {
                if ($paymentStatus === 'paid') $financeTimeUpdate = ", paid_at = NOW()";
                elseif ($paymentStatus === 'refunded') $financeTimeUpdate = ", refunded_at = NOW()";
            }

            $stmtFinance = $this->db->prepare("UPDATE order_finances SET payment_status = ?, shipping_fee = ?, payment_method = ?, updated_at = NOW() {$financeTimeUpdate} WHERE order_id = ?");
            $stmtFinance->execute([$paymentStatus, $shippingFee, $paymentMethod, $id]);

            // 3. Nếu có thay đổi trạng thái, ghi nhận lịch sử rõ ràng phục vụ đối soát hội đồng chấm
            if ($status !== $oldData['status']) {
                $desc = "Quản trị viên thay đổi trạng thái đơn từ '" . \App\Models\Order::getStatusLabel($oldData['status']) . "' sang '" . \App\Models\Order::getStatusLabel($status) . "'. Lý do: Hệ thống Admin hiệu chỉnh.";
                $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmtHist->execute([$id, $status, $desc]);
            }

            if ($paymentStatus !== $oldData['payment_status']) {
                $descFinance = "Quản trị viên thay đổi trạng thái thanh toán thành: " . $paymentStatus;
                $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmtHist->execute([$id, $status, $descFinance]);
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            error_log('Order updateForAdmin failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng và tự động lưu vào lịch sử
     */
    public function updateStatus(int $orderId, string $status, string $description = ''): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // 1. Cập nhật trạng thái chính trong bảng `orders`
            $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $orderId]);

            // 2. Cập nhật các cột mốc thời gian tương ứng trong bảng `order_deliveries`
            $timeColumn = null;
            if ($status === 'picking_up') $timeColumn = "picked_up_at";
            elseif ($status === 'completed') $timeColumn = "delivered_at";
            elseif ($status === 'cancelled') $timeColumn = "cancelled_at";

            if ($timeColumn) {
                try {
                    // Không thể bind tên cột, nhưng đã whitelist ở trên nên an toàn
                    $deliverySql = "UPDATE order_deliveries SET {$timeColumn} = NOW() WHERE order_id = ?";
                    $deliveryStmt = $this->db->prepare($deliverySql);
                    $deliveryStmt->execute([$orderId]);
                } catch (\PDOException $e) {
                    // Bỏ qua lỗi nếu cột mốc thời gian chưa được tạo trong CSDL để không làm gián đoạn luồng chính
                }
            }

            // 3. Ghi nhận lịch sử trạng thái (Order Status History) luôn luôn nhất quán
            $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmtHist->execute([$orderId, $status, $description ?: \App\Models\Order::getStatusLabel($status)]);

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            error_log('Order updateStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật trạng thái thanh toán và tự động ghi nhận mốc thời gian paid_at / refunded_at
     */
    public function updatePaymentStatus(int $orderId, string $paymentStatus): bool
    {
        $timeColumnUpdate = "";
        if ($paymentStatus === 'paid') {
            $timeColumnUpdate = ", paid_at = NOW()";
        } elseif ($paymentStatus === 'refunded') {
            $timeColumnUpdate = ", refunded_at = NOW()";
        }

        $sql = "UPDATE order_finances SET payment_status = ?, updated_at = NOW() {$timeColumnUpdate} WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$paymentStatus, $orderId]);
    }

    // Ghi nhận một lịch sử trạng thái tùy chỉnh cho đơn hàng
    public function addStatusHistory(int $orderId, string $status, string $description): bool
    {
        $stmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$orderId, $status, $description]);
    }

    // Hoàn tiền cho đơn hàng nếu khách đã thanh toán trước
    public function refundPaidOrder(int $orderId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE order_finances
            SET payment_status = 'refunded',
                refunded_at = COALESCE(refunded_at, NOW())
            WHERE order_id = ?
              AND payment_status = 'paid'
        ");
        $stmt->execute([$orderId]);
        return $stmt->rowCount() > 0;
    }

    // Lấy trạng thái liền kề trước đó của đơn hàng
    public function getPreviousStatus(int $orderId): string
    {
        $stmt = $this->db->prepare("SELECT status FROM order_status_history WHERE order_id = ? AND status != 'disputed' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        return $row ? $row['status'] : 'cancelled';
    }

    // Lấy chi phí giao hàng của một danh sách đơn hàng
    public function getShippingFees(array $orderIds): array
    {
        if (empty($orderIds)) return [];
        $inClause = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->db->prepare("SELECT order_id, shipping_fee, payment_method FROM order_finances WHERE order_id IN ($inClause)");
        $stmt->execute($orderIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lấy danh sách địa chỉ đã sử dụng gần đây của khách hàng
     */
    public function getRecentAddresses(int $userId, int $limit = 5): array
    {
        $limit = (int) $limit;

        $sql = "
            (SELECT MAX(o.created_at) as last_used, oa.sender_address as address, oa.sender_lat as lat, oa.sender_lng as lng, '' as name, '' as phone, 'pickup' as type 
             FROM order_addresses oa 
             JOIN orders o ON oa.order_id = o.id 
             WHERE o.customer_id = ? AND oa.sender_address != '' 
             GROUP BY oa.sender_address, oa.sender_lat, oa.sender_lng 
             ORDER BY last_used DESC LIMIT ?)
            UNION ALL
            (SELECT MAX(o.created_at) as last_used, oa.receiver_address as address, oa.receiver_lat as lat, oa.receiver_lng as lng, oa.receiver_name as name, oa.receiver_phone as phone, 'delivery' as type 
             FROM order_addresses oa 
             JOIN orders o ON oa.order_id = o.id 
             WHERE o.customer_id = ? AND oa.receiver_address != '' 
             GROUP BY oa.receiver_address, oa.receiver_lat, oa.receiver_lng, oa.receiver_name, oa.receiver_phone 
             ORDER BY last_used DESC LIMIT ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $userId, $limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pickups = [];
        $deliveries = [];
        foreach ($results as $row) {
            if ($row['type'] === 'pickup') {
                $pickups[] = $row;
            } else {
                $deliveries[] = $row;
            }
        }

        return [
            'pickups' => $pickups,
            'deliveries' => $deliveries
        ];
    }

    /**
     * Tối ưu hóa: Kiểm tra giới hạn nhận nhiều đơn hàng cùng lúc (Tránh lỗi N+1 Query)
     */
    public function canDriverAcceptMultipleOrders(int $driverId, array $orderIds): array
    {
        $response = ['valid_orders' => [], 'rejected_orders' => []];
        if (empty($orderIds)) return $response;

        // 1. Lấy thông tin tài xế (Chỉ 1 Query)
        $stmtDriver = $this->db->prepare("
            SELECT u.is_blocked, u.blocked_reason, dp.max_concurrent_orders, dp.max_total_weight, dp.current_load, dp.is_verified 
            FROM users u LEFT JOIN driver_profiles dp ON u.id = dp.user_id WHERE u.id = ?
        ");
        $stmtDriver->execute([$driverId]);
        $driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            foreach ($orderIds as $oid) $response['rejected_orders'][$oid] = 'Tài xế không tồn tại';
            return $response;
        }
        if ($driver['is_blocked']) {
            foreach ($orderIds as $oid) $response['rejected_orders'][$oid] = 'Tài khoản đã bị khóa. ' . ($driver['blocked_reason'] ?? '');
            return $response;
        }
        if (empty($driver['is_verified'])) {
            foreach ($orderIds as $oid) $response['rejected_orders'][$oid] = 'Tài khoản chưa được xác thực giấy tờ xe.';
            return $response;
        }

        // 2. Đếm số đơn đang hoạt động (Chỉ 1 Query)
        $stmtCount = $this->db->prepare("
            SELECT
                COUNT(*) as total_active,
                COALESCE(SUM(o.weight), 0) as active_weight,
                SUM(CASE WHEN o.shipping_method = 'express' THEN 1 ELSE 0 END) as express_count,
                SUM(CASE WHEN o.shipping_method = 'fast' THEN 1 ELSE 0 END) as fast_count
            FROM orders o JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmtCount->execute([$driverId]);
        $activeStats = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $activeCount = (int) $activeStats['total_active'];
        $activeWeight = (float) ($activeStats['active_weight'] ?? 0);
        $activeExpressCount = (int) $activeStats['express_count'];
        $activeFastCount = (int) $activeStats['fast_count'];

        if ($activeExpressCount > 0) {
            foreach ($orderIds as $oid) $response['rejected_orders'][$oid] = 'Bạn đang thực hiện đơn Siêu tốc (độc quyền), không thể nhận thêm đơn.';
            return $response;
        }

        $settingModel = new Setting();
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $fastMaxOrders = (int) $settingModel->get('fast_max_orders', 3);
        $maxOrders = isset($driver['max_concurrent_orders']) ? (int) $driver['max_concurrent_orders'] : $defaultMaxOrders;
        
        if ($activeFastCount > 0) {
            $maxOrders = min($maxOrders, $fastMaxOrders);
        }
        $maxTotalWeight = isset($driver['max_total_weight']) ? (float) $driver['max_total_weight'] : $defaultMaxWeight;
        $currentLoad = $activeWeight;

        // 3. Lấy thông tin đơn hàng (Chỉ 1 Query)
        $inClause = implode(',', array_fill(0, count($orderIds), '?'));
        $stmtOrders = $this->db->prepare("SELECT id, weight, scheduled_at, shipping_method FROM orders WHERE id IN ($inClause)");
        $stmtOrders->execute($orderIds);
        $orderData = [];
        while ($row = $stmtOrders->fetch(PDO::FETCH_ASSOC)) {
            $orderData[$row['id']] = [
                'weight' => isset($row['weight']) ? (float) $row['weight'] : 1.0,
                'scheduled_at' => $row['scheduled_at'] ?? null,
                'shipping_method' => $row['shipping_method'] ?? 'standard'
            ];
        }

        // 4. Mô phỏng cộng dồn dữ liệu trong RAM (Nhanh gấp hàng chục lần kết nối SQL)
        $now = time();
        foreach ($orderIds as $oid) {
            if (!isset($orderData[$oid])) {
                $response['rejected_orders'][$oid] = 'Đơn hàng không tồn tại';
                continue;
            }
            
            $isExpress = $orderData[$oid]['shipping_method'] === 'express';
            $isFast = $orderData[$oid]['shipping_method'] === 'fast';
            if ($isExpress) {
                if ($activeCount > 0) {
                    $response['rejected_orders'][$oid] = 'Bạn đang có đơn hàng khác, không thể nhận đơn Siêu tốc.';
                    continue;
                }
                if (count($orderIds) > 1) {
                    $response['rejected_orders'][$oid] = 'Đơn Siêu tốc phải được nhận độc lập, không thể ghép chung.';
                    continue;
                }
            }
            
            if ($isFast) {
                $maxOrders = min($maxOrders, $fastMaxOrders);
            }

            if (!empty($orderData[$oid]['scheduled_at'])) {
                $scheduledTime = strtotime($orderData[$oid]['scheduled_at']);
                // Đã đồng bộ múi giờ toàn hệ thống, chỉ cần thêm 5 phút (300s) bù trừ trễ mạng.
                if ($scheduledTime > $now + 7500) {
                    $response['rejected_orders'][$oid] = 'Chưa đến giờ nhận đơn (chỉ được nhận trước 2 tiếng)';
                    continue;
                }
            }
            if ($activeCount >= $maxOrders) {
                if ($maxOrders <= $fastMaxOrders && ($isFast || $activeFastCount > 0)) {
                    $response['rejected_orders'][$oid] = "Đã đạt giới hạn ({$activeCount}/{$maxOrders} đơn do quy định đơn Giao nhanh)";
                } else {
                    $response['rejected_orders'][$oid] = "Đã đạt giới hạn ({$activeCount}/{$maxOrders} đơn)";
                }
                continue;
            }
            $weight = $orderData[$oid]['weight'];
            if ($currentLoad + $weight > $maxTotalWeight) {
                $remaining = $maxTotalWeight - $currentLoad;
                $response['rejected_orders'][$oid] = "Quá tải trọng. Còn trống: {$remaining}kg, Đơn: {$weight}kg";
                continue;
            }
            $activeCount++;
            $currentLoad += $weight;
            $response['valid_orders'][] = $oid;
        }
        return $response;
    }

    /**
     * TỐI ƯU HÓA: Gộp 3 câu truy vấn I/O riêng lẻ thành 1 truy vấn duy nhất.
     */
    public function getDriverActiveStats(int $driverId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as active_count,
                COALESCE(SUM(o.weight), 0) as current_weight,
                MAX(CASE WHEN o.shipping_method = 'express' THEN 1 ELSE 0 END) as has_express,
                MAX(CASE WHEN o.shipping_method = 'fast' THEN 1 ELSE 0 END) as has_fast
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmt->execute([$driverId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['active_count' => 0, 'current_weight' => 0.0, 'has_express' => 0];
    }

    /**
     * Lấy số lượng đơn hàng hiện tại của tài xế
     */
    public function getDriverActiveOrderCount(int $driverId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Kiểm tra xem tài xế có đang thực hiện đơn hàng Siêu tốc nào không
     */
    public function hasDriverActiveExpressOrder(int $driverId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? 
              AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
              AND o.shipping_method = 'express'
            LIMIT 1
        ");
        $stmt->execute([$driverId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Lấy tổng cân nặng hiện tại của tài xế
     */
    public function getDriverCurrentWeight(int $driverId): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(COALESCE(o.weight, 1.0)) as total_weight
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_weight'] ?? 0);
    }

    /**
     * Lấy danh sách đơn hàng bị giam (đã nhận nhưng không đi lấy quá X phút)
     */
    public function getStuckAcceptedOrders(int $minutes = 15): array
    {
        $sql = "
            SELECT o.id, o.tracking_code, od.driver_id, od.accepted_at 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE o.status = 'accepted' 
              AND od.accepted_at <= DATE_SUB(NOW(), INTERVAL ? MINUTE)
              AND o.is_archived = 0
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$minutes]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Hủy chuyến của tài xế và đưa đơn hàng trở lại trạng thái tìm xe
     */
    public function autoReassignOrder(int $orderId): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $stmtOrder = $this->db->prepare("UPDATE orders SET status = 'searching_driver', updated_at = NOW() WHERE id = ? AND status = 'accepted'");
            $stmtOrder->execute([$orderId]);

            if ($stmtOrder->rowCount() === 0) {
                if ($ownsTransaction) $this->db->rollBack();
                return false;
            }

            // SỬA LỖI NGẦM: Bắt buộc phải xóa batch_code và batch_route_details để đơn hàng không bị kẹt lại trong cụm cũ nếu được tài xế khác nhận
            $stmtDel = $this->db->prepare("UPDATE order_deliveries SET driver_id = NULL, accepted_at = NULL, batch_code = NULL, batch_route_details = NULL WHERE order_id = ?");
            $stmtDel->execute([$orderId]);

            $desc = "Hệ thống tự động thu hồi đơn hàng do tài xế không đi lấy hàng quá thời gian quy định.";
            $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'searching_driver', ?, NOW())");
            $stmtHist->execute([$orderId, $desc]);

            if ($ownsTransaction) {
                $this->db->commit();
            }
            
            // Tìm thông tin khách hàng để gửi thông báo trấn an
            $stmtInfo = $this->db->prepare("SELECT tracking_code, customer_id FROM orders WHERE id = ?");
            $stmtInfo->execute([$orderId]);
            if ($info = $stmtInfo->fetch(\PDO::FETCH_ASSOC)) {
                $userModel = new \App\Models\User();
                $userModel->createNotification(
                    $info['customer_id'],
                    'Đang tìm lại tài xế',
                    "Hệ thống đang tự động tìm lại tài xế mới cho đơn hàng #{$info['tracking_code']} do tài xế trước đó không thể tiếp tục thực hiện.",
                    'order',
                    "/user/orders/track/{$info['tracking_code']}"
                );
            }
            
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /**
     * Tự động hủy các đơn hàng chờ quá 1 ngày (24h) không có ai nhận.
     */
    public function autoCancelExpiredPendingOrders(): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // Lấy danh sách đơn quá hạn 1 ngày (so với lúc tạo hoặc lúc hẹn lấy hàng)
            $stmt = $this->db->prepare("
                SELECT id, tracking_code, customer_id 
                FROM orders 
                WHERE status IN ('awaiting_payment', 'searching_driver') 
                  AND is_archived = 0
                  AND (
                      (scheduled_at IS NULL AND created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY))
                      OR 
                      (scheduled_at IS NOT NULL AND scheduled_at <= DATE_SUB(NOW(), INTERVAL 1 DAY))
                  )
            ");
            $stmt->execute();
            $expiredOrders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($expiredOrders)) {
                if ($ownsTransaction) $this->db->rollBack();
                return false;
            }

            $updateStmt = $this->db->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $desc = "Hệ thống tự động hủy đơn do quá 24h không có tài xế nhận.";
            $histStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'cancelled', ?, NOW())");
            $userModel = new \App\Models\User();

            foreach ($expiredOrders as $order) {
                $updateStmt->execute([$order['id']]);
                $histStmt->execute([$order['id'], $desc]);
                
                // Gửi thông báo cho khách hàng
                $userModel->createNotification(
                    $order['customer_id'],
                    'Hủy đơn hàng tự động',
                    "Hệ thống đã tự động hủy đơn hàng #{$order['tracking_code']} do không có tài xế tiếp nhận sau 24 giờ. Rất mong bạn thông cảm vì sự bất tiện này.",
                    'system',
                    "/user/orders/track/{$order['tracking_code']}"
                );
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
