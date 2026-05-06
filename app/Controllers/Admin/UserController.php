<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Core\Validator;
use App\Exceptions\ValidationException;

/**
 * Class UserController
 * Quản lý các chức năng liên quan đến người dùng trong vai trò Admin.
 */
class UserController extends BaseController
{
    /**
     * Helper xử lý upload ảnh giấy đăng ký xe
     */
    private function uploadVehicleImage(): string
    {
        if (isset($_FILES['vehicle_registration']) && $_FILES['vehicle_registration']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/vehicles/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['vehicle_registration']['name'], PATHINFO_EXTENSION);
            $filename = 'reg_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['vehicle_registration']['tmp_name'], $uploadDir . $filename)) {
                return '/uploads/vehicles/' . $filename;
            }
        }
        return '';
    }

    /**
     * Hiển thị danh sách người dùng.
     * Route: GET /admin/users
     */
    public function index(Request $request, Response $response)
    {
        // 1. Kiểm tra quyền truy cập (Chỉ Admin mới được vào)
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        // 2. Lấy bộ lọc và thông tin phân trang
        $query = $request->getBody();
        $roleFilter = trim($query['role'] ?? '');
        $search = trim($query['search'] ?? '');
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = 15; // Số người dùng mỗi trang
        $offset = ($page - 1) * $perPage;

        // 3. Lấy danh sách người dùng và tổng số người dùng từ Database
        $userModel = new User();
        $totalUsers = $userModel->countAll($roleFilter, $search);
        $users = $userModel->getAllWithDriverProfile($roleFilter, $perPage, $offset, $search);
        $totalPages = (int) ceil($totalUsers / $perPage);

        // 4. Render View và truyền dữ liệu
        return $response->render('admin/users/index', [
            'pageTitle' => 'Quản lý người dùng',
            'users' => $users,
            'roleFilter' => $roleFilter,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    /**
     * Xử lý hiển thị form và cập nhật người dùng.
     * Route: GET/POST /admin/users/edit/{id}
     */
    public function edit(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        // Lấy ID người dùng từ URI
        $id = (int) $request->getRouteParam('id');

        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            $_SESSION['flash_error'] = 'Không tìm thấy người dùng này trên hệ thống!';
            return $response->redirect('/admin/users');
        }

        // Không cho phép sửa tài khoản Quản trị viên khác (chỉ được sửa chính mình)
        if ($user['role'] === 'admin' && (int) $user['id'] !== $this->userId()) {
            $_SESSION['flash_error'] = 'Bạn không có quyền chỉnh sửa tài khoản Quản trị viên khác.';
            return $response->redirect('/admin/users');
        }

        // Xử lý khi Submit Form cập nhật
        if ($request->isPost()) {
            $data = app_sanitize($request->getBody());
            $role = $data['role'] ?? 'user';

            // Không cho phép đổi thành admin nếu không phải là admin
            if ($role === 'admin' && $user['role'] !== 'admin') {
                $_SESSION['flash_error'] = 'Bạn không thể cấp quyền Quản trị viên cho người dùng này.';
                return $response->redirect("/admin/users/edit/{$id}");
            }
            // Không cho phép hạ quyền nếu tài khoản đang là admin
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

                // Tránh gọi rule 'unique' có loại trừ ID để không dính lỗi SQL Syntax của core Validator
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
                    'role' => $role
                ];
                if (isset($data['no_show_count'])) {
                    $updateData['no_show_count'] = (int) $data['no_show_count'];
                }
                
                // Truyền thêm dữ liệu tài xế nếu có để Model xử lý (Chuẩn MVC)
                if ($role === 'driver') {
                    $updateData['max_concurrent_orders'] = $data['max_concurrent_orders'] ?? null;
                    $updateData['max_total_weight'] = $data['max_total_weight'] ?? null;
                    $updateData['license_plate'] = $data['license_plate'] ?? '';
                    $updateData['is_driver_verified'] = isset($data['is_driver_verified']) ? 1 : 0;
                    if (isset($data['balance'])) {
                        $updateData['balance'] = $data['balance'];
                    }
                    
                    $vehicleImage = $this->uploadVehicleImage();
                    if (!empty($vehicleImage)) {
                        $updateData['vehicle_registration_image'] = $vehicleImage;
                    }
                }

                if ($userModel->update($id, $updateData)) {
                    $_SESSION['flash_success'] = 'Cập nhật thông tin người dùng thành công!';
                    return $response->redirect('/admin/users');
                }
                
                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi lưu vào cơ sở dữ liệu. Vui lòng thử lại.';
            } catch (ValidationException $e) {
                $_SESSION['flash_error'] = implode('. ', $e->errors);
                // Ghi đè dữ liệu cũ bằng dữ liệu mới nhập để hiển thị lại trên form nếu có lỗi
                $user = array_merge($user, $data);
            }
        }

        // Lấy thông tin cấu hình tài xế để hiển thị ra form
        $driverProfile = null;
        if ($user['role'] === 'driver') {
            $driverProfile = $userModel->getDriverProfile($id);
        }

        return $response->render('admin/users/edit', [
            'pageTitle' => 'Chỉnh sửa người dùng',
            'user' => $user,
            'driverProfile' => $driverProfile
        ]);
    }

    /**
     * Xóa hoặc Khóa tài khoản.
     * Route: GET /admin/users/block/{id} hoặc /admin/users/unblock/{id}
     */
    public function toggleBlock(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $id = (int) $request->getRouteParam('id');
        // Không cho phép tự khóa tài khoản
        if ($id === $this->userId()) {
            $_SESSION['flash_error'] = 'Bạn không thể tự khóa tài khoản của mình.';
            return $response->redirect('/admin/users');
        }

        $path = $request->getPath(); // Ví dụ: /admin/users/block/5
        // Ưu tiên kiểm tra 'unblock' trước vì 'unblock' có chứa chuỗi 'block'
        $action = strpos($path, '/unblock/') !== false ? 'unblock' : 'block';

        if ($id > 0 && in_array($action, ['block', 'unblock'])) {
            $isBlocked = ($action === 'block') ? 1 : 0;
            $userModel = new User();

            $targetUser = $userModel->findById($id);
            if ($targetUser && $isBlocked === 1 && $targetUser['role'] === 'admin') {
                $_SESSION['flash_error'] = 'Không thể khóa tài khoản có vai trò Quản trị viên.';
                return $response->redirect('/admin/users');
            }

            if ($userModel->updateBlockStatus($id, $isBlocked)) {
                // Reset lại số lần bom hàng về 0 khi Admin quyết định mở khóa tài khoản
                if ($isBlocked === 0) {
                    $userModel->resetNoShowCount($id);
                }
                $_SESSION['flash_success'] = $isBlocked ? 'Đã khóa tài khoản thành công.' : 'Đã mở khóa tài khoản thành công.';
            } else {
                $_SESSION['flash_error'] = 'Có lỗi xảy ra, không thể thay đổi trạng thái.';
            }
        }

        // Thêm tham số thời gian để tránh trình duyệt lưu cache trạng thái cũ
        return $response->redirect('/admin/users?t=' . time());
    }

    /**
     * Xử lý hiển thị form và tạo người dùng mới.
     * Route: GET/POST /admin/users/create
     */
    public function create(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        if ($request->isPost()) {
            $data = app_sanitize($request->getBody());
            $role = $data['role'] ?? 'user';

            // Không cho phép tạo tài khoản Admin
            if ($role === 'admin') {
                $_SESSION['flash_error'] = 'Không được phép tạo mới tài khoản Quản trị viên.';
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

                $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                $createData = [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'password' => $hashedPassword,
                    'role' => $data['role'],
                    'license_plate' => $data['license_plate'] ?? null
                ];
                
                if ($createData['role'] === 'driver') {
                    $createData['vehicle_registration_image'] = $this->uploadVehicleImage();
                }
                
                $userModel = new User();
                $userModel->createWithDriverProfile($createData);
                $_SESSION['flash_success'] = 'Tạo người dùng mới thành công!';
                return $response->redirect('/admin/users');
            }
            catch (ValidationException $e) {
                $_SESSION['flash_error'] = implode('. ', $e->errors);
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'Lỗi hệ thống CSDL: ' . $e->getMessage();
            }
        }

        return $response->render('admin/users/create', [
            'pageTitle' => 'Thêm người dùng mới'
        ]);
    }
}
