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

    /**
     * Apply a penalty to a driver
     */
    public function applyPenalty(
        int $driverId,
        string $penaltyType,
        float $amount,
        string $reason,
        ?int $createdBy = null
    ): bool {
        try {
            $this->db->beginTransaction();

            // Insert penalty record
            $stmt = $this->db->prepare("
                INSERT INTO driver_penalties (driver_id, penalty_type, amount, reason, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$driverId, $penaltyType, $amount, $reason, $createdBy]);

            // Update user's total penalty amount
            $stmt = $this->db->prepare("
                UPDATE users 
                SET penalty_amount = penalty_amount + ?, violation_count = violation_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$amount, $driverId]);

            // Deduct penalty from driver wallet
            $walletModel = new Wallet();
            if (!$walletModel->deduct($driverId, $amount, 'penalty', $reason)) {
                $this->db->rollBack();
                return false;
            }

            // Check if driver should be banned
            $this->checkAndApplyBan($driverId);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Apply penalty failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get driver's violation history
     */
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

    /**
     * Get total unpaid penalties
     */
    public function getTotalUnpaidPenalties(int $driverId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM driver_penalties 
            WHERE driver_id = ? AND is_paid = 0
        ");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(int $penaltyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE driver_penalties 
            SET is_paid = 1, paid_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$penaltyId]);
    }

    /**
     * Check if driver should be banned and apply ban if needed
     */
    private function checkAndApplyBan(int $driverId): void
    {
        try {
            $settingModel = new Setting();
            $violationThreshold = (int) $settingModel->get('violation_threshold_for_ban', 5);

            // Get current violation count
            $stmt = $this->db->prepare("SELECT violation_count FROM users WHERE id = ?");
            $stmt->execute([$driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $violationCount = (int) ($result['violation_count'] ?? 0);

            if ($violationCount >= $violationThreshold) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET is_blocked = 1, blocked_reason = 'Vượt quá giới hạn vi phạm: ' + ?, blocked_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$violationCount, $driverId]);
            }
        } catch (\Throwable $e) {
            error_log('Ban check failed: ' . $e->getMessage());
        }
    }

    /**
     * Get penalty types
     */
    public static function getPenaltyTypes(): array
    {
        return [
            'cancellation' => 'Hủy đơn',
            'late_delivery' => 'Giao hàng muộn',
            'no_response' => 'Không phản hồi',
            'customer_complaint' => 'Khiếu nại khách hàng',
            'traffic_violation' => 'Vi phạm giao thông'
        ];
    }

    /**
     * Calculate multiplier for penalties based on violation count
     */
    public function getPenaltyMultiplier(int $driverId): float
    {
        $stmt = $this->db->prepare("SELECT violation_count FROM users WHERE id = ?");
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $violationCount = (int) ($result['violation_count'] ?? 0);

        $settingModel = new Setting();
        $baseMultiplier = (float) $settingModel->get('penalty_multiplier', 1.5);

        return 1.0 + ($violationCount * ($baseMultiplier - 1.0));
    }
}
