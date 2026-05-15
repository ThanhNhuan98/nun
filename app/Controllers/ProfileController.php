<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;
use App\Models\User;
use App\Core\Validator;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

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
            // Tạo một view riêng cho trường hợp không tìm thấy user
            return $this->notFound($response);
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
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            try {
                // Cấu hình Cloudinary từ biến môi trường .env
                Configuration::instance($_ENV['CLOUDINARY_URL']);

                $uploadApi = new UploadApi();
                
                // Upload file trực tiếp lên thư mục 'nun_express/avatars'
                $result = $uploadApi->upload($_FILES['avatar']['tmp_name'], [
                    'folder' => 'nun_express/avatars',
                    'transformation' => [
                        'width' => 400, 'height' => 400, 'crop' => 'fill' // Tự động crop vuông
                    ]
                ]);

                $avatarUrl = $result['secure_url'];
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
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

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
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

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
            'phone' => 'required|phone|unique:users,phone,id'
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
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

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
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $userId = $this->userId();
        $userModel = new User();
        $currentUser = $userModel->findById($userId);

        // Chỉ cho phép "user" đăng ký
        if ($currentUser['role'] !== 'user') {
            $_SESSION['flash_error'] = 'Tài khoản của bạn đã là tài xế hoặc không được phép đăng ký.';
            return $response->redirect('/profile/' . $userId);
        }

        $data = $request->getBody();
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
        if (isset($_FILES['vehicle_registration']) && $_FILES['vehicle_registration']['error'] === UPLOAD_ERR_OK) {
            $validation = app_validate_uploaded_image($_FILES['vehicle_registration']);
            if (!$validation['valid']) {
                throw new \RuntimeException($validation['error'] ?? 'Ảnh giấy đăng ký xe không hợp lệ.');
            }
            
            try {
                Configuration::instance($_ENV['CLOUDINARY_URL']);
                $uploadApi = new UploadApi();
                $result = $uploadApi->upload($_FILES['vehicle_registration']['tmp_name'], [
                    'folder' => 'nun_express/vehicles',
                ]);
                return $result['secure_url'];
            } catch (\Exception $e) {
                throw new \RuntimeException('Lỗi upload ảnh lên Cloudinary: ' . $e->getMessage());
            }
        }
        throw new \RuntimeException('Vui lòng tải lên ảnh giấy đăng ký xe (Cavet).');
    }
}
