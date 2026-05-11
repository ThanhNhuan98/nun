<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Wallet
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public function deduct(
        int $userId,
        float $amount,
        string $type = 'platform_fee',
        string $description = '',
        ?int $orderId = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = (float) ($stmt->fetchColumn() ?: 0);

            if ($currentBalance < $amount) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }

                return false;
            }

            $balanceAfter = $currentBalance - $amount;
            $stmt = $this->db->prepare("UPDATE driver_profiles SET balance = ? WHERE user_id = ?");
            $stmt->execute([$balanceAfter, $userId]);

            $this->recordTransaction($userId, $orderId, -$amount, $type, $description ?: 'Platform fee', $balanceAfter);

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Wallet deduct failed: ' . $e->getMessage());
            return false;
        }
    }

    public function add(
        int $userId,
        float $amount,
        string $type = 'refund',
        string $description = '',
        ?int $orderId = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = (float) ($stmt->fetchColumn() ?: 0);
            $balanceAfter = $currentBalance + $amount;

            $stmt = $this->db->prepare("UPDATE driver_profiles SET balance = ? WHERE user_id = ?");
            $stmt->execute([$balanceAfter, $userId]);

            $this->recordTransaction($userId, $orderId, $amount, $type, $description ?: 'Wallet credit', $balanceAfter);

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Wallet credit failed: ' . $e->getMessage());
            return false;
        }
    }

    private function recordTransaction(
        int $userId,
        ?int $orderId,
        float $amount,
        string $type,
        string $description,
        float $balanceAfter
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO wallet_transactions (user_id, order_id, amount, type, description, balance_after, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $orderId, $amount, $type, $description, $balanceAfter]);
        } catch (\Throwable $e) {
            error_log('Wallet transaction log failed: ' . $e->getMessage());
        }
    }

    /**
     * Credit driver earnings based on percentage of order value
     * Called when order is completed
     */
    public function creditDriverEarnings(int $driverId, int $orderId, float $shippingFee): bool
    {
        if ($shippingFee <= 0) {
            return false;
        }

        try {
            // Get commission percentage from settings (default 15%)
            $settingModel = new Setting();
            $commissionPercentage = (float) $settingModel->get('driver_commission_percentage', 15);

            // Calculate driver earnings
            $driverEarnings = $shippingFee * ($commissionPercentage / 100);

            // Credit to driver wallet
            return $this->add(
                $driverId,
                $driverEarnings,
                'adjustment',
                "Earnings from order #{$orderId} ({$commissionPercentage}%)",
                $orderId
            );
        } catch (\Throwable $e) {
            error_log('Driver earnings credit failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get driver commission percentage
     */
    public static function getCommissionPercentage(): float
    {
        $settingModel = new Setting();
        return (float) $settingModel->get('driver_commission_percentage', 15);
    }

    /**
     * Calculate driver earnings from shipping fee
     */
    public static function calculateEarnings(float $shippingFee): float
    {
        $percentage = self::getCommissionPercentage();
        return $shippingFee * ($percentage / 100);
    }
}
