<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Models\User;

class UserController extends BaseController
{
    private function uploadVehicleImage(): string
    {
        if (!isset($_FILES['vehicle_registration']) || $_FILES['vehicle_registration']['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $validation = app_validate_uploaded_image($_FILES['vehicle_registration']);
        if (!$validation['valid']) {
            throw new \RuntimeException($validation['error'] ?? 'Ảnh giấy đăng ký xe không hợp lệ.');
        }

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/vehicles/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $ext = $validation['extension'] ?? 'jpg';
        $filename = 'reg_' . time() . '_' . uniqid('', true) . '.' . $ext;

        if (!move_uploaded_file($_FILES['vehicle_registration']['tmp_name'], $uploadDir . $filename)) {
            throw new \RuntimeException('Không thể lưu ảnh giấy đăng ký xe.');
        }

        return '/uploads/vehicles/' . $filename;
    }

    public function index(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $query = $request->getBody();
        $roleFilter = trim($query['role'] ?? '');
        $search = trim($query['search'] ?? '');
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $userModel = new User();
        $totalUsers = $userModel->countAll($roleFilter, $search);
        $users = $userModel->getAllWithDriverProfile($roleFilter, $perPage, $offset, $search);
        $totalPages = (int) ceil($totalUsers / $perPage);

        return $response->render('admin/users/index', [
            'pageTitle' => 'Quản lý người dùng',
            'users' => $users,
            'roleFilter' => $roleFilter,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function edit(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $id = (int) $request->getRouteParam('id');

        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            $_SESSION['flash_error'] = 'Không tìm thấy người dùng này trên hệ thống.';
            return $response->redirect('/admin/users');
        }

        if ($user['role'] === 'admin' && (int) $user['id'] !== $this->userId()) {
            $_SESSION['flash_error'] = 'Bạn không có quyền chỉnh sửa tài khoản quản trị viên khác.';
            return $response->redirect('/admin/users');
        }

        if ($request->isPost()) {
            $data = app_sanitize($request->getBody());
            $role = $data['role'] ?? 'user';

            if ($role === 'admin' && $user['role'] !== 'admin') {
                $_SESSION['flash_error'] = 'Bạn không có quyền chỉnh sửa tài khoản quản trị viên.';
                return $response->redirect("/admin/users/edit/{$id}");
            }

            if ($user['role'] === 'admin') {
                $role = 'admin';
            }

            try {
                $rules = [
                    'name' => 'required|max:100',
                    'role' => 'required|in:user,driver,admin',
                ];

                if ($role === 'driver') {
                    $rules['license_plate'] = 'required|max:20';
                }

                $rules['phone'] = ($data['phone'] !== $user['phone'])
                    ? 'required|phone|unique:users,phone'
                    : 'required|phone';

                $rules['email'] = (($data['email'] ?? '') !== ($user['email'] ?? ''))
                    ? 'email|max:150|unique:users,email'
                    : 'email|max:150';

                (new Validator($data))->validate($rules)->throw();

                $updateData = [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'role' => $role,
                ];

                if (isset($data['no_show_count'])) {
                    $updateData['no_show_count'] = (int) $data['no_show_count'];
                }

                if ($role === 'driver') {
                    $updateData['max_concurrent_orders'] = $data['max_concurrent_orders'] ?? null;
                    $updateData['max_total_weight'] = $data['max_total_weight'] ?? null;
                    $updateData['license_plate'] = $data['license_plate'] ?? '';
                    $updateData['is_driver_verified'] = isset($data['is_driver_verified']) ? 1 : 0;

                    if (isset($data['balance'])) {
                        $updateData['balance'] = $data['balance'];
                    }

                    $vehicleImage = $this->uploadVehicleImage();
                    if ($vehicleImage !== '') {
                        $updateData['vehicle_registration_image'] = $vehicleImage;
                    }
                }

                if ($userModel->update($id, $updateData)) {
                    $_SESSION['flash_success'] = 'Cập nhật thông tin người dùng thành công.';
                    return $response->redirect('/admin/users');
                }

                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi lưu vào cơ sở dữ liệu. Vui lòng thử lại.';
            } catch (ValidationException | \RuntimeException $e) {
                $_SESSION['flash_error'] = $e instanceof ValidationException ? implode('. ', $e->errors) : $e->getMessage();
                $user = array_merge($user, $data);
            }
        }

        $driverProfile = null;
        if ($user['role'] === 'driver') {
            $driverProfile = $userModel->getDriverProfile($id);
        }

        return $response->render('admin/users/edit', [
            'pageTitle' => 'Chỉnh sửa người dùng',
            'user' => $user,
            'driverProfile' => $driverProfile,
        ]);
    }

    public function toggleBlock(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $id = (int) $request->getRouteParam('id');
        if ($id === $this->userId()) {
            $_SESSION['flash_error'] = 'Bạn không thể tự khóa tài khoản của mình.';
            return $response->redirect('/admin/users');
        }

        $path = $request->getPath();
        $action = strpos($path, '/unblock/') !== false ? 'unblock' : 'block';

        if ($id > 0 && in_array($action, ['block', 'unblock'], true)) {
            $isBlocked = $action === 'block' ? 1 : 0;
            $userModel = new User();

            $targetUser = $userModel->findById($id);
            if ($targetUser && $isBlocked === 1 && $targetUser['role'] === 'admin') {
                $_SESSION['flash_error'] = 'Không thể khóa tài khoản có vai trò quản trị viên.';
                return $response->redirect('/admin/users');
            }

            if ($userModel->updateBlockStatus($id, $isBlocked)) {
                if ($isBlocked === 0) {
                    $userModel->resetNoShowCount($id);
                }

                $_SESSION['flash_success'] = $isBlocked
                    ? 'Đã khóa tài khoản thành công.'
                    : 'Đã mở khóa tài khoản thành công.';
            } else {
                $_SESSION['flash_error'] = 'Có lỗi xảy ra, không thể thay đổi trạng thái.';
            }
        }

        return $response->redirect('/admin/users?t=' . time());
    }

    public function create(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        if ($request->isPost()) {
            $data = app_sanitize($request->getBody());
            $role = $data['role'] ?? 'user';

            if ($role === 'admin') {
                $_SESSION['flash_error'] = 'Không được phép tạo mới tài khoản quản trị viên.';
                return $response->redirect('/admin/users/create');
            }

            try {
                $rules = [
                    'name' => 'required|max:100',
                    'phone' => 'required|phone|unique:users,phone',
                    'email' => 'email|max:150|unique:users,email',
                    'role' => 'required|in:user,driver',
                    'password' => 'required|min:6',
                ];

                if ($role === 'driver') {
                    $rules['license_plate'] = 'required|max:20';
                }

                (new Validator($data))->validate($rules)->throw();

                $createData = [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                    'role' => $data['role'],
                    'license_plate' => $data['license_plate'] ?? null,
                ];

                if ($createData['role'] === 'driver') {
                    $createData['vehicle_registration_image'] = $this->uploadVehicleImage();
                }

                $userModel = new User();
                $userModel->createWithDriverProfile($createData);

                $_SESSION['flash_success'] = 'Tạo người dùng mới thành công.';
                return $response->redirect('/admin/users');
            } catch (ValidationException | \RuntimeException $e) {
                $_SESSION['flash_error'] = $e instanceof ValidationException ? implode('. ', $e->errors) : $e->getMessage();
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Lỗi hệ thống CSDL: ' . $e->getMessage();
            }
        }

        return $response->render('admin/users/create', [
            'pageTitle' => 'Thêm người dùng mới',
        ]);
    }
}
