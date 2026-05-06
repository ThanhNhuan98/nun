<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    protected PDO $db;

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
        $method = $method ?: 'standard';
        return self::SHIPPING_METHOD_LABELS[$method] ?? 'Giao tiêu chuẩn';
    }

    /**
     * Lấy màu sắc phương thức giao hàng
     */
    public static function getShippingMethodColor(?string $method): string
    {
        $method = $method ?: 'standard';
        return self::SHIPPING_METHOD_COLORS[$method] ?? 'var(--text-main)';
    }

    public function create(array $data)
    {
        $trackingCode = 'NUN' . time() . random_int(10, 99);
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $initialStatus = $paymentMethod === 'transfer' ? 'awaiting_payment' : 'searching_driver';
        
        $this->db->beginTransaction();
        try {
            // 1. Lưu bảng orders
            $scheduledAt = !empty($data['scheduled_at']) ? date('Y-m-d H:i:s', strtotime($data['scheduled_at'])) : null;
            $weight = isset($data['weight']) ? (float) $data['weight'] : 1.0;
            $shippingMethod = $data['shipping_method'] ?? 'standard';
            $stmt = $this->db->prepare("INSERT INTO orders (customer_id, tracking_code, status, weight, shipping_method, note, scheduled_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $data['customer_id'],
                $trackingCode,
                $initialStatus,
                $weight,
                $shippingMethod,
                $data['description'] ?? ($data['note'] ?? ''),
                $scheduledAt
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

            $this->db->commit();
            return $trackingCode;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Bạn có thể thêm các hàm như getById($id), getByUserId($userId)... ở đây

    public function findByIdAndUserId(int $orderId, int $userId)
    {
        $sql = "
            SELECT 
                o.*, 
                del.driver_id,
                u.name AS driver_name, 
                u.phone AS driver_phone, 
                u.avatar AS driver_avatar,
                dp.license_plate AS driver_license_plate
            FROM orders o 
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users u ON u.id = del.driver_id 
            LEFT JOIN driver_profiles dp ON dp.user_id = del.driver_id
            WHERE o.id = ? AND o.customer_id = ? AND o.is_archived = 0 
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        return $stmt->fetch();
    }

    public function getPendingForDriver(int $driverId): array
    {
        // Lấy thêm thông tin địa chỉ và phí ship để AI tính toán và hiển thị
        $sql = "
            SELECT 
                o.*, 
                oa.sender_lat, oa.sender_lng, oa.sender_address AS pickup_address, oa.receiver_address AS delivery_address,
                fin.shipping_fee
            FROM orders o
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            WHERE o.status IN ('pending', 'searching_driver') AND o.is_archived = 0 
              AND (o.scheduled_at IS NULL OR o.scheduled_at <= DATE_ADD(NOW(), INTERVAL 120 MINUTE))
            ORDER BY o.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * TỐI ƯU HÓA: Chỉ đếm số lượng đơn đang chờ, dùng cho API Polling
     * Tránh việc dùng SELECT * và JOIN các bảng không cần thiết gây quá tải RAM.
     */
    public function countPendingOrders(): int
    {
        $sql = "
            SELECT COUNT(id) 
            FROM orders 
            WHERE status IN ('pending', 'searching_driver') AND is_archived = 0 
              AND (scheduled_at IS NULL OR scheduled_at <= DATE_ADD(NOW(), INTERVAL 120 MINUTE))
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
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
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, o.scheduled_at,
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
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, od.accepted_at, o.weight, o.scheduled_at,
                oa.sender_address AS pickup_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_address AS delivery_address, oa.receiver_lat, oa.receiver_lng,
                fin.shipping_fee
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            LEFT JOIN order_addresses oa ON o.id = oa.order_id
            LEFT JOIN order_finances fin ON o.id = fin.order_id
            WHERE od.driver_id = ? 
              AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping')
              AND o.is_archived = 0
            ORDER BY 
                CASE o.status
                    WHEN 'accepted' THEN 1
                    WHEN 'picking_up' THEN 2
                    WHEN 'in_transit' THEN 3
                    WHEN 'shipping' THEN 4
                    ELSE 5
                END ASC,
                od.accepted_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

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
                fin.shipping_fee, fin.payment_method, fin.payment_status,
                oa.sender_name, oa.sender_phone, oa.sender_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_phone, oa.receiver_address, oa.receiver_lat, oa.receiver_lng,
                dp.current_lat AS driver_lat, dp.current_lng AS driver_lng,
                u.no_show_count AS customer_no_show_count,
                dp.license_plate AS driver_license_plate
            FROM orders o
            JOIN order_deliveries del ON o.id = del.order_id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN driver_profiles dp ON del.driver_id = dp.user_id
            LEFT JOIN users u ON o.customer_id = u.id
            WHERE o.id = ? AND del.driver_id = ? AND o.is_archived = 0
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $driverId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function assignDriver(int $orderId, int $driverId): bool
    {
        $this->db->beginTransaction();
        try {
            // Cập nhật trạng thái đơn hàng sang accepted
            $sql = "UPDATE orders SET status = 'accepted', updated_at = NOW() WHERE id = :id AND status IN ('pending', 'searching_driver')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $orderId]);

            if ($stmt->rowCount() > 0) {
                // Cập nhật driver_id vào bảng order_deliveries
                $sqlDelivery = "UPDATE order_deliveries SET driver_id = :driver_id, accepted_at = NOW() WHERE order_id = :order_id";
                $stmtDelivery = $this->db->prepare($sqlDelivery);
                $stmtDelivery->execute(['driver_id' => $driverId, 'order_id' => $orderId]);
                
                // Thêm lịch sử trạng thái
                $sqlHistory = "INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', NOW())";
                $this->db->prepare($sqlHistory)->execute([$orderId]);
                
                $this->db->commit();
                return true;
            }
            $this->db->rollBack();
            return false;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Gán nhiều đơn hàng cho tài xế cùng lúc (Dùng cho ghép chuyến)
     */
    public function assignMultipleOrdersToDriver(array $orderIds, int $driverId): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $assignedIds = [];
        $now = date('Y-m-d H:i:s'); // Dùng chung 1 mốc thời gian duy nhất cho cả chuyến
        
        $this->db->beginTransaction();
        try {
            $stmtOrder = $this->db->prepare("UPDATE orders SET status = 'accepted', updated_at = ? WHERE id = ? AND status IN ('pending', 'searching_driver')");
            $stmtDelivery = $this->db->prepare("UPDATE order_deliveries SET driver_id = ?, accepted_at = ? WHERE order_id = ?");
            $stmtHistory = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', ?)");
            
            foreach ($orderIds as $orderId) {
                $stmtOrder->execute([$now, $orderId]);
                
                // Chỉ khi đơn hàng đó thực sự được cập nhật thành 'accepted' (Chưa bị người khác nhận)
                if ($stmtOrder->rowCount() > 0) {
                    $stmtDelivery->execute([$driverId, $now, $orderId]);
                    $stmtHistory->execute([$orderId, $now]);
                    $assignedIds[] = $orderId;
                } else {
                    // Logic ALL OR NOTHING (Tất cả hoặc không có gì)
                    // Nếu 1 đơn trong chuyến ghép đã bị người khác nhận trước, hủy toàn bộ chuyến đó.
                    $this->db->rollBack();
                    return [];
                }
            }
            
            $this->db->commit();
            return $assignedIds;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [];
        }
    }

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
                o.id, o.tracking_code, o.status, o.created_at, o.scheduled_at,
                fin.shipping_fee,
                oa.receiver_address,
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

    public function findByTrackingCodeForUser(string $trackingCode, int $userId)
    {
        $sql = "
            SELECT 
                o.*, 
                oa.*, 
                fin.*, 
                del.*,
                u.name AS driver_name, 
                u.phone AS driver_phone, 
                u.avatar AS driver_avatar, 
                dp.current_lat AS driver_lat, 
                dp.current_lng AS driver_lng,
                dp.license_plate AS driver_license_plate
            FROM orders o 
            LEFT JOIN order_addresses oa ON oa.order_id = o.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            LEFT JOIN order_deliveries del ON del.order_id = o.id
            LEFT JOIN users u ON u.id = del.driver_id 
            LEFT JOIN driver_profiles dp ON dp.user_id = del.driver_id 
            WHERE o.tracking_code = ? AND o.customer_id = ? AND o.is_archived = 0 
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trackingCode, $userId]);
        return $stmt->fetch();
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
                fin.shipping_fee, fin.payment_method, fin.payment_status,
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
            $where .= " AND (o.tracking_code LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR oa.receiver_phone LIKE ?)";
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }

        return [$where, $params];
    }

    /**
     * Lấy danh sách đơn hàng cho Admin kèm bộ lọc trạng thái và tìm kiếm
     */
    public function countAllForAdmin(string $statusFilter = '', string $search = ''): int
    {
        [$where, $params] = $this->buildAdminFilters($statusFilter, $search);

        $sql = "SELECT COUNT(o.id) FROM orders o LEFT JOIN users u ON o.customer_id = u.id LEFT JOIN order_addresses oa ON oa.order_id = o.id $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllForAdmin(string $statusFilter = '', string $search = '', int $limit = 15, int $offset = 0): array
    {
        [$where, $params] = $this->buildAdminFilters($statusFilter, $search);

        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.scheduled_at,
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
                fin.shipping_fee, fin.payment_method, fin.payment_status,
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
    public function updateForAdmin(int $orderId, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            // Cập nhật bảng orders
            // Sử dụng COALESCE để giữ nguyên trạng thái cũ nếu form không gửi lên
            $stmt = $this->db->prepare("UPDATE orders SET status = COALESCE(?, status), note = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $data['status'] ?? null,
                $data['description'] ?? ($data['note'] ?? ''),
                $orderId
            ]);

            // Cập nhật bảng order_finances
            $stmtFin = $this->db->prepare("UPDATE order_finances SET shipping_fee = ?, payment_method = ?, payment_status = ? WHERE order_id = ?");
            $stmtFin->execute([
                isset($data['shipping_fee']) ? (float)$data['shipping_fee'] : 0,
                $data['payment_method'] ?? 'cash',
                $data['payment_status'] ?? 'pending',
                $orderId
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng và tự động lưu vào lịch sử
     */
    public function updateStatus(int $orderId, string $status, string $description = ''): bool
    {
        // Transaction được quản lý ở Controller để gộp chung với logic hoàn tiền
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);

        $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmtHist->execute([$orderId, $status, $description]);

        $deliveryTimeColumns = [
            'in_transit' => 'picked_up_at',
            'shipping' => 'picked_up_at',
            'completed' => 'delivered_at',
            'cancelled' => 'cancelled_at',
        ];
        if (isset($deliveryTimeColumns[$status])) {
            $column = $deliveryTimeColumns[$status];
            $stmtDelivery = $this->db->prepare("UPDATE order_deliveries SET {$column} = COALESCE({$column}, NOW()) WHERE order_id = ?");
            $stmtDelivery->execute([$orderId]);
        }

        // Tự động chuyển trạng thái thành "Đã thanh toán" nếu giao thành công bằng tiền mặt
        if ($status === 'completed') {
            $stmtFinance = $this->db->prepare("UPDATE order_finances SET payment_status = 'paid', paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ? AND payment_method = 'cash'");
            $stmtFinance->execute([$orderId]);
        }

        return true;
    }

    public function updatePaymentStatus(int $orderId, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE order_finances SET payment_status = ? WHERE order_id = ?");
        return $stmt->execute([$status, $orderId]);
    }

    public function getPreviousStatus(int $orderId): string
    {
        $stmt = $this->db->prepare("SELECT status FROM order_status_history WHERE order_id = ? AND status != 'disputed' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        return $row ? $row['status'] : 'cancelled';
    }

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
     * Kiểm tra tài xế có thể nhận đơn hàng hay không
     * Kiểm tra: số lượng đơn hiện tại, tổng cân nặng, tài xế bị khóa
     */
    public function canDriverAcceptOrder(int $driverId, int $orderId): array
    {
        $response = ['can_accept' => false, 'message' => ''];

        // 1. Kiểm tra tài xế có bị khóa không
        $stmtUser = $this->db->prepare("SELECT is_blocked, blocked_reason FROM users WHERE id = ?");
        $stmtUser->execute([$driverId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['can_accept' => false, 'message' => 'Tài xế không tồn tại'];
        }

        if ($user['is_blocked']) {
            return ['can_accept' => false, 'message' => 'Tài khoản của bạn đã bị khóa. ' . ($user['blocked_reason'] ?? 'Liên hệ quản trị viên.')];
        }

        // 2. Kiểm tra số lượng đơn hiện tại
        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) as active_count 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping')
        ");
        $stmtCount->execute([$driverId]);
        $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $activeOrderCount = $countResult['active_count'] ?? 0;

        // Kiểm tra giới hạn từ driver_profiles
        $stmtDriver = $this->db->prepare("
            SELECT max_concurrent_orders, max_total_weight, current_load, is_verified 
            FROM driver_profiles 
            WHERE user_id = ?
        ");
        $stmtDriver->execute([$driverId]);
        $driverProfile = $stmtDriver->fetch(PDO::FETCH_ASSOC);

        if (!$driverProfile) {
            return ['can_accept' => false, 'message' => 'Hồ sơ tài xế không tồn tại'];
        }
        
        if (empty($driverProfile['is_verified'])) {
            return ['can_accept' => false, 'message' => 'Tài khoản chưa được xác thực giấy tờ xe. Vui lòng chờ Admin phê duyệt.'];
        }

        $settingModel = new Setting();
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $maxOrders = $driverProfile['max_concurrent_orders'] ?? $defaultMaxOrders;
        if ($activeOrderCount >= $maxOrders) {
            return [
                'can_accept' => false, 
                'message' => "Bạn đã đạt giới hạn đơn hàng ({$activeOrderCount}/{$maxOrders}). Hoàn thành một số đơn trước đó."
            ];
        }

        // 3. Kiểm tra đơn hàng hợp lệ
        $stmtWeight = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtWeight->execute([$orderId]);
        $order = $stmtWeight->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['can_accept' => false, 'message' => 'Đơn hàng không tồn tại'];
        }

        if (!empty($order['scheduled_at'])) {
            $scheduledTime = strtotime($order['scheduled_at']);
            // Đã đồng bộ múi giờ toàn hệ thống, chỉ cần thêm 5 phút (300s) bù trừ trễ mạng.
            if ($scheduledTime > time() + 7500) {
                return ['can_accept' => false, 'message' => 'Chưa đến giờ nhận đơn (chỉ được nhận trước 2 tiếng)'];
            }
        }

        $orderWeight = (float)($order['weight'] ?? 0);
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $maxTotalWeight = (float)($driverProfile['max_total_weight'] ?? $defaultMaxWeight);
        $currentLoad = (float)($driverProfile['current_load'] ?? 0);

        if ($currentLoad + $orderWeight > $maxTotalWeight) {
            $remaining = $maxTotalWeight - $currentLoad;
            return [
                'can_accept' => false, 
                'message' => "Cân nặng vượt quá giới hạn. Dung tích còn lại: {$remaining}kg, đơn hàng: {$orderWeight}kg"
            ];
        }

        return ['can_accept' => true, 'message' => 'Có thể nhận đơn hàng'];
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
            SELECT COUNT(*) FROM orders o JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping')
        ");
        $stmtCount->execute([$driverId]);
        $activeCount = (int) $stmtCount->fetchColumn();

        $settingModel = new Setting();
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $maxOrders = isset($driver['max_concurrent_orders']) ? (int) $driver['max_concurrent_orders'] : $defaultMaxOrders;
        $maxTotalWeight = isset($driver['max_total_weight']) ? (float) $driver['max_total_weight'] : $defaultMaxWeight;
        $currentLoad = (float) ($driver['current_load'] ?? 0);

        // 3. Lấy thông tin đơn hàng (Chỉ 1 Query)
        $inClause = implode(',', array_fill(0, count($orderIds), '?'));
        $stmtOrders = $this->db->prepare("SELECT id, weight, scheduled_at FROM orders WHERE id IN ($inClause)");
        $stmtOrders->execute($orderIds);
        $orderData = [];
        while ($row = $stmtOrders->fetch(PDO::FETCH_ASSOC)) {
            $orderData[$row['id']] = [
                'weight' => isset($row['weight']) ? (float) $row['weight'] : 1.0,
                'scheduled_at' => $row['scheduled_at'] ?? null
            ];
        }

        // 4. Mô phỏng cộng dồn dữ liệu trong RAM (Nhanh gấp hàng chục lần kết nối SQL)
        $now = time();
        foreach ($orderIds as $oid) {
            if (!isset($orderData[$oid])) {
                $response['rejected_orders'][$oid] = 'Đơn hàng không tồn tại';
                continue;
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
                $response['rejected_orders'][$oid] = "Đã đạt giới hạn ({$activeCount}/{$maxOrders} đơn)";
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
     * Lấy số lượng đơn hàng hiện tại của tài xế
     */
    public function getDriverActiveOrderCount(int $driverId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping')
        ");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
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
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping')
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
        $this->db->beginTransaction();
        try {
            $stmtOrder = $this->db->prepare("UPDATE orders SET status = 'searching_driver', updated_at = NOW() WHERE id = ? AND status = 'accepted'");
            $stmtOrder->execute([$orderId]);

            if ($stmtOrder->rowCount() > 0) {
                $stmtDel = $this->db->prepare("UPDATE order_deliveries SET driver_id = NULL, accepted_at = NULL WHERE order_id = ?");
                $stmtDel->execute([$orderId]);

                $desc = "Hệ thống tự động thu hồi đơn hàng do tài xế không đi lấy hàng quá thời gian quy định.";
                $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'searching_driver', ?, NOW())");
                $stmtHist->execute([$orderId, $desc]);

                $this->db->commit();
                return true;
            }
            $this->db->rollBack();
            return false;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Tự động hủy các đơn hàng chờ quá 1 ngày (24h) không có ai nhận.
     */
    public function autoCancelExpiredPendingOrders(): bool
    {
        $this->db->beginTransaction();
        try {
            // Lấy danh sách đơn quá hạn 1 ngày (so với lúc tạo hoặc lúc hẹn lấy hàng)
            $stmt = $this->db->prepare("
                SELECT id 
                FROM orders 
                WHERE status IN ('pending', 'searching_driver') 
                  AND is_archived = 0
                  AND (
                      (scheduled_at IS NULL AND created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY))
                      OR 
                      (scheduled_at IS NOT NULL AND scheduled_at <= DATE_SUB(NOW(), INTERVAL 1 DAY))
                  )
            ");
            $stmt->execute();
            $expiredOrders = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($expiredOrders)) {
                $this->db->rollBack();
                return false;
            }

            // Cập nhật trạng thái
            $inClause = implode(',', array_fill(0, count($expiredOrders), '?'));
            $updateStmt = $this->db->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id IN ($inClause)");
            $updateStmt->execute($expiredOrders);

            // Thêm vào lịch sử
            $desc = "Hệ thống tự động hủy đơn do quá 24h không có tài xế nhận.";
            $histStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'cancelled', ?, NOW())");
            foreach ($expiredOrders as $orderId) {
                $histStmt->execute([$orderId, $desc]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
