<?php

namespace App\Services;

use App\Models\Setting;

class ShippingFeeService
{

    // Báo giá cước phí vận chuyển tổng hợp (Kích hoạt AI nếu đủ tọa độ, ngược lại dùng phí dự phòng).
    public function quote(array $data): array
    {
        $missing = $this->missingCoordinates($data);
        if (!empty($missing)) {
            $fallbackFee = $this->fallbackFee($data);

            return [
                'success' => false,
                'message' => 'Chưa đủ tọa độ.',
                'shipping_fee' => $fallbackFee,
                'base_fee' => $fallbackFee,
                'surge_fee' => 0,
            ];
        }

        $result = $this->runOptimizer($data);
        if ($this->isSuccessfulResult($result)) {
            return [
                'success' => true,
                'shipping_fee' => (float) $result['shipping_fee'],
                'base_fee' => (float) ($result['base_fee'] ?? $result['shipping_fee']),
                'surge_fee' => (float) ($result['surge_fee'] ?? 0),
                'distance_km' => $result['distance_km'] ?? null,
                'duration_minutes' => $result['duration_minutes'] ?? null,
                'surge_label' => $result['surge_label'] ?? 'Bình thường',
                'surge_multiplier' => $result['surge_multiplier'] ?? 1,
            ];
        }

        $fallbackFee = $this->fallbackFee($data);

        return [
            'success' => false,
            'message' => $this->resolveErrorMessage($result),
            'shipping_fee' => $fallbackFee,
            'base_fee' => $fallbackFee,
            'surge_fee' => 0,
        ];
    }

    // Kiểm tra xem mảng dữ liệu có bị thiếu bất kỳ tọa độ Lấy/Giao hàng nào không.
    private function missingCoordinates(array $data): array
    {
        $required = ['sender_lat', 'sender_lng', 'receiver_lat', 'receiver_lng'];

        return array_values(array_filter($required, static function ($key) use ($data) {
            return empty($data[$key]);
        }));
    }

    // Gọi API FastAPI để tính khoảng cách và thời gian di chuyển.
    private function runOptimizer(array $data): array
    {
        $serviceType = $this->resolveServiceType($data);
        $settingModel = new Setting();
        
        $pricingConfig = [
            'standard' => [
                'base' => (float) $settingModel->get('price_standard_base', 12000),
                'weight' => (float) $settingModel->get('price_standard_weight', 5000),
                'distance' => (float) $settingModel->get('price_standard_distance', 3000),
            ],
            'fast' => [
                'base' => (float) $settingModel->get('price_fast_base', 18000),
                'weight' => (float) $settingModel->get('price_fast_weight', 6200),
                'distance' => (float) $settingModel->get('price_fast_distance', 3800),
            ],
            'express' => [
                'base' => (float) $settingModel->get('price_express_base', 25000),
                'weight' => (float) $settingModel->get('price_express_weight', 7500),
                'distance' => (float) $settingModel->get('price_express_distance', 4800),
            ],
        ];

        $payload = [
            'sender_lat' => (float) $data['sender_lat'],
            'sender_lng' => (float) $data['sender_lng'],
            'receiver_lat' => (float) $data['receiver_lat'],
            'receiver_lng' => (float) $data['receiver_lng'],
            'weight' => (float) ($data['weight'] ?? 1.0),
            'service_type' => $serviceType,
            'scheduled_at' => $data['scheduled_at'] ?? '',
            'pricing' => $pricingConfig,
            'vehicle_speed' => (float) $settingModel->get('vehicle_speed_kmh', 28.0)
        ];

        $apiUrl = $_ENV['AI_FEE_SERVICE_URL'] ?? 'http://127.0.0.1:8000/api/v1/calculate-fee';
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $output = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'status' => 'error',
                'message' => 'Lỗi kết nối FastAPI: ' . $error,
            ];
        }
        curl_close($ch);

        $decoded = json_decode((string) $output, true);

        if (is_array($decoded)) {
            $decoded['_raw_output'] = $output;
            return $decoded;
        }

        return [
            'status' => 'error',
            'message' => 'Không có phản hồi hợp lệ từ Python API.',
            '_raw_output' => $output,
        ];
    }

    // Kiểm tra chuỗi phản hồi từ Python AI có thành công và chứa cước phí hay không.
    private function isSuccessfulResult(array $result): bool
    {
        return ($result['status'] ?? '') === 'success' && isset($result['shipping_fee']);
    }

    // Trích xuất và định dạng thông báo lỗi từ kết quả trả về của script Python.
    private function resolveErrorMessage(array $result): string
    {
        if (!empty($result['message'])) {
            return 'Lỗi AI: ' . $result['message'];
        }

        if (!empty($result['_raw_output'])) {
            return 'Lỗi AI: ' . trim((string) $result['_raw_output']);
        }

        return 'Lỗi AI: Không có phản hồi từ Python.';
    }

    // Lọc và trả về loại hình dịch vụ hợp lệ (standard, fast, express).
    private function resolveServiceType(array $data): string
    {
        $serviceType = (string) ($data['service_type'] ?? $data['shipping_method'] ?? 'standard');

        if (in_array($serviceType, ['standard', 'fast', 'express'], true)) {
            return $serviceType;
        }

        return 'standard';
    }

    // Lấy mức phí dự phòng cố định theo từng loại hình dịch vụ (Dùng khi AI gặp sự cố).
    private function fallbackFee(array $data): float
    {
        $method = $this->resolveServiceType($data);
        $settingModel = new Setting();
        
        $fallback = 25000;
        if ($method === 'standard') {
            $fallback = (float) $settingModel->get('price_standard_base', 12000);
        } elseif ($method === 'fast') {
            $fallback = (float) $settingModel->get('price_fast_base', 18000);
        } elseif ($method === 'express') {
            $fallback = (float) $settingModel->get('price_express_base', 25000);
        }

        return $fallback;
    }
}
