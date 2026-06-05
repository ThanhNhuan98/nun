<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    protected PDO $db;

    // Khởi tạo model Setting và kết nối cơ sở dữ liệu.
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy giá trị cấu hình theo khóa (key), trả về mặc định nếu không tồn tại.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    /**
     * Lấy tất cả các cài đặt hệ thống dưới dạng mảng cặp key-value.
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    /**
     * Cập nhật hoặc chèn mới danh sách các giá trị cài đặt vào hệ thống.
     * @param array $settings
     * @return bool
     */
    public function update(array $settings): bool
    {
        if (empty($settings)) {
            return true;
        }

        $values = [];
        $params = [];

        foreach ($settings as $key => $value) {
            $values[] = "(?, ?)";
            // Tối ưu hóa: Dùng toán tử append [] thay cho array_push
            $params[] = $key;
            $params[] = $value;
        }
        
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES " . implode(', ', $values) . " ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return true;
    }
}
