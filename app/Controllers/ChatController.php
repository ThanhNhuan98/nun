<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Chat;

class ChatController
{
    public function getMessages(Request $request, Response $response)
    {
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $orderId = (int)$request->getRouteParam('order_id');
        $chatModel = new Chat();
        $messages = $chatModel->getMessages($orderId);
        
        return $response->json(['success' => true, 'messages' => $messages, 'current_user_id' => $userId]);
    }

    public function sendMessage(Request $request, Response $response)
    {
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $response->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $orderId = (int)$request->getRouteParam('order_id');
        $data = $request->getJsonBody() ?: json_decode(file_get_contents('php://input'), true);
        
        $message = app_sanitize($data['message'] ?? '');
        $receiverId = (int)($data['receiver_id'] ?? 0);

        if (empty($message) || !$receiverId) return $response->json(['success' => false]);

        $chatModel = new Chat();
        $success = $chatModel->sendMessage($orderId, $userId, $receiverId, $message);
        
        return $response->json(['success' => $success]);
    }
}