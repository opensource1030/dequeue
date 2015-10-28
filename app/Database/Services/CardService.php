<?php

namespace App\Database\Services;

use Braintree_Customer;

class CardService extends Service {

    public function get_all_cards($customerId) {

        $customer = Braintree_Customer::find($customerId);

        $creditCards = [];
        foreach ($customer->creditCards as $cc) {
            $creditCards[] = [
                'bin'           => $cc->bin,
                'cardType'      => $cc->cardType,
                'cardholderName'    => $cc->cardholderName,
                'expirationYear'    => $cc->expirationYear,
                'expirationMonth'   => $cc->expirationMonth,
                'last4'         => $cc->last4,
                'maskedNumber'  => $cc->maskedNumber,
                'token'         => $cc->token,
                'uniqueNumberIdentifier'    => $cc->uniqueNumberIdentifier,
            ];
        }

        return $creditCards;
    }
}