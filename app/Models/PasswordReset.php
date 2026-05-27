<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PasswordReset
{
    private PDO $db;

    // Khởi tạo model PasswordReset và kết nối cơ sở dữ liệu.
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate OTP and send to user's email
     * Returns the OTP code if successful
     */
    public function generateAndSendOTP(string $emailOrPhone): array
    {
        try {
            // Find user
            $userModel = new User();
            $user = $userModel->findByAccount($emailOrPhone);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email hoặc số điện thoại không tồn tại'
                ];
            }

            if (!$user['is_verified'] || $user['is_blocked']) {
                return [
                    'success' => false,
                    'message' => 'Tài khoản của bạn chưa được xác thực hoặc đã bị khóa'
                ];
            }

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

            // Clean up old OTPs
            $stmt = $this->db->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND otp_expires_at < NOW()");
            $stmt->execute([$user['id']]);

            // Insert OTP record
            $stmt = $this->db->prepare("
                INSERT INTO password_reset_tokens (user_id, otp_code, otp_expires_at, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user['id'], $otp, $otpExpiresAt]);

            // Send OTP to email (if email exists and is verified)
            if (!empty($user['email'])) {
                $this->sendOTPEmail($user['email'], $user['name'], $otp);
            }

            return [
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra.',
                'user_id' => $user['id'],
                'email_hint' => $this->maskEmail($user['email'] ?? '')
            ];
        } catch (\Throwable $e) {
            error_log('Generate OTP failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP(int $userId, string $otpCode): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM password_reset_tokens 
                WHERE user_id = ? AND otp_code = ? AND otp_expires_at > NOW() AND is_used = 0
                LIMIT 1
            ");
            $stmt->execute([$userId, $otpCode]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn'
                ];
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(50));
            $tokenExpiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Update token record
            $stmt = $this->db->prepare("
                UPDATE password_reset_tokens 
                SET reset_token = ?, token_expires_at = ?, is_used = 1, used_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$resetToken, $tokenExpiresAt, $token['id']]);

            return [
                'success' => true,
                'message' => 'Xác thực OTP thành công',
                'reset_token' => $resetToken,
                'user_id' => $userId
            ];
        } catch (\Throwable $e) {
            error_log('Verify OTP failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ];
        }
    }

    /**
     * Reset password using reset token
     */
    public function resetPassword(int $userId, string $resetToken, string $newPassword): array
    {
        try {
            // Verify token
            $stmt = $this->db->prepare("
                SELECT * FROM password_reset_tokens 
                WHERE user_id = ? AND reset_token = ? AND token_expires_at > NOW() AND is_used = 1
                LIMIT 1
            ");
            $stmt->execute([$userId, $resetToken]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn'
                ];
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // Begin transaction
            $this->db->beginTransaction();

            // Update user password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            // Mark token as used and clear
            $stmt = $this->db->prepare("
                UPDATE password_reset_tokens 
                SET reset_token = NULL, token_expires_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$token['id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Mật khẩu đã được cập nhật thành công'
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reset password failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ];
        }
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): void
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM password_reset_tokens 
                WHERE (otp_expires_at < NOW() OR token_expires_at < NOW())
                AND is_used = 0
            ");
            $stmt->execute();
        } catch (\Throwable $e) {
            error_log('Cleanup tokens failed: ' . $e->getMessage());
        }
    }

    /**
     * Send OTP to email
     */
    private function sendOTPEmail(string $email, string $name, string $otp): bool
    {
        try {
            $mailService = new \App\Services\MailService();
            $subject = 'Mã xác thực đặt lại mật khẩu NUN Express';
            $htmlContent = "
                <h2>Xin chào {$name},</h2>
                <p>Bạn đã yêu cầu đặt lại mật khẩu. Đây là mã OTP của bạn:</p>
                <h1 style='color: #3b82f6; letter-spacing: 5px; font-family: monospace;'>{$otp}</h1>
                <p>Mã này có hiệu lực trong 10 phút.</p>
                <p style='color: #999; font-size: 12px;'>Nếu bạn không yêu cầu điều này, vui lòng bỏ qua email này.</p>
            ";

            return $mailService->send($email, $subject, $htmlContent);
        } catch (\Throwable $e) {
            error_log('Send OTP email failed: ' . $e->getMessage());
            return false;
        }
    }


    // Che giấu một phần địa chỉ email (VD: abc***@gmail.com) để bảo vệ quyền riêng tư.
    private function maskEmail(string $email): string
    {
        if (empty($email)) {
            return '';
        }
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1] ?? '';
        $len = strlen($username);
        
        if ($len <= 3) {
            return str_repeat('*', $len) . '@' . $domain;
        }
        
        return substr($username, 0, 3) . str_repeat('*', $len - 3) . '@' . $domain;
    }
}
