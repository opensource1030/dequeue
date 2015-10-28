<?php

namespace App\Database\Services;

use Braintree_Customer;

class CardService extends Service {

    public function get_all_cards($customerId) {

        $customer = Braintree_Customer::find($customerId);

//        $result = [
//            'creditCards' => $customer->creditCards,
//        ];

        return $customer;
    }
}