<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DriverPenalty
{
    private PDO $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Áp dụng hình phạt tài chính và cộng dồn số lần vi phạm cho tài xế.
    public function applyPenalty(
        int $driverId,
        string $penaltyType,
        float $amount,
        string $reason,
        ?int $createdBy = null
    ): bool {
        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            // Insert penalty record
            $stmt = $this->db->prepare("
                INSERT INTO driver_penalties (driver_id, penalty_type, amount, reason, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$driverId, $penaltyType, $amount, $reason, $createdBy]);

            // Update user's total penalty amount
            $stmt = $this->db->prepare("
                UPDATE users 
                SET penalty_amount = penalty_amount + ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $driverId]);

            // Deduct penalty from driver wallet
            $walletModel = new Wallet();
            if (!$walletModel->forceDeduct($driverId, $amount, 'penalty', $reason)) {
                if ($ownsTransaction) $this->db->rollBack();
                return false;
            }

            // Check if driver should be banned
            $this->checkAndApplyBan($driverId);

            if ($ownsTransaction) $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Apply penalty failed: ' . $e->getMessage());
            return false;
        }
    }

    // Lấy danh sách lịch sử các lần bị phạt/vi phạm của một tài xế.
    public function getViolations(int $driverId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM driver_penalties 
            WHERE driver_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$driverId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra và tự động khóa tài khoản nếu tài xế vượt quá số lần vi phạm cho phép.
    private function checkAndApplyBan(int $driverId): void
    {
        try {
            $settingModel = new Setting();
            $violationThreshold = (int) $settingModel->get('violation_threshold_for_ban', 5);

            // BẢN VÁ: Cửa sổ trượt - Chỉ đếm các vi phạm thực tế xảy ra trong vòng 3 tháng (90 ngày) gần nhất
            $stmt = $this->db->prepare("
                SELECT COUNT(id) as recent_violations 
                FROM driver_penalties 
                WHERE driver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            ");
            $stmt->execute([$driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $violationCount = (int) ($result['recent_violations'] ?? 0);

            // Đồng bộ lại con số thực tế vào bảng users để phục vụ hiển thị
            $this->db->prepare("UPDATE users SET violation_count = ? WHERE id = ?")->execute([$violationCount, $driverId]);

            if ($violationCount >= $violationThreshold) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET is_blocked = 1, blocked_reason = CONCAT('Vượt quá giới hạn vi phạm (', ?, ' lần)')
                    WHERE id = ?
                ");
                $stmt->execute([$violationCount, $driverId]);
            }
        } catch (\Throwable $e) {
            error_log('Ban check failed: ' . $e->getMessage());
        }
    }
}
