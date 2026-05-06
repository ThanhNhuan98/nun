<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy giá trị của một cài đặt
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
     * Lấy tất cả cài đặt
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    /**
     * Cập nhật một hoặc nhiều cài đặt
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
            array_push($params, $key, $value);
        }
        
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES " . implode(', ', $values) . " ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return true;
    }
}