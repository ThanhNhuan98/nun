<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Wallet;

class WithdrawalController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $walletModel = new Wallet();

        $balance = $walletModel->getBalance($driverId);

        return $response->render('driver/wallet/withdraw', [
            'pageTitle' => 'Rút tiền',
            'balance' => $balance
        ]);
    }

    public function store(Request $request, Response $response)
    {
        if ($redirect = $this->requireRole($response, 'driver')) {
            return $redirect;
        }

        $driverId = $this->userId();
        $data = app_sanitize($request->getBody());
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount < 50000) {
            $_SESSION['flash_error'] = 'Số tiền rút tối thiểu là 50.000đ.';
            return $response->redirect('/driver/wallet/withdraw');
        }

        $walletModel = new Wallet();

        if ($walletModel->deduct($driverId, $amount, 'adjustment', 'Rút tiền về ngân hàng (Tượng trưng)')) {
            $_SESSION['flash_success'] = 'Rút tiền thành công ' . number_format($amount, 0, ',', '.') . 'đ (Mô phỏng).';
        } else {
            $_SESSION['flash_error'] = 'Số dư ví không đủ để rút số tiền này.';
        }

        return $response->redirect('/driver/wallet/withdraw');
    }
}