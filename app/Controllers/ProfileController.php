<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;
use App\Models\User;
use App\Core\Validator;

class ProfileController extends BaseController
{
    public function show(Request $request, Response $response)
    {
        $targetId = (int) $request->getRouteParam('id');
        if (!$targetId) {
            $response->setStatusCode(404);
            return $response->render('layouts/404');
        }

        $userModel = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $response->setStatusCode(404);
            return $response->render('layouts/404');
        }

        $ratingInfo = null;
        $reviews = [];
        if ($targetUser['role'] === 'driver') {
            $ratingInfo = $userModel->getDriverRating($targetId);
            $reviews = $userModel->getDriverReviews($targetId);
        }

        $driverProfile = $userModel->getDriverProfile($targetId);

        // Lấy role của người dùng đang đăng nhập để hiển thị sidebar phù hợp
        $currentUserRole = $this->currentUser('role', 'user');

        return $response->render('profile/show', [
            'pageTitle' => 'Hồ sơ - ' . $targetUser['name'],
            'targetUser' => $targetUser,
            'ratingInfo' => $ratingInfo,
            'reviews' => $reviews,
            'driverProfile' => $driverProfile,
            'currentUserRole' => $currentUserRole
        ]);
    }

    /**
     * Xử lý upload và cập nhật Avatar bằng Cloudinary
     * Route: POST /profile/update-avatar
     */
    public function updateAvatar(Request $request, Response $response)
    {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            try {
                // KIỂM TRA TÍNH HỢP LỆ (Tránh user upload file mã độc đổi đuôi .jpg)
                $validation = app_validate_uploaded_image($_FILES['avatar']);
                if (!$validation['valid']) {
                    throw new \Exception($validation['error'] ?? 'Ảnh không hợp lệ.');
                }

                // Nén ảnh cục bộ trước khi đẩy lên đường truyền mạng tới Cloudinary
                if (function_exists('app_compress_image_before_upload')) {
                    app_compress_image_before_upload($_FILES['avatar']['tmp_name'], 800, 85);
                }

                $avatarUrl = $this->uploadToCloudinary($_FILES['avatar'], 'nun_express/avatars', null, [
                    'transformation' => ['width' => 400, 'height' => 400, 'crop' => 'fill']
                ]);
                
                $userId = $this->userId();

                $userModel = new User();
                $userModel->updateAvatar($userId, $avatarUrl);

                // Cập nhật lại session để giao diện đổi ảnh ngay lập tức
                $_SESSION['user']['avatar'] = $avatarUrl;
                $_SESSION['flash_success'] = 'Cập nhật ảnh đại diện thành công!';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }
        
        return $response->redirect('/profile/' . $this->userId());
    }

    /**
     * Xử lý đổi mật khẩu
     * Route: POST /profile/change-password
     */
    public function changePassword(Request $request, Response $response)
    {
        $data = $request->getBody();
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        $validator = new Validator($data);
        $validator->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|password_match:new_password'
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = implode(' ', $validator->getErrors());
            return $response->redirect('/profile/' . $this->userId());
        }

        $userModel = new User();
        $user = $userModel->findByIdWithPassword($this->userId());

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['flash_error'] = 'Mật khẩu hiện tại không chính xác.';
            return $response->redirect('/profile/' . $this->userId());
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        if ($userModel->updatePassword($this->userId(), $hashedPassword)) {
            $_SESSION['flash_success'] = 'Đổi mật khẩu thành công!';
        } else {
            $_SESSION['flash_error'] = 'Có lỗi xảy ra khi đổi mật khẩu.';
        }

        return $response->redirect('/profile/' . $this->userId());
    }

    /**
     * Xử lý cập nhật thông tin cá nhân (Tên, Số điện thoại)
     * Route: POST /profile/update-info
     */
    public function updateInfo(Request $request, Response $response)
    {
        $data = $request->getBody();
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $licensePlate = trim($data['license_plate'] ?? '');
        $userId = $this->userId();

        if (empty($name) || empty($phone)) {
            $_SESSION['flash_error'] = 'Họ tên và số điện thoại không được để trống.';
            return $response->redirect('/profile/' . $userId);
        }

        $userModel = new User();
        $currentUser = $userModel->findById($userId);

        $rules = [
            'name' => 'required|max:100',
            'phone' => ($phone !== $currentUser['phone']) ? 'required|phone|unique:users,phone' : 'required|phone'
        ];
        
        if ($currentUser['role'] === 'driver') {
            $rules['license_plate'] = 'required|max:20';
        }

        // Sử dụng lớp Validator của hệ thống để kiểm tra dữ liệu
        $validator = new Validator([
            'name' => $name,
            'phone' => $phone,
            'id' => $userId,
            'license_plate' => $licensePlate
        ]);
        
        $validator->validate($rules);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = implode(' ', $validator->getErrors());
            return $response->redirect('/profile/' . $userId);
        }

        $updateData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $currentUser['email'],
            'role' => $currentUser['role'],
            'license_plate' => $licensePlate
        ];

        if ($userModel->update($userId, $updateData)) {
            $_SESSION['user']['name'] = $name; // Cập nhật session để Navbar đổi tên ngay lập tức
            $_SESSION['flash_success'] = 'Cập nhật thông tin cá nhân thành công!';
        } else {
            $_SESSION['flash_error'] = 'Có lỗi xảy ra khi cập nhật thông tin.';
        }

        return $response->redirect('/profile/' . $userId);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     * Route: GET /notifications/read
     */
    public function readAllNotifications(Request $request, Response $response)
    {
        $notificationModel = new Notification();
        $notificationModel->markAllAsRead($this->userId());

        // Trở lại trang hiện tại sau khi đánh dấu
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $response->redirect($referer);
    }

    /**
     * Khách hàng đăng ký nâng cấp lên tài xế
     * Route: POST /profile/register-driver
     */
    public function registerDriver(Request $request, Response $response)
    {
        $userId = $this->userId();
        $userModel = new User();
        $currentUser = $userModel->findById($userId);

        // Chỉ cho phép "user" đăng ký
        if ($currentUser['role'] !== 'user') {
            $_SESSION['flash_error'] = 'Tài khoản của bạn đã là tài xế hoặc không được phép đăng ký.';
            return $response->redirect('/profile/' . $userId);
        }

        $data = $request->getBody();
        $acceptRules = isset($data['accept_rules']) ? (bool) $data['accept_rules'] : false;

        if (!$acceptRules) {
            $_SESSION['flash_error'] = 'Bạn phải đọc và đồng ý với các Quy tắc vận hành của nền tảng trước khi đăng ký làm tài xế.';
            return $response->redirect('/profile/' . $userId);
        }
        
        $licensePlate = trim($data['license_plate'] ?? '');

        if (empty($licensePlate)) {
            $_SESSION['flash_error'] = 'Vui lòng nhập biển số xe.';
            return $response->redirect('/profile/' . $userId);
        }

        try {
            $vehicleImage = $this->uploadVehicleImage();

            if ($userModel->upgradeToDriver($userId, $licensePlate, $vehicleImage)) {
                $_SESSION['flash_success'] = 'Đã gửi yêu cầu đăng ký làm tài xế! Vui lòng đợi Quản trị viên xét duyệt.';

                // Gửi thông báo Push cho tất cả Admin
                (new Notification())->notifyAdmins(
                    'Yêu cầu đăng ký tài xế mới',
                    "Khách hàng {$currentUser['name']} vừa gửi yêu cầu nâng cấp lên Tài xế (Biển số: {$licensePlate}).",
                    'system',
                    "/admin/users/edit/{$userId}"
                );
            } else {
                $_SESSION['flash_error'] = 'Có lỗi xảy ra trong quá trình đăng ký.';
            }
        } catch (\RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        return $response->redirect('/profile/' . $userId);
    }

    private function uploadVehicleImage(): string
    {
        if (isset($_FILES['vehicle_registration'])) {
            $errCode = $_FILES['vehicle_registration']['error'];
            
            if ($errCode === UPLOAD_ERR_OK) {
                $validation = app_validate_uploaded_image($_FILES['vehicle_registration']);
                if (!$validation['valid']) {
                    throw new \RuntimeException($validation['error'] ?? 'Ảnh hồ sơ không hợp lệ.');
                }
                
                // Tự động nén ảnh để upload nhẹ và nhanh hơn
                if (function_exists('app_compress_image_before_upload')) {
                    app_compress_image_before_upload($_FILES['vehicle_registration']['tmp_name']);
                }

                return $this->uploadToCloudinary($_FILES['vehicle_registration'], 'nun_express/vehicles', 'reg_' . time() . '_' . uniqid());
            } elseif ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                throw new \RuntimeException('Kích thước ảnh quá lớn (vượt giới hạn máy chủ). Vui lòng chọn ảnh có dung lượng nhỏ hơn.');
            }
        }
        
        throw new \RuntimeException('Vui lòng tải lên ảnh Hồ sơ tổng hợp hợp lệ (CCCD, Bằng lái, Cà vẹt).');
    }
}
