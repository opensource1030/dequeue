<?php

namespace App\Database\Services;

use Braintree_Customer;
use Braintree_PaymentMethod;
use Braintree_CreditCard;
use Braintree_Exception;

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

    public function getCustomerId($idUser) {

        $user = $this->userRepository->findWhere([
            'id' => $idUser
        ])->first();

        if (empty($user))
            return false;

        if ($user->szCustomerId != '')
            return $user->szCustomerId;

        $szCustomerId = "";

        if ($user->szPaymentToken != '') {

            $card = Braintree_PaymentMethod::find($user->szPaymentToken);
            $szCustomerId = $card->customerId;
//            var_dump($card);
//            if (empty($szCustomerId) || $szCustomerId == "") {
        } else {

            $result = Braintree_Customer::create([
                "firstName" => $user->szFirstName,
                "lastName"  => $user->szLastName,
            ]);

            if ($result->success != 1) {
                return false;
            }

            $szCustomerId = $result->customer->id;
        }

        $this->userRepository->update([
            'szCustomerId' => $szCustomerId,
        ], $user->id);

        return $szCustomerId;
    }

    public function get_all_cards($customerId) {

        $customer = Braintree_Customer::find($customerId);

        $creditCards = [];
        foreach ($customer->creditCards as $cc) {
            $creditCards[] = $this->convertCreditCardToJson($cc);
        }

        return $creditCards;
    }

    public function get_card($token, $customerId) {

        $paymentMethod = Braintree_PaymentMethod::find($token);

        $result = [];
        if ($paymentMethod instanceof Braintree_CreditCard) {
            $result = $this->convertCreditCardToJson($paymentMethod);
        }
        return $result;

//        return $paymentMethod->_attributes;
    }

    public function create_card($number, $expirationMonth, $expirationYear, $cvv, $customerId) {

        $result = Braintree_CreditCard::create([
            'customerId'    => $customerId,
            'number'        => $number,
            'expirationMonth'   => $expirationMonth,
            'expirationYear'    => $expirationYear,
            'cvv'           => $cvv,
            'options' => [
                'failOnDuplicatePaymentMethod' => true,
//                'makeDefault'   => true,
            ]
        ]);

        if (!$result->success) {
            $errors = $result->errors->deepAll();
            $error = $errors[0];
            throw new Braintree_Exception($error->message, $error->code);
        }

        $result = $this->convertCreditCardToJson($result->creditCard);

        return $result;
    }

    public function delete_card($token, $customerId) {

        $result = Braintree_PaymentMethod::delete($token);

        return $result;
    }

    public function set_default_card($token, $customerId) {

        $result = Braintree_PaymentMethod::update($token, [
            'options' => [
                'makeDefault'   => true
            ]
        ]);

        return $result;
    }

    public function get_default_card($customerId) {

        $customer = Braintree_Customer::find($customerId);
        $token = $customer->creditCards[0]->token;
        foreach ($customer->creditCards as $cc) {
            if ($cc->isDefault()) {
                $token = $cc->token;
                break;
            }
        }

        $user = $this->userRepository->findWhere([
            'szCustomerId' => $customerId
        ])->first();

        if ($user) {
            $this->userRepository->update([
                'szPaymentToken' => $token,
            ], $user->id);
        }

        return $token;
    }
}