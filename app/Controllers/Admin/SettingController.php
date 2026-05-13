<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;

class SettingController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'admin')) {
            return $redirect;
        }

        $settingModel = new Setting();

        if ($request->isPost()) {
            $data = $request->getBody();
            
            // Lọc và ràng buộc giá trị phần trăm (từ 0 đến 100)
            if (isset($data['platform_fee_percent'])) {
                $data['platform_fee_percent'] = max(0, min(100, (float) $data['platform_fee_percent']));
            }
            
            // Ràng buộc số lần bom hàng tối thiểu là 1
            if (isset($data['no_show_threshold_for_ban'])) {
                $data['no_show_threshold_for_ban'] = max(1, (int) $data['no_show_threshold_for_ban']);
            }

            if (isset($data['max_order_weight'])) {
                $data['max_order_weight'] = max(1, (float) $data['max_order_weight']);
            }

            // Bọc trong transaction để đảm bảo an toàn
            $db = Database::getInstance();
            $db->beginTransaction();
            try {
                $settingModel->update($data);
                $db->commit();
                $_SESSION['flash_success'] = 'Cập nhật cài đặt thành công!';
            } catch (\Exception $e) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi cập nhật cài đặt.';
            }
            return $response->redirect('/admin/settings');
        }

        $settings = $settingModel->getAll();

        return $response->render('admin/settings/index', [
            'pageTitle' => 'Cài đặt hệ thống',
            'settings' => $settings
        ]);
    }
}
