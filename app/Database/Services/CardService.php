<?php

namespace App\Database\Services;

use Braintree_Customer;
use Braintree_PaymentMethod;
use Braintree_CreditCard;

class CardService extends Service {
    
    private function convertCreditCardToJson($object) {
        return [
            'bin'           => $object->bin,
            'expirationYear'    => $object->expirationYear,
            'expirationMonth'   => $object->expirationMonth,
            'last4'         => $object->last4,
            'cardType'      => $object->cardType,
            'cardholderName'    => $object->cardholderName,
            'default'       => $object->default,
            'expired'       => $object->expired,
            'token'         => $object->token,
            'uniqueNumberIdentifier'    => $object->uniqueNumberIdentifier,
            'maskedNumber'  => $object->maskedNumber,
        ];
    }

    public function get_all_cards($customerId) {

        $customer = Braintree_Customer::find($customerId);

        $creditCards = [];
        foreach ($customer->creditCards as $cc) {
            $creditCards[] = $this->convertCreditCardToJson($cc);
        }

        return $creditCards;
    }

    public function get_card($cardToken, $customerId) {

        $paymentMethod = Braintree_PaymentMethod::find($cardToken);

        $result = [];
        if ($paymentMethod instanceof Braintree_CreditCard) {
            $result = $this->convertCreditCardToJson($paymentMethod);
        }
        return $result;

//        return $paymentMethod->_attributes;
    }
}