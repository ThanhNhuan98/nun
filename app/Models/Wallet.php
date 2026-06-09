<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Wallet
{
    private PDO $db;
    private const ALLOWED_TRANSACTION_TYPES = ['deposit', 'platform_fee', 'refund', 'adjustment', 'penalty'];

    // Khởi tạo model Wallet và kết nối cơ sở dữ liệu.
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Lấy số dư ví hiện hành của tài xế.
    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Khấu trừ tiền ví tài xế an toàn kèm theo xử lý ánh xạ ENUM CSDL
     */
    public function deduct(int $userId, float $amount, string $type = 'platform_fee', string $description = '', ?int $orderId = null): bool
    {
        if ($amount <= 0) return false;

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();

        try {
            // [KHÓA BI QUAN - PESSIMISTIC LOCKING]
            // Sử dụng mệnh đề `FOR UPDATE` để khóa dòng (Row-level lock) tài khoản của tài xế.
            // Ngăn chặn luồng khác đọc số dư hiện tại cho đến khi luồng này thực hiện xong giao dịch (commit).
            // Đảm bảo an toàn tuyệt đối tránh Race Condition làm sai lệch tiền trong ví.
            $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = (float) ($stmt->fetchColumn() ?: 0);

            if ($currentBalance < $amount) {
                if ($ownsTransaction) $this->db->rollBack();
                return false;
            }

            $balanceAfter = $currentBalance - $amount;
            $stmtUpdate = $this->db->prepare("UPDATE driver_profiles SET balance = ? WHERE user_id = ?");
            $stmtUpdate->execute([$balanceAfter, $userId]);

            $this->logTransaction($userId, $orderId, -$amount, $type, $description, $balanceAfter);

            if ($ownsTransaction) $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) $this->db->rollBack();
            error_log('Wallet deduct failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ép buộc trừ tiền (Bất kể số dư có bị âm hay không - dùng cho việc Admin ra quyết định phạt nặng)
     */
    public function forceDeduct(int $userId, float $amount, string $type = 'platform_fee', string $description = '', ?int $orderId = null): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();

        try {
            // [KHÓA BI QUAN - PESSIMISTIC LOCKING]
            // Khóa dòng để đảm bảo tính toàn vẹn dữ liệu khi cưỡng chế trừ tiền (phạt vi phạm).
            $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = (float) ($stmt->fetchColumn() ?: 0);

            $balanceAfter = $currentBalance - $amount;
            $stmtUpdate = $this->db->prepare("UPDATE driver_profiles SET balance = ? WHERE user_id = ?");
            $stmtUpdate->execute([$balanceAfter, $userId]);

            $this->logTransaction($userId, $orderId, -$amount, $type, $description, $balanceAfter);

            if ($ownsTransaction) $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) $this->db->rollBack();
            error_log('Wallet forceDeduct failed: ' . $e->getMessage());
            return false;
        }
    }

    // Cộng tiền vào ví tài xế và lưu lại lịch sử biến động số dư.
    public function add(int $userId, float $amount, string $type = 'deposit', string $description = '', ?int $orderId = null): bool
    {
        if ($amount <= 0) return false;

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();

        try {
            // [KHÓA BI QUAN - PESSIMISTIC LOCKING]
            // Đảm bảo số dư cũ được đọc chính xác tuyệt đối trước khi cộng thêm tiền mới.
            $stmt = $this->db->prepare("SELECT balance FROM driver_profiles WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = (float) ($stmt->fetchColumn() ?: 0);

            $balanceAfter = $currentBalance + $amount;
            $stmtUpdate = $this->db->prepare("UPDATE driver_profiles SET balance = ? WHERE user_id = ?");
            $stmtUpdate->execute([$balanceAfter, $userId]);

            $this->logTransaction($userId, $orderId, $amount, $type, $description, $balanceAfter);

            if ($ownsTransaction) $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction) $this->db->rollBack();
            error_log('Wallet add failed: ' . $e->getMessage());
            return false;
        }
    }

    // Ghi nhận chi tiết một giao dịch (nạp/trừ/hoàn tiền) vào bảng wallet_transactions.
    private function logTransaction(int $userId, ?int $orderId, float $amount, string $type, string $description, float $balanceAfter): void
    {
        $normalizedType = in_array($type, self::ALLOWED_TRANSACTION_TYPES, true) ? $type : 'adjustment';
        $stmt = $this->db->prepare("
            INSERT INTO wallet_transactions (user_id, order_id, amount, type, description, balance_after, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $orderId, $amount, $normalizedType, $description, $balanceAfter]);
    }
}
