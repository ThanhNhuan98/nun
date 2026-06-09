<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Wallet;
use App\Models\Setting;

class WalletController extends BaseController
{
    // Hiển thị giao diện nạp tiền và xử lý cập nhật số dư khi tài xế nạp tiền vào ví.
    public function topup(Request $request, Response $response)
    {

        $driverId = $this->userId();
        $walletModel = new Wallet();
        $currentBalance = $walletModel->getBalance($driverId);

        $settingModel = new Setting();
        $minTopup = (float) $settingModel->get('min_wallet_topup_amount', 10000);

        if ($request->isPost()) {
            $data = $request->getBody();
            $amount = (float) ($data['amount'] ?? 0);

            if ($amount < $minTopup) {
                $_SESSION['flash_error'] = 'Số tiền nạp tối thiểu là ' . number_format($minTopup, 0, ',', '.') . 'đ.';
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
            'currentBalance' => $currentBalance,
            'minTopup' => $minTopup,
            'paymentSettings' => [
                'bank_id' => $settingModel->get('bank_id', 'VCB'),
                'bank_name' => $settingModel->get('bank_name', 'Vietcombank'),
                'bank_account_no' => $settingModel->get('bank_account_no', '1234567890'),
                'bank_account_name' => $settingModel->get('bank_account_name', 'CONG TY TNHH NUN EXPRESS')
            ]
        ]);
    }
}
