<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    protected PDO $db;

    public function __construct()
    {
        // **nun_ai**: Khởi tạo Model, kết nối cơ sở dữ liệu MySQL thông qua lớp Database (Singleton Pattern).
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
        // **nun_ai**: Hàm tiện ích tĩnh chuyển đổi mã trạng thái đơn hàng (tiếng Anh) sang nhãn hiển thị tiếng Việt.
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Lấy màu sắc tương ứng với từng trạng thái
     */
    public static function getStatusColor(string $status): string
    {
        // **nun_ai**: Hàm tiện ích tĩnh lấy mã màu hex/CSS tương ứng với từng trạng thái đơn hàng để phục vụ render UI.
        return self::STATUS_COLORS[$status] ?? '#64748b'; // Xám (mặc định)
    }

    /**
     * Chuyển đổi mã phương thức giao hàng sang tiếng Việt
     */
    public static function getShippingMethodLabel(?string $method): string
    {
        // **nun_ai**: Chuyển đổi mã phương thức giao hàng (standard, fast, express) sang tên tiếng Việt.
        return self::SHIPPING_METHOD_LABELS[$method ?: 'standard'] ?? 'Giao tiêu chuẩn';
    }

    /**
     * Lấy màu sắc phương thức giao hàng
     */
    public static function getShippingMethodColor(?string $method): string
    {
        // **nun_ai**: Lấy biến màu CSS cho phương thức giao hàng (dùng để làm nổi bật thẻ Đơn siêu tốc/Giao nhanh).
        return self::SHIPPING_METHOD_COLORS[$method ?: 'standard'] ?? 'var(--text-main)';
    }

    public function create(array $data)
    {
        // **nun_ai**: Tạo mới một đơn hàng hoàn chỉnh. Sử dụng Database Transaction để phân bổ và ghi dữ liệu đồng thời vào 4 bảng (orders, order_addresses, order_finances, order_status_history), đảm bảo tính toàn vẹn (ACID).
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
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function findByIdAndUserId(int $orderId, int $userId)
    {
        // **nun_ai**: Lấy chi tiết một đơn hàng dựa trên ID đơn hàng và ID của khách hàng tạo đơn. Kết hợp (LEFT JOIN) lấy thông tin tài xế nếu đã có người tiếp nhận.
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
        // **nun_ai**: Truy vấn danh sách các đơn hàng đang ở trạng thái chờ (pending/searching_driver) trong vòng 2 tiếng gần nhất. Dữ liệu này làm đầu vào cho module Trí tuệ nhân tạo quét và gom cụm (AI Batching).
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
        // **nun_ai**: Tối ưu hóa việc đếm số lượng đơn hàng chờ để phục vụ cho kỹ thuật AJAX Polling của chức năng Radar, tránh việc tải toàn bộ dữ liệu bảng vào RAM của PHP gây nghẽn cổ chai.
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
        // **nun_ai**: Lấy danh sách lịch sử các chuyến đi mà tài xế đã thực hiện. Có hỗ trợ phân trang và lọc theo một khoảng thời gian nhất định.
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
        // **nun_ai**: Lấy danh sách các đơn hàng ĐANG CHẠY (chưa hoàn thành) của một tài xế. Sắp xếp theo một trình tự trạng thái (CASE WHEN o.status...) để tài xế biết thứ tự điểm nào cần thao tác đi lấy/giao trước.
        $sql = "
            SELECT 
                o.id, o.tracking_code, o.status, o.created_at, o.updated_at, od.accepted_at, o.weight, o.scheduled_at, o.shipping_method,
                oa.sender_name, oa.sender_address AS pickup_address, oa.sender_lat, oa.sender_lng,
                oa.receiver_name, oa.receiver_address AS delivery_address, oa.receiver_lat, oa.receiver_lng,
                u.no_show_count AS customer_no_show_count,
                fin.shipping_fee
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            LEFT JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_addresses oa ON o.id = oa.order_id
            LEFT JOIN order_finances fin ON o.id = fin.order_id
            WHERE od.driver_id = ? 
              AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
              AND o.is_archived = 0
            ORDER BY 
                CASE o.status
                    WHEN 'accepted' THEN 1
                    WHEN 'picking_up' THEN 2
                    WHEN 'in_transit' THEN 3
                    WHEN 'shipping' THEN 4
                    WHEN 'returning' THEN 5
                    ELSE 6
                END ASC,
                od.accepted_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countDriverHistory(int $driverId, string $startDate = '', string $endDate = ''): int
    {
        // **nun_ai**: Đếm tổng số đơn hàng trong lịch sử của tài xế để tính toán số trang trong chức năng phân trang.
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
        // **nun_ai**: Lấy chi tiết của một đơn hàng dành riêng cho màn hình theo dõi của tài xế đang thực hiện chuyến đi đó (bao gồm tọa độ 2 chiều, phí ship, số điện thoại người nhận).
        $sql = "
            SELECT 
                o.*, 
                fin.shipping_fee, fin.payment_method, fin.payment_status, fin.paid_at, fin.refunded_at,
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
        // **nun_ai**: Gán một đơn lẻ cho tài xế. Sử dụng mô hình Khóa Lạc quan (Optimistic Locking: WHERE status IN 'pending') và Transaction để phòng chống triệt để lỗi Tranh chấp Dữ liệu (Race Condition) khi 2 tài xế cùng bấm nhận 1 đơn.
        $this->db->beginTransaction();
        try {
            // Cập nhật trạng thái đơn hàng sang accepted
            $sql = "UPDATE orders SET status = 'accepted', updated_at = NOW() WHERE id = :id AND status IN ('pending', 'searching_driver')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $orderId]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // Cập nhật driver_id vào bảng order_deliveries
            $sqlDelivery = "UPDATE order_deliveries SET driver_id = :driver_id, accepted_at = NOW() WHERE order_id = :order_id";
            $stmtDelivery = $this->db->prepare($sqlDelivery);
            $stmtDelivery->execute(['driver_id' => $driverId, 'order_id' => $orderId]);
            
            // Thêm lịch sử trạng thái
            $sqlHistory = "INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'accepted', 'Tài xế đã nhận đơn hàng và đang di chuyển đến điểm lấy hàng.', NOW())";
            $this->db->prepare($sqlHistory)->execute([$orderId]);
            
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Gán nhiều đơn hàng cho tài xế cùng lúc (Dùng cho ghép chuyến)
     */
    public function assignMultipleOrdersToDriver(array $orderIds, int $driverId): array
    {
        // **nun_ai**: Kỹ thuật cốt lõi trong xử lý AI Batching (Ghép chuyến). Gán hàng loạt đơn. Tuân thủ nguyên tắc All-or-Nothing: Nếu có dù chỉ 1 đơn trong cụm đã bị tài xế khác nhận mất, hệ thống sẽ Rollback (Hủy) thao tác gán của toàn bộ cụm.
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
                
                // Logic ALL OR NOTHING (Tất cả hoặc không có gì)
                // Nếu 1 đơn trong chuyến ghép đã bị người khác nhận trước, hủy toàn bộ chuyến đó.
                if ($stmtOrder->rowCount() === 0) {
                    $this->db->rollBack();
                    return [];
                }

                $stmtDelivery->execute([$driverId, $now, $orderId]);
                $stmtHistory->execute([$orderId, $now]);
                $assignedIds[] = $orderId;
            }
            
            $this->db->commit();
            return $assignedIds;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return [];
        }
    }

    public function findAllByUserId(int $userId, string $statusFilter = '', int $limit = 10, int $offset = 0): array
    {
        // **nun_ai**: Lấy danh sách đơn hàng có phân trang và bộ lọc trạng thái dành cho màn hình "Quản lý đơn hàng" của khách hàng (Customer).
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
        // **nun_ai**: Đếm số lượng đơn hàng của một khách hàng phục vụ cho phân trang.
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
        // **nun_ai**: Tra cứu tiến trình đơn hàng công khai thông qua mã vận đơn (Dành cho trang chủ / Người nhận hàng chưa có tài khoản đăng nhập).
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
        // **nun_ai**: Trích xuất riêng Vĩ độ và Kinh độ hiện tại của tài xế đang giao đơn hàng này. Dùng để gửi về giao diện (qua AJAX) vẽ icon xe máy di chuyển (Real-time Tracking).
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
        // **nun_ai**: Lấy toàn bộ timeline chuyển đổi trạng thái của đơn hàng từ lúc khởi tạo đến hiện tại (bảng order_status_history).
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
        // **nun_ai**: Hàm helper nội bộ xây dựng linh hoạt các câu lệnh WHERE và danh sách tham số (bind params) để lọc đơn hàng cho màn hình Admin.
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
        // **nun_ai**: Đếm tổng số lượng đơn hàng trên toàn hệ thống dựa theo bộ lọc của Quản trị viên.
        [$where, $params] = $this->buildAdminFilters($statusFilter, $search);

        $sql = "SELECT COUNT(o.id) FROM orders o LEFT JOIN users u ON o.customer_id = u.id LEFT JOIN order_addresses oa ON oa.order_id = o.id $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllForAdmin(string $statusFilter = '', string $search = '', int $limit = 15, int $offset = 0): array
    {
        // **nun_ai**: Lấy danh sách tổng hợp toàn bộ đơn hàng (kèm thông tin khách gửi, tài xế nhận, doanh thu) cho Bảng điều khiển (Dashboard) của Admin.
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
        // **nun_ai**: Lấy tất tần tật dữ liệu đa bảng của một đơn hàng bao gồm các thông tin nhạy cảm để Quản trị viên xem xét, xử lý và phân xử khiếu nại.
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
    public function updateForAdmin(int $orderId, array $data): bool
    {
        // **nun_ai**: Cấp quyền tối cao cho Admin can thiệp sâu vào đơn hàng (ép đổi trạng thái đơn, trạng thái thanh toán và sửa ghi chú). Đảm bảo dữ liệu các bảng liên kết (như order_finances) được đồng bộ hóa qua Transaction.
        $this->db->beginTransaction();
        try {
            $stmtCurrent = $this->db->prepare("
                SELECT o.status, o.note, fin.payment_status
                FROM orders o
                LEFT JOIN order_finances fin ON fin.order_id = o.id
                WHERE o.id = ?
                LIMIT 1
            ");
            $stmtCurrent->execute([$orderId]);
            $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                $this->db->rollBack();
                return false;
            }

            $newStatus = $data['status'] ?? $current['status'];
            $newNote = $data['description'] ?? ($data['note'] ?? ($current['note'] ?? ''));
            $newPaymentStatus = $data['payment_status'] ?? ($current['payment_status'] ?? 'pending');
            if ($newStatus === 'cancelled' && $newPaymentStatus === 'paid') {
                $newPaymentStatus = 'refunded';
            }

            $stmt = $this->db->prepare("UPDATE orders SET note = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $newNote,
                $orderId
            ]);

            $stmtFin = $this->db->prepare("
                UPDATE order_finances
                SET shipping_fee = ?,
                    payment_method = ?,
                    payment_status = ?,
                    paid_at = CASE WHEN ? = 'paid' THEN COALESCE(paid_at, NOW()) ELSE paid_at END,
                    refunded_at = CASE WHEN ? = 'refunded' THEN COALESCE(refunded_at, NOW()) ELSE refunded_at END
                WHERE order_id = ?
            ");
            $stmtFin->execute([
                isset($data['shipping_fee']) ? (float)$data['shipping_fee'] : 0,
                $data['payment_method'] ?? 'cash',
                $newPaymentStatus,
                $newPaymentStatus,
                $newPaymentStatus,
                $orderId
            ]);

            if ($newStatus !== $current['status']) {
                $this->updateStatus($orderId, $newStatus, 'Admin cập nhật trạng thái đơn hàng.');
            }
            if ($newPaymentStatus !== ($current['payment_status'] ?? 'pending')) {
                $paymentLabels = [
                    'pending' => 'Chờ thanh toán',
                    'paid' => 'Đã thanh toán',
                    'refunded' => 'Đã hoàn tiền'
                ];
                $paymentLabel = $paymentLabels[$newPaymentStatus] ?? $newPaymentStatus;
                $this->addStatusHistory($orderId, $newStatus, 'Admin cập nhật trạng thái thanh toán thành: ' . $paymentLabel);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng và tự động lưu vào lịch sử
     */
    public function updateStatus(int $orderId, string $status, string $description = ''): bool
    {
        // **nun_ai**: Cập nhật trạng thái vật lý của đơn hàng (VD: Đang lấy -> Đang giao), ghi nhận mốc thời gian hành động và tự động sinh ra log vào bảng lịch sử.
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
        // **nun_ai**: Cập nhật trạng thái thanh toán của đơn (VD: pending -> paid). Tự động lưu vết thời gian thanh toán (paid_at) hoặc hoàn tiền (refunded_at).
        $stmt = $this->db->prepare("
            UPDATE order_finances
            SET payment_status = ?,
                paid_at = CASE WHEN ? = 'paid' THEN COALESCE(paid_at, NOW()) ELSE paid_at END,
                refunded_at = CASE WHEN ? = 'refunded' THEN COALESCE(refunded_at, NOW()) ELSE refunded_at END
            WHERE order_id = ?
        ");
        return $stmt->execute([$status, $status, $status, $orderId]);
    }

    public function addStatusHistory(int $orderId, string $status, string $description): bool
    {
        // **nun_ai**: Đẩy thêm một dòng ghi chú sự kiện tùy chỉnh (Event log) vào lịch sử timeline của đơn hàng.
        $stmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$orderId, $status, $description]);
    }

    public function refundPaidOrder(int $orderId): bool
    {
        // **nun_ai**: Hoàn tiền trực tuyến (Chuyển trạng thái 'paid' thành 'refunded') trong bảng order_finances khi khách hàng hủy đơn đã thanh toán.
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

    public function getPreviousStatus(int $orderId): string
    {
        // **nun_ai**: Tìm trạng thái hợp lệ ngay trước đó của đơn hàng (Bỏ qua trạng thái Đang khiếu nại - 'disputed' nếu có). Thường dùng khi giải quyết xong khiếu nại và muốn trả đơn về bình thường.
        $stmt = $this->db->prepare("SELECT status FROM order_status_history WHERE order_id = ? AND status != 'disputed' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        return $row ? $row['status'] : 'cancelled';
    }

    public function getShippingFees(array $orderIds): array
    {
        // **nun_ai**: Lấy phí ship (Cước phí) của một danh sách các ID đơn hàng. Hỗ trợ cho việc tính toán tổng cước của cụm đơn hàng (Batch) bên Controller.
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
        // **nun_ai**: Gợi ý các địa chỉ gửi/nhận hàng mà khách hàng đã sử dụng (và giao dịch thành công) gần đây nhất. Hỗ trợ để điền nhanh (Auto-fill) vào biểu mẫu tạo đơn hàng.
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
        // **nun_ai**: Hàm phòng thủ đánh giá 1 đơn hàng lẻ. Kiểm tra xem tài xế có thỏa mãn các ràng buộc nghiệp vụ (Không bị khóa, tải trọng còn dư, chưa chạm mốc số đơn tối đa, Cửa sổ thời gian) trước khi cho phép nhận đơn.
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
            SELECT COUNT(*) as active_count, SUM(CASE WHEN o.shipping_method = 'express' THEN 1 ELSE 0 END) as express_count 
            FROM orders o
            JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmtCount->execute([$driverId]);
        $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $activeOrderCount = (int)($countResult['active_count'] ?? 0);
        $activeExpressCount = (int)($countResult['express_count'] ?? 0);

        if ($activeExpressCount > 0) {
            return ['can_accept' => false, 'message' => 'Bạn đang thực hiện đơn Siêu tốc (độc quyền), không thể nhận thêm đơn.'];
        }

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

        if (($order['shipping_method'] ?? 'standard') === 'express' && $activeOrderCount > 0) {
            return ['can_accept' => false, 'message' => 'Bạn đang có đơn hàng khác, không thể nhận đơn Siêu tốc.'];
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
        // **nun_ai**: Hàm đánh giá tối ưu hóa cho AI Batching (xử lý mảng đơn hàng). Hạn chế lỗi N+1 Query. Tính toán mô phỏng trong bộ nhớ RAM để xác định xem tài xế có thể ôm toàn bộ cụm đơn do thuật toán đề xuất hay không. Đảm bảo triệt để các quy tắc: Tải trọng giới hạn (Capacity) và Độc quyền Siêu tốc (Mutual Exclusion).
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
            SELECT COUNT(*) as total_active, SUM(CASE WHEN o.shipping_method = 'express' THEN 1 ELSE 0 END) as express_count 
            FROM orders o JOIN order_deliveries od ON o.id = od.order_id
            WHERE od.driver_id = ? AND o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning')
        ");
        $stmtCount->execute([$driverId]);
        $activeStats = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $activeCount = (int) $activeStats['total_active'];
        $activeExpressCount = (int) $activeStats['express_count'];

        if ($activeExpressCount > 0) {
            foreach ($orderIds as $oid) $response['rejected_orders'][$oid] = 'Bạn đang thực hiện đơn Siêu tốc (độc quyền), không thể nhận thêm đơn.';
            return $response;
        }

        $settingModel = new Setting();
        $defaultMaxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
        $defaultMaxWeight = (float) $settingModel->get('default_max_total_weight', 100);
        $maxOrders = isset($driver['max_concurrent_orders']) ? (int) $driver['max_concurrent_orders'] : $defaultMaxOrders;
        $maxTotalWeight = isset($driver['max_total_weight']) ? (float) $driver['max_total_weight'] : $defaultMaxWeight;
        $currentLoad = (float) ($driver['current_load'] ?? 0);

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
        // **nun_ai**: Đếm số lượng đơn hàng thực tế mà tài xế đang mang trên xe ở thời điểm hiện tại.
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
        // **nun_ai**: Kiểm tra trạng thái "Độc quyền": Đánh giá xem tài xế có đang chạy đơn Siêu tốc (express) nào hay không. Nếu có, các hàm Controller sẽ khóa tính năng Radar/Ghép chuyến của tài xế.
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
        // **nun_ai**: Lấy tổng khối lượng hàng hóa (đơn vị kg) hiện đang chở trên xe của tài xế bằng việc sum toàn bộ đơn hàng trạng thái đang chạy.
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
        // **nun_ai**: Tìm các đơn hàng đã được bấm nhận nhưng tài xế "ngâm" quá thời gian quy định (mặc định 15 phút) mà không chịu thực hiện thao tác đi lấy hàng (picking_up).
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
        // **nun_ai**: Cơ chế phạt/Auto-Reassignment: Tự động thu hồi đơn hàng từ tài xế bị giam đơn, đưa đơn trở lại trạng thái tìm xe trên Radar chung (searching_driver) và gửi Push Notification để trấn an khách hàng.
        $this->db->beginTransaction();
        try {
            $stmtOrder = $this->db->prepare("UPDATE orders SET status = 'searching_driver', updated_at = NOW() WHERE id = ? AND status = 'accepted'");
            $stmtOrder->execute([$orderId]);

            if ($stmtOrder->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            $stmtDel = $this->db->prepare("UPDATE order_deliveries SET driver_id = NULL, accepted_at = NULL WHERE order_id = ?");
            $stmtDel->execute([$orderId]);

            $desc = "Hệ thống tự động thu hồi đơn hàng do tài xế không đi lấy hàng quá thời gian quy định.";
            $stmtHist = $this->db->prepare("INSERT INTO order_status_history (order_id, status, description, created_at) VALUES (?, 'searching_driver', ?, NOW())");
            $stmtHist->execute([$orderId, $desc]);

            $this->db->commit();
            
            // Tìm thông tin khách hàng để gửi thông báo trấn an
            $stmtInfo = $this->db->prepare("SELECT tracking_code, customer_id FROM orders WHERE id = ?");
            $stmtInfo->execute([$orderId]);
            if ($info = $stmtInfo->fetch(\PDO::FETCH_ASSOC)) {
                $userModel = new \App\Models\User();
                $userModel->createNotification(
                    $info['customer_id'],
                    'Đang tìm lại tài xế',
                    "Đơn hàng #{$info['tracking_code']} đang được hệ thống tự động tìm lại tài xế mới do tài xế trước đó không đến lấy hàng.",
                    'order',
                    "/user/orders/track/{$info['tracking_code']}"
                );
            }
            
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Tự động hủy các đơn hàng chờ quá 1 ngày (24h) không có ai nhận.
     */
    public function autoCancelExpiredPendingOrders(): bool
    {
        // **nun_ai**: Cron-job System: Tự động dọn dẹp hệ thống bằng cách Quét và Hủy (Cancelled) các đơn hàng đã bị "treo" quá 24h (so với thời gian hẹn hoặc tạo đơn) mà vẫn chưa có bất kỳ tài xế nào tiếp nhận.
        $this->db->beginTransaction();
        try {
            // Lấy danh sách đơn quá hạn 1 ngày (so với lúc tạo hoặc lúc hẹn lấy hàng)
            $stmt = $this->db->prepare("
                SELECT id, tracking_code, customer_id 
                FROM orders 
                WHERE status IN ('pending', 'awaiting_payment', 'searching_driver') 
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
                $this->db->rollBack();
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
                    "Đơn hàng #{$order['tracking_code']} của bạn đã bị hủy tự động do không có tài xế nhận sau 24 giờ.",
                    'system',
                    "/user/orders/track/{$order['tracking_code']}"
                );
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
