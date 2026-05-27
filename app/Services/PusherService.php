<?php

namespace App\Services;

use Pusher\Pusher;

class PusherService
{
    private ?Pusher $pusher = null;

    // Khởi tạo dịch vụ Pusher kết nối đến máy chủ WebSocket để gửi thông báo thời gian thực.
    public function __construct()
    {
        // Chỉ khởi tạo nếu đã cài đặt thư viện qua Composer
        if (class_exists(Pusher::class)) {
            $options = [
                'cluster' => $_ENV['PUSHER_APP_CLUSTER'] ?? 'ap1',
                'useTLS' => true
            ];

            $this->pusher = new Pusher(
                $_ENV['PUSHER_APP_KEY'] ?? '',
                $_ENV['PUSHER_APP_SECRET'] ?? '',
                $_ENV['PUSHER_APP_ID'] ?? '',
                $options
            );
        }
    }

    /**
     * Gửi sự kiện thời gian thực đến một kênh cụ thể
     */
    public function trigger(string $channel, string $event, array $data): void
    {
        if ($this->pusher) {
            $this->pusher->trigger($channel, $event, $data);
        }
    }
}
