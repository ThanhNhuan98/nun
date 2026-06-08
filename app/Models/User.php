<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    protected PDO $db;

    // Khởi tạo kết nối cơ sở dữ liệu
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Dịch vai trò người dùng sang tiếng Việt
     */
    public static function getRoleLabel(string $role): string
    {
        $roles = [
            'user'   => 'Khách hàng',
            'driver' => 'Tài xế',
            'admin'  => 'Quản trị viên'
        ];
        return $roles[$role] ?? $role;
    }

    // Tìm kiếm người dùng dựa trên tài khoản đăng nhập (Email/SDT)
    public function findByAccount(string $account)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$account, $account]);
        return $stmt->fetch();
    }

    // Lấy chi tiết thông tin của người dùng kèm hồ sơ tài xế theo ID
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.phone, u.email, u.created_at, u.avatar, u.role, u.is_blocked, u.no_show_count, u.violation_count, dp.license_plate 
            FROM users u
            LEFT JOIN driver_profiles dp ON u.id = dp.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Trích xuất ID và mật khẩu (Đã băm) của người dùng
    public function findByIdWithPassword(int $id)
    {
        $stmt = $this->db->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Tính điểm đánh giá (Rating) trung bình của tài xế
    public function getDriverRating(int $driverId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(AVG(rating), 0) as avg_rating, 
                COUNT(id) as total_reviews 
            FROM driver_reviews 
            WHERE driver_id = ?
        ");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch();

        return [
            'avg' => number_format((float)($result['avg_rating'] ?? 0), 1),
            'total' => (int)($result['total_reviews'] ?? 0)
        ];
    }

    // Lấy danh sách đánh giá của tài xế
    public function getDriverReviews(int $driverId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.name as customer_name 
            FROM driver_reviews r
            JOIN users u ON r.customer_id = u.id
            WHERE r.driver_id = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    // Trích xuất cấu hình Hồ sơ tài xế (Driver Profile)
    public function getDriverProfile(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM driver_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Cú pháp rút gọn để tạo tài khoản người dùng
    public function create(string $name, string $email, string $phone, string $password, string $role = 'user', ?string $licensePlate = null, string $vehicleImage = ''): int|false
    {
        $createData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => $role,
            'license_plate' => $licensePlate,
            'vehicle_registration_image' => $vehicleImage
        ];
        return $this->createWithDriverProfile($createData);
    }

    /**
     * Tạo người dùng mới và hồ sơ tài xế nếu có. Dùng cho Admin.
     * @param array $data Dữ liệu người dùng, bao gồm password đã hash.
     * @return int|false Trả về ID người dùng nếu thành công, ngược lại trả về false.
     */
    public function createWithDriverProfile(array $data): int|false
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $defaultAvatar = '/assets/images/default-avatar.png';
            $stmt = $this->db->prepare("INSERT INTO users (name, email, phone, password, role, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['name'],
                $this->normalizeEmail($data['email'] ?? ''),
                $data['phone'],
                $data['password'], // Password should be pre-hashed
                $data['role'],
                $defaultAvatar
            ]);
            $userId = (int) $this->db->lastInsertId();

            if ($data['role'] === 'driver') {
                $settingModel = new Setting();
                $maxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
                $maxWeight = (float) $settingModel->get('default_max_total_weight', 100);
                $licensePlate = $data['license_plate'] ?? null;
                $vehicleImage = $data['vehicle_registration_image'] ?? '';

                // Tạo hồ sơ tài xế nếu chưa có
                $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, vehicle_registration_image) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
                $stmtDriver->execute([$userId, $maxOrders, $maxWeight, $licensePlate, $vehicleImage]);
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $userId;
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            throw $e; // Ném thẳng lỗi ra ngoài để Controller bắt và hiển thị
        }
    }

    /**
     * Nâng cấp người dùng từ Khách hàng lên Tài xế
     */
    public function upgradeToDriver(int $userId, string $licensePlate, string $vehicleImage): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // Lấy thông số mặc định cho tài xế mới
            $settingModel = new Setting();
            $maxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
            $maxWeight = (float) $settingModel->get('default_max_total_weight', 100);

            // Tạo hồ sơ tài xế (với is_verified = 0 để chờ Admin duyệt)
            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, vehicle_registration_image, is_verified) VALUES (?, ?, ?, ?, ?, 0) ON DUPLICATE KEY UPDATE license_plate = VALUES(license_plate), vehicle_registration_image = VALUES(vehicle_registration_image), is_verified = 0");
            $stmtDriver->execute([$userId, $maxOrders, $maxWeight, $licensePlate, $vehicleImage]);

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            error_log("Lỗi nâng cấp tài xế: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Xây dựng mệnh đề WHERE và tham số cho việc lọc người dùng.
     */
    private function _buildUserFilter(string $roleFilter = '', string $search = ''): array
    {
        $where = [];
        $params = [];

        if ($roleFilter !== '') {
            $where[] = "u.role = :role";
            $params[':role'] = $roleFilter;
        }
        if ($search !== '') {
            // Sử dụng u. prefix cho các cột để tương thích với JOIN
            $where[] = "(u.name LIKE :search OR u.phone LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $sqlWhere = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

        return ['sql' => $sqlWhere, 'params' => $params];
    }
    /**
     * Lấy tất cả người dùng với thông tin bổ sung cho trang admin.
     * @param string $roleFilter Lọc theo vai trò.
     * @param int $limit Số lượng bản ghi trên mỗi trang.
     * @param int $offset Vị trí bắt đầu lấy.
     * @param string $search Từ khóa tìm kiếm.
     * @return array
     */
    public function getAllWithDriverProfile(string $roleFilter = '', int $limit = 15, int $offset = 0, string $search = ''): array
    {
        $sql = "
            SELECT 
                u.id, u.name, u.phone, u.email, u.role, u.created_at, u.is_blocked, u.avatar, u.violation_count,
                dp.balance, dp.license_plate, dp.is_verified AS is_driver_verified
            FROM users u
            LEFT JOIN driver_profiles dp ON u.id = dp.user_id
        ";
        
        $filter = $this->_buildUserFilter($roleFilter, $search);
        $sql .= $filter['sql'];
        $sql .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Gán các tham số từ helper
        foreach ($filter['params'] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đếm tổng số lượng người dùng thỏa mãn điều kiện lọc
    public function countAll(string $roleFilter = '', string $search = ''): int
    {
        // Thêm alias 'u' để tương thích với helper
        $sql = "SELECT COUNT(u.id) FROM users u";
        $filter = $this->_buildUserFilter($roleFilter, $search);
        $sql .= $filter['sql'];
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($filter['params']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cập nhật thông tin người dùng (dùng cho Admin).
     */
    public function update(int $id, array $data): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $email = $this->normalizeEmail($data['email'] ?? '');
            
            if (isset($data['no_show_count'])) {
                $stmt = $this->db->prepare("UPDATE users SET name = :name, phone = :phone, email = :email, role = :role, no_show_count = :no_show WHERE id = :id");
                $stmt->bindValue(':no_show', $data['no_show_count'], PDO::PARAM_INT);
            } else {
                $stmt = $this->db->prepare("UPDATE users SET name = :name, phone = :phone, email = :email, role = :role WHERE id = :id");
            }
            
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':role', $data['role'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                if ($ownsTransaction) $this->db->rollBack();
                return false;
            }

            // Cập nhật cấu hình tài xế nếu là role driver
            if (($data['role'] ?? '') === 'driver') {
                if (isset($data['max_concurrent_orders'])) {
                    $maxOrders = max(1, (int)$data['max_concurrent_orders']);
                    $maxWeight = max(1.0, (float)($data['max_total_weight'] ?? 100));
                    $licensePlate = $data['license_plate'] ?? '';
                    $isVerified = $data['is_driver_verified'] ?? 0;
                    $vehicleImage = $data['vehicle_registration_image'] ?? null;
                    
                    if (isset($data['balance'])) {
                        $balance = (float)$data['balance'];
                        if ($vehicleImage !== null && $vehicleImage !== '') {
                            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, balance, license_plate, is_verified, vehicle_registration_image) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), balance = VALUES(balance), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified), vehicle_registration_image = VALUES(vehicle_registration_image)");
                            $stmtDriver->execute([$id, $maxOrders, $maxWeight, $balance, $licensePlate, $isVerified, $vehicleImage]);
                        } else {
                            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, balance, license_plate, is_verified) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), balance = VALUES(balance), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified)");
                            $stmtDriver->execute([$id, $maxOrders, $maxWeight, $balance, $licensePlate, $isVerified]);
                        }
                    } else {
                        if ($vehicleImage !== null && $vehicleImage !== '') {
                            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, is_verified, vehicle_registration_image) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified), vehicle_registration_image = VALUES(vehicle_registration_image)");
                            $stmtDriver->execute([$id, $maxOrders, $maxWeight, $licensePlate, $isVerified, $vehicleImage]);
                        } else {
                            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, is_verified) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified)");
                            $stmtDriver->execute([$id, $maxOrders, $maxWeight, $licensePlate, $isVerified]);
                        }
                    }
                } elseif (isset($data['license_plate'])) { // Xử lý cho luồng Cập nhật Profile bình thường
                    $stmtDriver = $this->db->prepare("UPDATE driver_profiles SET license_plate = ? WHERE user_id = ?");
                    $stmtDriver->execute([$data['license_plate'], $id]);
                }
            }
            
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\PDOException $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            error_log("Lỗi cập nhật người dùng: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật biển số xe của tài xế
    public function updateLicensePlate(int $userId, string $licensePlate): bool
    {
        $stmt = $this->db->prepare("UPDATE driver_profiles SET license_plate = ? WHERE user_id = ?");
        return $stmt->execute([$licensePlate, $userId]);
    }

    /**
     * Cập nhật mật khẩu của người dùng.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Cập nhật trạng thái khóa của người dùng.
     */
    public function updateBlockStatus(int $id, int $isBlocked): bool
    {
        try {
            $status = $isBlocked === 1 ? 1 : 0;
            $stmt = $this->db->prepare("UPDATE users SET is_blocked = :status WHERE id = :id");
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Lỗi cập nhật trạng thái khóa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật vị trí hiện tại của tài xế vào bảng driver_profiles
     */
    public function updateLocation(int $userId, float $lat, float $lng): bool
    {
        $stmt = $this->db->prepare("INSERT INTO driver_profiles (user_id, current_lat, current_lng) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE current_lat = VALUES(current_lat), current_lng = VALUES(current_lng)");
        return $stmt->execute([$userId, $lat, $lng]);
    }

    /**
     * Lấy vị trí hiện tại của tài xế từ bảng driver_profiles
     */
    public function getLocation(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT current_lat, current_lng FROM driver_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $location = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($location && $location['current_lat'] !== null && $location['current_lng'] !== null) {
            return [
                'lat' => (float) $location['current_lat'],
                'lng' => (float) $location['current_lng']
            ];
        }
        return null;
    }

    // Cập nhật đường dẫn ảnh đại diện (Avatar) cho người dùng
    public function updateAvatar(int $id, string $avatarUrl): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarUrl, $id]);
    }

    // Khởi tạo mã Token phục vụ cho việc cấp lại mật khẩu hoặc xác thực
    public function setVerificationToken(int $userId, string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET verification_token = ?, is_verified = 0 WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }

    /**
     * Tìm người dùng bằng mã OTP.
     */
    public function findUserByToken(string $token)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = ? AND is_verified = 0");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Đánh dấu email của người dùng đã được xác thực.
     */
    public function markEmailAsVerified(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Tạo thông báo mới cho người dùng
     */
    public function createNotification(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): bool
    {
        $result = (new Notification())->create($userId, $title, $message, $type, $link);

        // Tích hợp Pusher: Bắn thông báo Real-time ngay sau khi lưu DB thành công
        if ($result && class_exists('\App\Services\PusherService')) {
            try {
                $pusher = new \App\Services\PusherService();
                // Đổi thành kênh Public để Frontend dễ bắt sự kiện mà không cần API Auth
                $pusher->trigger('notify-user-' . $userId, 'new_notification', [
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'link' => $link
                ]);
            } catch (\Throwable $e) {
                error_log('Lỗi Pusher: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Lấy danh sách ID của tất cả Quản trị viên
     */
    public function getAdminIds(): array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND is_blocked = 0");
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    // Làm sạch chuỗi Email để tránh lỗi rỗng trong CSDL
    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : $email;
    }

    /**
     * Track a no-show event for a customer (when they don't pick up)
     * Auto-ban if threshold exceeded
     */
    public function recordNoShow(int $userId): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            // Increment no-show count
            $stmt = $this->db->prepare("
                UPDATE users 
                SET no_show_count = no_show_count + 1 
                WHERE id = ? AND role = 'user'
            ");
            $stmt->execute([$userId]);

            // Get setting for no-show threshold
            $settingModel = new Setting();
            $threshold = (int) $settingModel->get('no_show_threshold_for_ban', 3);

            // Get current no-show count
            $stmt = $this->db->prepare("SELECT no_show_count FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $noShowCount = (int) ($result['no_show_count'] ?? 0);

            // Auto-ban if threshold reached
            if ($noShowCount >= $threshold) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET is_blocked = 1
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Record no-show failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset no-show count (admin action)
     */
    public function resetNoShowCount(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET no_show_count = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Cập nhật trạng thái Sẵn sàng nhận đơn của tài xế
     */
    public function updateOnlineStatus(int $userId, int $isOnline): bool
    {
        $stmt = $this->db->prepare("UPDATE driver_profiles SET is_online = ? WHERE user_id = ?");
        return $stmt->execute([$isOnline, $userId]);
    }
}
