<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Services\MailService;
use App\Controllers\BaseController;

class AuthController extends BaseController
{
    /**
     * Helper xử lý upload ảnh giấy đăng ký xe
     */

    public function showLoginForm(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        return $response->render('auth/login', [
            'pageTitle' => 'Đăng nhập - NUN Express'
        ]);
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getBody();

        try {
            (new Validator($data))->validate([
                'account' => 'required',
                'password' => 'required'
            ])->throw();

            $account = trim($data['account'] ?? '');
            $password = $data['password'] ?? '';

            $userModel = new User();
            $user = $userModel->findByAccount($account);

            if ($user && password_verify($password, $user['password'])) {
                if (!empty($user['is_blocked'])) {
                    $reason = !empty($user['blocked_reason']) ? $user['blocked_reason'] : 'Vui lòng liên hệ quản trị viên.';
                    return $response->render('auth/login', [
                        'pageTitle' => 'Đăng nhập - NUN Express',
                        'error' => 'Tài khoản của bạn đã bị khóa. Lý do: ' . $reason,
                        'old' => ['account' => $account]
                    ]);
                }

                session_regenerate_id(true);
                $sessionUser = $user;
                unset($sessionUser['password'], $sessionUser['verification_token']);

                $_SESSION['user'] = $sessionUser;
                $_SESSION['user_id'] = $user['id'];

                return $this->redirectBasedOnRole($response, $user['role']);
            }

            throw new ValidationException(['account' => ['Tài khoản hoặc mật khẩu không chính xác.']], $data);
        } catch (ValidationException $e) {
            $firstError = 'Lỗi đăng nhập.';
            if (!empty($e->errors)) {
                $firstVal = reset($e->errors);
                $firstError = is_array($firstVal) ? reset($firstVal) : $firstVal;
            }

            return $response->render('auth/login', [
                'pageTitle' => 'Đăng nhập - NUN Express',
                'error' => $firstError,
                'old' => $data
            ]);
        }
    }

    public function showRegisterForm(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        return $response->render('auth/register', [
            'pageTitle' => 'Đăng ký - NUN Express'
        ]);
    }

    public function register(Request $request, Response $response)
    {
        $rawData = $request->getBody();
        $data = app_sanitize($rawData);

        // Không sanitize mật khẩu để tránh lỗi khi người dùng sử dụng ký tự (<, >)
        if (isset($rawData['password'])) $data['password'] = $rawData['password'];
        if (isset($rawData['password_confirm'])) $data['password_confirm'] = $rawData['password_confirm'];

        try {
            $rules = [
                'name' => 'required|max:100',
                'phone' => 'required|phone|unique:users,phone',
                'email' => 'email|max:150|unique:users,email',
                'password' => 'required|min:6',
                'password_confirm' => 'required|password_match:password',
            ];

            $data['role'] = 'user'; // Mặc định tất cả đăng ký mới đều là khách hàng (user)
            (new Validator($data))->validate($rules)->throw();

            return $this->createUserAndSendMail($response, $data, '');
        } catch (ValidationException $e) {
            return $response->render('auth/register', [
                'pageTitle' => 'Đăng ký - NUN Express',
                'errors' => $e->errors,
                'old' => $data
            ]);
        } catch (\RuntimeException $e) {
            return $response->render('auth/register', [
                'pageTitle' => 'Đăng ký - NUN Express',
                'errors' => ['general' => $e->getMessage()],
                'old' => $data
            ]);
        }
    }

    private function createUserAndSendMail(Response $response, array $data, string $vehicleImage = '')
    {
        $name = $data['name'];
        $email = $data['email'] ?? '';
        $phone = $data['phone'];
        $password = $data['password'];
        $role = $data['role'];

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();

        try {
            $userModel = new User();
            $newUserId = $userModel->create($name, $email, $phone, $hashedPassword, $role, $data['license_plate'] ?? null, $vehicleImage);
            if (!$newUserId) {
                throw new \Exception('Không thể khởi tạo tài khoản.');
            }

            if (!empty($email)) {
                $otp = random_int(100000, 999999);
                $userModel->setVerificationToken($newUserId, (string) $otp);

                $mailService = new MailService();
                $mailService->sendVerificationEmail($email, $name, (string) $otp);

                $db->commit();

                $_SESSION['verification_email'] = $email;
                $_SESSION['verification_otp'] = (string) $otp;
                $_SESSION['verification_otp_expires_at'] = time() + (15 * 60);
                $_SESSION['flash_info'] = 'Đăng ký thành công! Một mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra và xác thực.';
                return $response->redirect('/auth/verify');
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập bằng số điện thoại.';
            return $response->redirect('/login');
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Đã xảy ra lỗi: ' . $e->getMessage();
            return $response->redirect('/register');
        }
    }

    public function showVerifyForm(Request $request, Response $response)
    {
        if (empty($_SESSION['verification_email'])) {
            return $response->redirect('/register');
        }

        if (!empty($_SESSION['verification_otp_expires_at']) && time() > (int) $_SESSION['verification_otp_expires_at']) {
            unset($_SESSION['verification_email'], $_SESSION['verification_otp'], $_SESSION['verification_otp_expires_at']);
            $_SESSION['flash_error'] = 'Mã OTP xác thực đã hết hạn. Vui lòng đăng ký lại để nhận mã mới.';
            return $response->redirect('/register');
        }

        return $response->render('auth/verify', [
            'pageTitle' => 'Xác thực tài khoản',
            'email' => $_SESSION['verification_email']
        ]);
    }

    public function verify(Request $request, Response $response)
    {
        $data = $request->getBody();
        $otp = trim($data['otp'] ?? '');

        if (empty($_SESSION['verification_email'])) {
            return $response->redirect('/register');
        }

        if (!empty($_SESSION['verification_otp_expires_at']) && time() > (int) $_SESSION['verification_otp_expires_at']) {
            unset($_SESSION['verification_email'], $_SESSION['verification_otp'], $_SESSION['verification_otp_expires_at']);
            $_SESSION['flash_error'] = 'Mã OTP xác thực đã hết hạn. Vui lòng đăng ký lại để nhận mã mới.';
            return $response->redirect('/register');
        }

        $userModel = new User();
        $user = $userModel->findUserByToken($otp);

        if ($user && !empty($_SESSION['verification_otp']) && hash_equals((string) $_SESSION['verification_otp'], $otp)) {
            $userModel->markEmailAsVerified($user['id']);
            unset($_SESSION['verification_email'], $_SESSION['verification_otp'], $_SESSION['verification_otp_expires_at']);
            $_SESSION['flash_success'] = 'Xác thực tài khoản thành công! Bây giờ bạn có thể đăng nhập.';
            return $response->redirect('/login');
        }

        $_SESSION['flash_error'] = 'Mã OTP không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.';
        return $response->redirect('/auth/verify');
    }

    public function logout(Request $request, Response $response)
    {
        session_destroy();
        return $response->redirect('/login');
    }

    public function showForgotPasswordForm(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        return $response->render('auth/forgot-password', [
            'pageTitle' => 'Quên mật khẩu - NUN Express'
        ]);
    }

    public function requestOTP(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        $data = app_sanitize($request->getBody());
        $emailOrPhone = trim($data['email_or_phone'] ?? '');

        if (empty($emailOrPhone)) {
            return $response->render('auth/forgot-password', [
                'pageTitle' => 'Quên mật khẩu - NUN Express',
                'error' => 'Vui lòng nhập email hoặc số điện thoại',
                'old' => $data
            ]);
        }

        $passwordResetModel = new \App\Models\PasswordReset();
        $result = $passwordResetModel->generateAndSendOTP($emailOrPhone);

        if (!$result['success']) {
            return $response->render('auth/forgot-password', [
                'pageTitle' => 'Quên mật khẩu - NUN Express',
                'error' => $result['message'],
                'old' => $data
            ]);
        }

        $_SESSION['otp_user_id'] = $result['user_id'];
        $_SESSION['otp_email_hint'] = $result['email_hint'];
        $_SESSION['flash_success'] = $result['message'];

        return $response->redirect('/verify-otp');
    }

    public function showVerifyOTPForm(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        if (empty($_SESSION['otp_user_id'])) {
            return $response->redirect('/forgot-password');
        }

        return $response->render('auth/verify-otp', [
            'pageTitle' => 'Xác thực OTP - NUN Express',
            'email_hint' => $_SESSION['otp_email_hint'] ?? ''
        ]);
    }

    public function verifyOTP(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        if (empty($_SESSION['otp_user_id'])) {
            return $response->redirect('/forgot-password');
        }

        $data = app_sanitize($request->getBody());
        $otpCode = trim($data['otp'] ?? '');

        if (empty($otpCode)) {
            return $response->render('auth/verify-otp', [
                'pageTitle' => 'Xác thực OTP - NUN Express',
                'error' => 'Vui lòng nhập mã OTP',
                'email_hint' => $_SESSION['otp_email_hint'] ?? ''
            ]);
        }

        $passwordResetModel = new \App\Models\PasswordReset();
        $result = $passwordResetModel->verifyOTP((int) $_SESSION['otp_user_id'], $otpCode);

        if (!$result['success']) {
            return $response->render('auth/verify-otp', [
                'pageTitle' => 'Xác thực OTP - NUN Express',
                'error' => $result['message'],
                'email_hint' => $_SESSION['otp_email_hint'] ?? ''
            ]);
        }

        $_SESSION['reset_token'] = $result['reset_token'];
        $_SESSION['flash_success'] = $result['message'];

        return $response->redirect('/reset-password');
    }

    public function showResetPasswordForm(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        if (empty($_SESSION['reset_token']) || empty($_SESSION['otp_user_id'])) {
            return $response->redirect('/forgot-password');
        }

        return $response->render('auth/reset-password', [
            'pageTitle' => 'Đặt lại mật khẩu - NUN Express'
        ]);
    }

    public function resetPassword(Request $request, Response $response)
    {
        if (isset($_SESSION['user'])) {
            return $this->redirectBasedOnRole($response, $_SESSION['user']['role'] ?? 'user');
        }

        if (empty($_SESSION['reset_token']) || empty($_SESSION['otp_user_id'])) {
            return $response->redirect('/forgot-password');
        }

        $rawData = $request->getBody();
        $data = app_sanitize($rawData);

        // Giữ nguyên mật khẩu gốc
        if (isset($rawData['password'])) $data['password'] = $rawData['password'];
        if (isset($rawData['password_confirm'])) $data['password_confirm'] = $rawData['password_confirm'];

        try {
            (new Validator($data))->validate([
                'password' => 'required|min:6',
                'password_confirm' => 'required|password_match:password'
            ])->throw();

            $passwordResetModel = new \App\Models\PasswordReset();
            $result = $passwordResetModel->resetPassword(
                (int) $_SESSION['otp_user_id'],
                $_SESSION['reset_token'],
                $data['password']
            );

            if (!$result['success']) {
                return $response->render('auth/reset-password', [
                    'pageTitle' => 'Đặt lại mật khẩu - NUN Express',
                    'error' => $result['message']
                ]);
            }

            unset($_SESSION['otp_user_id'], $_SESSION['reset_token'], $_SESSION['otp_email_hint']);

            $_SESSION['flash_success'] = $result['message'] . ' Vui lòng đăng nhập với mật khẩu mới.';
            return $response->redirect('/login');
        } catch (ValidationException $e) {
            return $response->render('auth/reset-password', [
                'pageTitle' => 'Đặt lại mật khẩu - NUN Express',
                'errors' => $e->errors
            ]);
        }
    }

    private function redirectBasedOnRole(Response $response, string $role)
    {
        if ($role === 'driver') {
            return $response->redirect('/driver/active-orders');
        }
        if ($role === 'admin') {
            return $response->redirect('/admin/dashboard');
        }
        return $response->redirect('/user/dashboard');
    }
}
