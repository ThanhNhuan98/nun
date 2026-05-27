<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Chat;
use App\Services\PusherService;

class ChatController
{
    // API lấy danh sách tin nhắn của một phòng chat cụ thể.
    public function getMessages(Request $request, Response $response)
    {
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $orderId = (int)$request->getRouteParam('order_id');
        $chatModel = new Chat();
        $role = $_SESSION['user']['role'] ?? 'user';
        if (!$chatModel->userCanAccessOrder($orderId, (int) $userId, $role)) {
            return $response->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $messages = $chatModel->getMessages($orderId);
        
        return $response->json(['success' => true, 'messages' => $messages, 'current_user_id' => $userId]);
    }

    // API xử lý lưu tin nhắn mới và kích hoạt sự kiện realtime qua Pusher.
    public function sendMessage(Request $request, Response $response)
    {
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $orderId = (int)$request->getRouteParam('order_id');
        $data = $request->getJsonBody() ?: json_decode(file_get_contents('php://input'), true);
        $data = is_array($data) ? $data : [];
        
        $message = app_sanitize($data['message'] ?? '');
        $receiverId = (int)($data['receiver_id'] ?? 0);

        if (empty($message) || !$receiverId) return $response->json(['success' => false]);

        $chatModel = new Chat();
        $role = $_SESSION['user']['role'] ?? 'user';
        if (!$chatModel->userCanAccessOrder($orderId, (int) $userId, $role)) {
            return $response->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $participants = $chatModel->getOrderParticipantIds($orderId);
        if (!in_array((int) $userId, $participants, true) || !in_array($receiverId, $participants, true) || $receiverId === (int) $userId) {
            return $response->json(['success' => false, 'error' => 'Invalid chat receiver'], 422);
        }

        $success = $chatModel->sendMessage($orderId, (int) $userId, $receiverId, $message);
        
        // Tích hợp Pusher: Bắn tin nhắn real-time
        if ($success && class_exists(PusherService::class)) {
            try {
                $pusher = new PusherService();
                $pusher->trigger('chat-channel-' . $orderId, 'new_message', [
                    'sender_id' => $userId,
                    'receiver_id' => $receiverId,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    // (Tùy chọn) Gửi thêm thông tin người gửi để hiển thị avatar
                    'sender_name' => $_SESSION['user']['name'] ?? 'User',
                    'sender_avatar' => $_SESSION['user']['avatar'] ?? ''
                ]);
            } catch (\Throwable $e) {
                error_log("Lỗi Pusher Chat: " . $e->getMessage());
            }
        }
        
        return $response->json(['success' => $success]);
    }
}
