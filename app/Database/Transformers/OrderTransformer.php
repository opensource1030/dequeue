<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Order;
use App\Database\Models\Subscription;
use Log;

class OrderTransformer extends TransformerAbstract {

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'subscription',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Order $order) {

        return [
            'id'			    => (int) $order->id,
            'idUser'		    => $order->idUser,
            'idSubscription'	=> $order->idSubscription,
            'idParentOrder'	    => $order->idParentOrder,
            'fSubscriptionAmount'	=> $order->fSubscriptionAmount,
            'szSubscriptionPeriod'	=> $order->szSubscriptionPeriod,
            'iLimitionCount'	=> $order->iLimitionCount,
            'iPromotional'	    => $order->iPromotional,
//            'isGifted'		    => $order->isGifted,
//            'fPaidAmount'		=> $order->fPaidAmount,
//            'dtPurchased'		=> $order->dtPurchased,
//            'szPhoneNumber'	    => $order->szPhoneNumber,
//            'dtCreated'		    => $order->dtCreated,
//            'szFirstName'		=> $order->szFirstName,
//            'szLastName'		=> $order->szLastName,
//            'szEmail'	        => $order->szEmail,
//            'szPaymentType'	    => $order->szPaymentType,
            'iCompleted'	    => $order->iCompleted,
            'dtExpiry'		    => $order->dtExpiry,
//            'iTotalPaymentCount'	=> $order->iTotalPaymentCount,
//            'fDiscountAmount'	=> $order->fDiscountAmount,
//            'idPromoCode'		=> $order->idPromoCode,
//            'iTotalUsedCount'	=> $order->iTotalUsedCount,
//            'dtAvailable'		=> $order->dtAvailable,
//            'dtUsed'	        => $order->dtUsed,
//            'TRANSACTIONID'		=> $order->TRANSACTIONID,
//            'PROFILESTATUS'		=> $order->PROFILESTATUS,
            'iAutoRenewFlag'    => $order->iAutoRenewFlag,
            'iCancelSubscriptionFlag'   => $order->iCancelSubscriptionFlag,
        ];
    }

    public function includeSubscription(Order $order) {
        $subscription = $order->subscription()->get()->first();
//        var_dump($subscription);
//        \Log::info($subscription);
        return $this->item($subscription, new SubscriptionTransformer());
//        return $this->collection($subscription, new SubscriptionTransformer());

    }
}