<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    /**
     * Gửi email chung
     */
    public function send(string $toEmail, string $subject, string $htmlContent, string $toName = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USER'] ?? $_SERVER['MAIL_USER'] ?? '';
            $mail->Password   = $_ENV['MAIL_PASS'] ?? $_SERVER['MAIL_PASS'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 5; // Giới hạn Timeout 5s để tránh treo Server nếu SMTP sập

            // Tắt kiểm tra chứng chỉ SSL (Giúp sửa lỗi không gửi được mail )
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Người gửi & Người nhận
            $mail->setFrom($mail->Username ?: 'no-reply@nun.vn', 'NUN Express');
            if ($toName) {
                $mail->addAddress($toEmail, $toName);
            } else {
                $mail->addAddress($toEmail);
            }

            // Nội dung Email HTML
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;

            if (!$mail->send()) {
                throw new \Exception($mail->ErrorInfo ?: "Lỗi không xác định khi kết nối SMTP.");
            }
            
            return true;
        } catch (Exception $e) {
            throw new \Exception($mail->ErrorInfo ?: $e->getMessage());
        }
    }

    /**
     * Gửi email chứa mã OTP để xác thực tài khoản
     */
    public function sendVerificationEmail(string $toEmail, string $toName, string $otp): bool
    {
        $subject = "{$otp} là mã xác thực tài khoản NUN Express của bạn";
        $htmlContent = "
            <h2>Chào {$toName},</h2>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>NUN Express</strong>. Vui lòng sử dụng mã OTP dưới đây để hoàn tất quá trình đăng ký:</p>
            <div style='background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                <strong>{$otp}</strong>
            </div>
            <p>Mã này sẽ hết hạn sau 15 phút. Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
            <br>
            <p>Trân trọng,</p>
            <p><strong>Đội ngũ NUN Express</strong></p>
        ";
        
        return $this->send($toEmail, $subject, $htmlContent, $toName);
    }
}
