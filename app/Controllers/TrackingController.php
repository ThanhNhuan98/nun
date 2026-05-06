<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;

class TrackingController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $query = $request->getBody();
        $code = trim($query['code'] ?? '');

        $order = null;
        $history = [];

        if (!empty($code)) {
            $orderModel = new Order();
            $order = $orderModel->findByTrackingCodePublic($code);

            if ($order) {
                $history = $orderModel->getOrderHistory($order['id']);
            }
        }

        return $response->render('tracking', [
            'pageTitle' => 'Tra cứu vận đơn: ' . htmlspecialchars($code),
            'order' => $order,
            'history' => $history,
            'code' => $code
        ]);
    }
}