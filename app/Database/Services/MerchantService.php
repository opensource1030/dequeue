<?php

namespace App\Database\Services;

//use Carbon\Carbon;

class MerchantService extends Service {

    public function payout($data) {

        $merchant = $merchant = $this->merchantRepository->find($data['idMerchant']);

        if (empty($merchant)) {
            throw new \ErrorException('No found');
        }

        if ( isset($data['isAdmin']) && $data['isAdmin'] == 1) {
            $admin = $this->adminRepository->findWhere(['szMobileKey' => $data['szMobileKey']])->first();

            if (empty($admin)) {
                throw new \ErrorException('Invalid mobile key');
            }
        } else {

            if ($merchant->szMobileKey != $data['szMobileKey']) {
                throw new \ErrorException('Unauthorized merchant');
            }
        }

        $result = [
            'bank_name'         => $merchant->szBankName,
            'account_number'    => $merchant->szAccountNumber,
            'routing_number'    => $merchant->szRoutingNumber,
        ];

        return $result;
    }
}