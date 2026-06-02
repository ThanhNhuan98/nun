<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Wallet;

class WalletController extends BaseController
{
    // Hiển thị giao diện nạp tiền và xử lý cập nhật số dư khi tài xế nạp tiền vào ví.
    public function topup(Request $request, Response $response)
    {

        $driverId = $this->userId();
        $walletModel = new Wallet();
        $currentBalance = $walletModel->getBalance($driverId);

        if ($request->isPost()) {
            $data = $request->getBody();
            $amount = (float) ($data['amount'] ?? 0);

            if ($amount < 10000) {
                $_SESSION['flash_error'] = 'Số tiền nạp tối thiểu là 10.000đ.';
            } else {
                if ($walletModel->add($driverId, $amount, 'deposit', 'Driver wallet top-up')) {
                    $_SESSION['flash_success'] = 'Nạp thành công ' . number_format($amount, 0, ',', '.') . 'đ vào ví.';
                    return $response->redirect('/driver/wallet/topup');
                } else {
                    $_SESSION['flash_error'] = 'Có lỗi xảy ra khi nạp tiền. Vui lòng thử lại.';
                }
            }
        }

        return $response->render('driver/wallet/topup', [
            'pageTitle' => 'Nạp tiền vào ví',
            'currentBalance' => $currentBalance
        ]);
    }
}
