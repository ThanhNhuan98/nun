<?php

namespace App\Services;

class OsrmService
{
    private const OSRM_BASE_URL = 'https://router.project-osrm.org';

    /**
     * Lấy thông tin lộ trình (khoảng cách, thời gian) giữa 2 điểm.
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return array
     */
    public function getRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $coords = "{$lon1},{$lat1};{$lon2},{$lat2}";
        $url = self::OSRM_BASE_URL . "/route/v1/driving/{$coords}?overview=false";

        try {
            // Sử dụng context để set timeout, tránh chờ đợi quá lâu
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return $this->fallbackRoute($lat1, $lon1, $lat2, $lon2);
            }

            $payload = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($payload['routes'])) {
                return $this->fallbackRoute($lat1, $lon1, $lat2, $lon2);
            }

            $route = $payload['routes'][0];
            return [
                'distance_km' => (float) ($route['distance'] ?? 0) / 1000,
                'duration_s' => (int) ($route['duration'] ?? 0),
                'source' => 'osrm',
            ];
        } catch (\Exception $e) {
            return $this->fallbackRoute($lat1, $lon1, $lat2, $lon2);
        }
    }

    /**
     * Tính toán dự phòng bằng công thức Haversine nếu OSRM lỗi.
     */
    private function fallbackRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $distance_km = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
        // Giả định tốc độ trung bình của xe máy là 28 km/h
        $duration_s = ($distance_km > 0) ? ($distance_km / 28) * 3600 : 0;

        return [
            'distance_km' => $distance_km,
            'duration_s' => (int) $duration_s,
            'source' => 'haversine_fallback',
        ];
    }

    /**
     * Công thức Haversine để tính khoảng cách đường chim bay.
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
