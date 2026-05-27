<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    protected PDO $db;

    // Ham __construct: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function __construct()
    {
        // **nun_ai**: Khởi tạo model, lấy instance của kết nối cơ sở dữ liệu PDO thông qua class Database (mô hình Singleton).
        $this->db = Database::getInstance();
    }

    /**
     * Dịch vai trò người dùng sang tiếng Việt
     */
    public static function getRoleLabel(string $role): string
    {
        // **nun_ai**: Hàm tiện ích tĩnh (static) dùng để chuyển đổi mã vai trò (user, driver, admin) thành chuỗi hiển thị tiếng Việt tương ứng.
        $roles = [
            'user'   => 'Khách hàng',
            'driver' => 'Tài xế',
            'admin'  => 'Quản trị viên'
        ];
        return $roles[$role] ?? $role;
    }

    // Ham findByAccount: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function findByAccount(string $account)
    {
        // **nun_ai**: Tìm kiếm một người dùng dựa trên tài khoản đăng nhập (có thể là email hoặc số điện thoại). Trả về bản ghi đầu tiên tìm thấy.
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$account, $account]);
        return $stmt->fetch();
    }

    // Ham findById: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function findById(int $id)
    {
        // **nun_ai**: Lấy thông tin chi tiết của một người dùng theo ID. Bao gồm các trường cơ bản từ bảng `users` và kết hợp (LEFT JOIN) với bảng `driver_profiles` để lấy thêm thông tin (ví dụ biển số xe) nếu người dùng đó là tài xế.
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.phone, u.email, u.created_at, u.avatar, u.role, u.is_blocked, u.no_show_count, dp.license_plate 
            FROM users u
            LEFT JOIN driver_profiles dp ON u.id = dp.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Ham findByIdWithPassword: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function findByIdWithPassword(int $id)
    {
        // **nun_ai**: Chỉ lấy ID và mật khẩu (đã băm) của người dùng. Thường được sử dụng trong các tác vụ kiểm tra mật khẩu cũ trước khi đổi mật khẩu mới nhằm tối ưu hiệu suất truy vấn.
        $stmt = $this->db->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Ham getDriverRating: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function getDriverRating(int $driverId): array
    {
        // **nun_ai**: Tính toán điểm đánh giá trung bình và tổng số lượt đánh giá của một tài xế dựa trên các dữ liệu trong bảng `driver_reviews`.
        // Logic này được chuyển từ file driver_profile.php cũ
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

    // Ham getDriverReviews: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function getDriverReviews(int $driverId): array
    {
        // **nun_ai**: Lấy danh sách tất cả các bài đánh giá về một tài xế, kèm theo tên của khách hàng đã đánh giá, sắp xếp theo thời gian mới nhất.
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

    // Ham getDriverProfile: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function getDriverProfile(int $userId)
    {
        // **nun_ai**: Lấy toàn bộ thông tin cấu hình và hồ sơ tài xế (bảng `driver_profiles`) dựa trên ID của người dùng.
        $stmt = $this->db->prepare("SELECT * FROM driver_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Ham create: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function create(string $name, string $email, string $phone, string $password, string $role = 'user', ?string $licensePlate = null, string $vehicleImage = ''): int|false
    {
        // **nun_ai**: Hàm bọc (wrapper) cho `createWithDriverProfile`. Định dạng lại các tham số đầu vào thành một mảng dữ liệu để gọi hàm tạo mới.
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
        // **nun_ai**: Tạo mới một người dùng. Nếu vai trò là `driver`, hàm này sẽ tự động tạo thêm một bản ghi trong bảng `driver_profiles` với các thiết lập mặc định (giới hạn đơn, tải trọng). Sử dụng Khóa giao dịch (Transaction) để đảm bảo tính toàn vẹn (thành công cả hai hoặc Rollback).
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
        // **nun_ai**: Nâng cấp tài khoản từ Khách hàng thành Tài xế. Tạo hoặc cập nhật hồ sơ trong bảng `driver_profiles` với trạng thái `is_verified = 0` (để chờ Quản trị viên duyệt). Sử dụng Transaction để đảm bảo an toàn.
        $this->db->beginTransaction();
        try {
            // Lấy thông số mặc định cho tài xế mới
            $settingModel = new Setting();
            $maxOrders = (int) $settingModel->get('default_max_concurrent_orders', 10);
            $maxWeight = (float) $settingModel->get('default_max_total_weight', 100);

            // Tạo hồ sơ tài xế (với is_verified = 0 để chờ Admin duyệt)
            $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, vehicle_registration_image, is_verified) VALUES (?, ?, ?, ?, ?, 0) ON DUPLICATE KEY UPDATE license_plate = VALUES(license_plate), vehicle_registration_image = VALUES(vehicle_registration_image), is_verified = 0");
            $stmtDriver->execute([$userId, $maxOrders, $maxWeight, $licensePlate, $vehicleImage]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Lỗi nâng cấp tài xế: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Xây dựng mệnh đề WHERE và tham số cho việc lọc người dùng.
     */
    private function _buildUserFilter(string $roleFilter = '', string $search = ''): array
    {
        // **nun_ai**: Hàm hỗ trợ nội bộ (private) dùng để xây dựng linh hoạt các chuỗi điều kiện WHERE và mảng tham số (parameters) cho câu truy vấn SQL, phục vụ cho việc lọc người dùng theo vai trò và từ khóa tìm kiếm.
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
        // **nun_ai**: Truy vấn danh sách người dùng có phân trang, kết hợp (LEFT JOIN) với bảng `driver_profiles` để lấy thêm thông tin hồ sơ/ví điện tử nếu họ là tài xế. Tích hợp sẵn bộ lọc động từ hàm `_buildUserFilter`.
        $sql = "
            SELECT 
                u.id, u.name, u.phone, u.email, u.role, u.created_at, u.is_blocked, u.avatar,
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

    // Ham countAll: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function countAll(string $roleFilter = '', string $search = ''): int
    {
        // **nun_ai**: Đếm tổng số lượng người dùng thỏa mãn điều kiện lọc. Kết quả của hàm này thường được Controller sử dụng để tính toán tổng số trang trong tính năng phân trang.
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
        // **nun_ai**: Cập nhật thông tin chung của người dùng. Nếu quản trị viên đang chỉnh sửa một tài xế, hàm sẽ đồng thời cập nhật cả các thông số bên bảng `driver_profiles` (như giới hạn đơn cùng lúc, tải trọng tối đa, số dư ví). Có sử dụng Transaction.
        $this->db->beginTransaction();
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
                $this->db->rollBack();
                return false;
            }

            // Cập nhật cấu hình tài xế nếu là role driver
            if (($data['role'] ?? '') === 'driver') {
                if (isset($data['max_concurrent_orders'])) {
                    $maxOrders = max(1, (int)$data['max_concurrent_orders']);
                    $maxWeight = max(1.0, (float)($data['max_total_weight'] ?? 100));
                    $licensePlate = $data['license_plate'] ?? '';
                    $isVerified = $data['is_driver_verified'] ?? 0;
                    
                    if (isset($data['balance'])) {
                        $balance = (float)$data['balance'];
                        $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, balance, license_plate, is_verified) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), balance = VALUES(balance), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified)");
                        $stmtDriver->execute([$id, $maxOrders, $maxWeight, $balance, $licensePlate, $isVerified]);
                    } else {
                        $stmtDriver = $this->db->prepare("INSERT INTO driver_profiles (user_id, max_concurrent_orders, max_total_weight, license_plate, is_verified) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_concurrent_orders = VALUES(max_concurrent_orders), max_total_weight = VALUES(max_total_weight), license_plate = VALUES(license_plate), is_verified = VALUES(is_verified)");
                        $stmtDriver->execute([$id, $maxOrders, $maxWeight, $licensePlate, $isVerified]);
                    }
                } elseif (isset($data['license_plate'])) { // Xử lý cho luồng Cập nhật Profile bình thường
                    $stmtDriver = $this->db->prepare("UPDATE driver_profiles SET license_plate = ? WHERE user_id = ?");
                    $stmtDriver->execute([$data['license_plate'], $id]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Lỗi cập nhật người dùng: " . $e->getMessage());
            return false;
        }
    }

    // Ham updateLicensePlate: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function updateLicensePlate(int $userId, string $licensePlate): bool
    {
        // **nun_ai**: Cập nhật riêng lẻ trường thông tin biển số xe cho một tài xế.
        $stmt = $this->db->prepare("UPDATE driver_profiles SET license_plate = ? WHERE user_id = ?");
        return $stmt->execute([$licensePlate, $userId]);
    }

    /**
     * Cập nhật mật khẩu của người dùng.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        // **nun_ai**: Cập nhật mật khẩu mới (yêu cầu mật khẩu đã được băm - hashed) cho người dùng trong cơ sở dữ liệu.
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Cập nhật trạng thái khóa của người dùng.
     */
    public function updateBlockStatus(int $id, int $isBlocked): bool
    {
        // **nun_ai**: Thay đổi trạng thái khóa hoặc mở khóa (`is_blocked`) của tài khoản người dùng.
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
        // **nun_ai**: Cập nhật tọa độ GPS (Vĩ độ, Kinh độ) hiện tại của tài xế vào bảng `driver_profiles`. Sử dụng cú pháp "ON DUPLICATE KEY UPDATE" để chèn mới nếu bản ghi chưa có, hoặc ghi đè dữ liệu nếu đã tồn tại.
        $stmt = $this->db->prepare("INSERT INTO driver_profiles (user_id, current_lat, current_lng) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE current_lat = VALUES(current_lat), current_lng = VALUES(current_lng)");
        return $stmt->execute([$userId, $lat, $lng]);
    }

    /**
     * Lấy vị trí hiện tại của tài xế từ bảng driver_profiles
     */
    public function getLocation(int $userId): ?array
    {
        // **nun_ai**: Truy xuất tọa độ GPS (Vĩ độ, Kinh độ) hiện tại của tài xế từ bảng `driver_profiles`.
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

    // Ham updateAvatar: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function updateAvatar(int $id, string $avatarUrl): bool
    {
        // **nun_ai**: Cập nhật đường dẫn URL tới ảnh đại diện (avatar) của người dùng.
        $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarUrl, $id]);
    }

    // Ham setVerificationToken: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    public function setVerificationToken(int $userId, string $token): bool
    {
        // **nun_ai**: Gán một mã xác thực (OTP/Token) cho người dùng và đánh dấu trạng thái tài khoản là chưa xác minh (`is_verified = 0`). Thường được gọi trong luồng quên mật khẩu hoặc cần xác minh email.
        $stmt = $this->db->prepare("UPDATE users SET verification_token = ?, is_verified = 0 WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }

    /**
     * Tìm người dùng bằng mã OTP.
     */
    public function findUserByToken(string $token)
    {
        // **nun_ai**: Tìm kiếm người dùng có tài khoản chưa xác minh mà mã xác thực (token) trong CSDL khớp với mã được cung cấp.
        $stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = ? AND is_verified = 0");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Đánh dấu email của người dùng đã được xác thực.
     */
    public function markEmailAsVerified(int $userId): bool
    {
        // **nun_ai**: Đánh dấu tài khoản là đã xác minh thành công (`is_verified = 1`) và xóa bỏ mã xác thực (token) đi để dọn dẹp.
        $stmt = $this->db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Tạo thông báo mới cho người dùng
     */
    public function createNotification(int $userId, string $title, string $message, string $type = 'system', ?string $link = null): bool
    {
        // **nun_ai**: Ủy quyền (delegate) việc tạo thông báo đẩy (Notification) vào CSDL thông qua Model `Notification`.
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
        // **nun_ai**: Lấy danh sách ID của tất cả người dùng có quyền Quản trị viên (Admin), thường dùng để gửi thông báo sự kiện quan trọng.
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND is_blocked = 0");
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    // Ham normalizeEmail: xu ly nghiep vu hoac tien ich tuong ung trong he thong.
    private function normalizeEmail(?string $email): ?string
    {
        // **nun_ai**: Hàm hỗ trợ nội bộ giúp làm sạch chuỗi email. Chuyển các chuỗi chỉ có khoảng trắng thành `null` để tránh lỗi vi phạm Ràng buộc Duy nhất (Unique Constraint) trong Database.
        $email = trim((string) $email);

        return $email === '' ? null : $email;
    }

    /**
     * Track a no-show event for a customer (when they don't pick up)
     * Auto-ban if threshold exceeded
     */
    public function recordNoShow(int $userId): bool
    {
        // **nun_ai**: Ghi nhận một lần "vi phạm giao nhận" của người dùng. Nếu tổng số lần vi phạm vượt qua ngưỡng cấu hình (trong bảng `settings`), thuật toán sẽ tự động khóa tài khoản (`is_blocked = 1`). Chạy trong Transaction để đảm bảo tính an toàn hệ thống.
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
     * Get no-show count for a user
     */
    public function getNoShowCount(int $userId): int
    {
        // **nun_ai**: Lấy tổng số lần một khách hàng đã có hành vi "vi phạm giao nhận" từ trước đến nay.
        $stmt = $this->db->prepare("SELECT no_show_count FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['no_show_count'] ?? 0);
    }

    /**
     * Reset no-show count (admin action)
     */
    public function resetNoShowCount(int $userId): bool
    {
        // **nun_ai**: Đặt lại bộ đếm số lần "vi phạm giao nhận" về mốc 0 (Thường được Admin thực hiện sau khi giải quyết tranh chấp thành công hoặc ân xá).
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
