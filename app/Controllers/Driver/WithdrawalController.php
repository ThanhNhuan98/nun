<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Wallet;
use App\Models\Setting;

class WithdrawalController extends BaseController
{
    // Hiển thị giao diện rút tiền từ ví tài xế về tài khoản ngân hàng.
    public function index(Request $request, Response $response)
    {

        $driverId = $this->userId();
        $walletModel = new Wallet();

        $balance = $walletModel->getBalance($driverId);

        $settingModel = new Setting();
        $minWithdraw = (float) $settingModel->get('min_wallet_withdraw_amount', 50000);

        return $response->render('driver/wallet/withdraw', [
            'pageTitle' => 'Rút tiền',
            'balance' => $balance,
            'minWithdraw' => $minWithdraw
        ]);
    }

    // Xử lý yêu cầu rút tiền của tài xế (Thực hiện mô phỏng trừ tiền trong ví).
    public function store(Request $request, Response $response)
    {
        $driverId = $this->userId();
        $data = app_sanitize($request->getBody());
        $amount = (float) ($data['amount'] ?? 0);

        $settingModel = new Setting();
        $minWithdraw = (float) $settingModel->get('min_wallet_withdraw_amount', 50000);

        if ($amount < $minWithdraw) {
            $_SESSION['flash_error'] = 'Số tiền rút tối thiểu là ' . number_format($minWithdraw, 0, ',', '.') . 'đ.';
            return $response->redirect('/driver/wallet/withdraw');
        }

        $walletModel = new Wallet();

        if ($walletModel->deduct($driverId, $amount, 'adjustment', 'Rút tiền về ngân hàng')) {
            $_SESSION['flash_success'] = 'Rút tiền thành công ' . number_format($amount, 0, ',', '.') . 'đ.';
        } else {
            $_SESSION['flash_error'] = 'Số dư ví không đủ để rút số tiền này.';
        }

        return $response->redirect('/driver/wallet/withdraw');
    }
}
