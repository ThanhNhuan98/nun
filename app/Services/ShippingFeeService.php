<?php

namespace App\Services;

class ShippingFeeService
{
    private const FALLBACK_FEE = 25000;

    public function quote(array $data): array
    {
        $missing = $this->missingCoordinates($data);
        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => 'Chưa đủ tọa độ.',
                'shipping_fee' => self::FALLBACK_FEE,
            ];
        }

        $result = $this->runOptimizer($data);
        if ($this->isSuccessfulResult($result)) {
            return [
                'success' => true,
                'shipping_fee' => (float) $result['shipping_fee'],
                'distance_km' => $result['distance_km'] ?? null,
                'surge_label' => $result['surge_label'] ?? 'Bình thường',
                'surge_multiplier' => $result['surge_multiplier'] ?? 1,
            ];
        }

        return [
            'success' => false,
            'message' => $this->resolveErrorMessage($result),
            'shipping_fee' => self::FALLBACK_FEE,
        ];
    }

    public function feeOrFallback(array $data): float
    {
        $quote = $this->quote($data);
        return (float) ($quote['shipping_fee'] ?? self::FALLBACK_FEE);
    }

    private function missingCoordinates(array $data): array
    {
        $required = ['sender_lat', 'sender_lng', 'receiver_lat', 'receiver_lng'];
        return array_values(array_filter($required, static function ($key) use ($data) {
            return empty($data[$key]);
        }));
    }

    private function runOptimizer(array $data): array
    {
        $scriptPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ai' . DIRECTORY_SEPARATOR . 'route_optimizer.py';
        $python = $_ENV['PYTHON_BIN'] ?? 'python';

        $args = [
            $data['sender_lat'],
            $data['sender_lng'],
            $data['receiver_lat'],
            $data['receiver_lng'],
            $data['weight'] ?? 1.0,
            $data['service_type'] ?? 'standard',
            $data['scheduled_at'] ?? '',
        ];

        $command = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' .
            implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';

        $output = shell_exec($command);
        $decoded = json_decode((string) $output, true);

        if (is_array($decoded)) {
            $decoded['_raw_output'] = $output;
            return $decoded;
        }

        return [
            'status' => 'error',
            'message' => trim((string) $output) ?: 'Không có phản hồi từ Python.',
            '_raw_output' => $output,
        ];
    }

    private function isSuccessfulResult(array $result): bool
    {
        return ($result['status'] ?? '') === 'success' && isset($result['shipping_fee']);
    }

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
}
