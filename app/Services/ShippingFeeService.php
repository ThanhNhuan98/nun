<?php

namespace App\Services;

class ShippingFeeService
{
    private const FALLBACK_FEE = 25000;
    private const METHOD_FALLBACK_FEES = [
        'standard' => 25000,
        'fast' => 40000,
        'express' => 55000,
    ];

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
        $serviceType = $this->resolveServiceType($data);

        $args = [
            $data['sender_lat'],
            $data['sender_lng'],
            $data['receiver_lat'],
            $data['receiver_lng'],
            $data['weight'] ?? 1.0,
            $serviceType,
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

    private function resolveServiceType(array $data): string
    {
        $serviceType = (string) ($data['service_type'] ?? $data['shipping_method'] ?? 'standard');

        if (in_array($serviceType, ['standard', 'fast', 'express'], true)) {
            return $serviceType;
        }

        return 'standard';
    }

    private function fallbackFee(array $data): float
    {
        $method = $this->resolveServiceType($data);
        return (float) (self::METHOD_FALLBACK_FEES[$method] ?? self::FALLBACK_FEE);
    }
}
