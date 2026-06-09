<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;

class SettingController extends BaseController
{
    // Hiển thị và xử lý lưu cấu hình vận hành hệ thống.
    public function index(Request $request, Response $response)
    {
        $settingModel = new Setting();

        if ($request->isPost()) {
            $data = app_sanitize($request->getBody());

            $percentKeys = ['platform_fee_percent'];
            foreach ($percentKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = max(0, min(100, (float) $data[$key]));
                }
            }

            $positiveIntegerKeys = [
                'default_max_concurrent_orders',
                'max_orders_per_batch',
                'fast_max_orders',
                'no_show_threshold_for_ban',
                'violation_threshold_for_ban',
                'min_wallet_topup_amount',
                'min_wallet_withdraw_amount',
                'driver_pickup_timeout_minutes',
                'pending_order_auto_cancel_hours',
                'scheduled_order_visible_before_minutes',
            ];
            foreach ($positiveIntegerKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = max(1, (int) $data[$key]);
                }
            }

            $positiveFloatKeys = [
                'max_order_weight',
                'default_max_total_weight',
                'driver_radar_radius_km',
                'vehicle_speed_kmh',
            ];
            foreach ($positiveFloatKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = max(1, (float) $data[$key]);
                }
            }

            if (isset($data['auto_refund_on_system_cancel'])) {
                $data['auto_refund_on_system_cancel'] = (int) ((bool) $data['auto_refund_on_system_cancel']);
            }

            $textKeys = [
                'bank_id',
                'bank_name',
                'bank_account_no',
                'bank_account_name',
                'refund_processing_note',
            ];
            foreach ($textKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = trim((string) $data[$key]);
                }
            }

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
