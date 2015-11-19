<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Order;
use App\Database\Models\UserOrderSubscriptionMapping;
use Log;

class OrderDetailTransformer extends TransformerAbstract {

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * List of resources default to include
     *
     * @var array
     */
    protected $defaultIncludes = ['category'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Order $order) {

        $subscription = $order->subscription()->get()->first();
        $merchant = $subscription->merchant()->get()->first();
        $uosMapping = UserOrderSubscriptionMapping::where('idOrder', $order->id)->first();
        $user = $order->user()->get()->first();
        $popup = \DB::table('tblshowlocationcodepopup')->select(['id', 'szFlag'])->first();

        $url = \Config::get('constants.__MAIN_SITE_URL__');
        $path = \Config::get('constants.__MAIN_SITE_PATH__');

        return [
            'idOrder'           => $order->id,
            'idUser'            => $order->idUser,
            'idSubscription'    => $order->idSubscription,
            'idMerchant'        => $merchant->id,
//            'idMerchant'        => $subscription->idMerchant,


            'szName'            => "{$order->szFirstName} {$order->szLastName}",
            'szFirstName'       => $order->szFirstName,
            'szLastName'        => $order->szLastName,
            'szEmail'           => $order->szEmail,
            'szPassType'        => $order->szPassType,
            'iPromotional'      => $order->iPromotional,
            'iLimitionCount'    => $order->iLimitionCount,
            'passes_left'       => $order->iLimitionCount > 0 ? $order->iLimitionCount - $order->iTotalUsedCount : 0,
            'pass_amount'       => $order->fSubscriptionAmount,
            'fSubscriptionAmount'   => $order->fSubscriptionAmount,
            'fPaidAmount'       => $order->fPaidAmount,
            'iTotalPaymentCount'=> $order->iTotalPaymentCount,
            'iTotalUsedCount'   => $order->iTotalUsedCount,
            'dtPurchased'       => $order->dtPurchased,
            'dtAvailable'       => $order->dtAvailable,
            'dtUsed'            => $order->dtUsed,
            'PROFILEID'         => $order->PROFILEID,
            'PROFILESTATUS'     => $order->PROFILESTATUS,
            'iCancelled'        => $order->iCancelled,
            'dtCancelled'       => $order->dtCancelled,
            'szSubscriptionPeriod'	=> $order->szSubscriptionPeriod,
            'iAutoRenewFlag'    => $order->iAutoRenewFlag,
            'iCancelSubscriptionFlag'   => $order->iCancelSubscriptionFlag,


            'szTilte'           => $subscription->szTilte,
            'szShortDescription'=> $subscription->szShortDescription,
            'szUpgradeDescription'  => $subscription->szUpgradeDescription,
            'szExchangeDescription' => $subscription->szExchangeDescription,
            'szDescription'     => $subscription->szDescription,
            'szOfferHighlight'  => $subscription->szOfferHighlight,
            'szPassImg'         => $subscription->szUploadImageName && file_exists($path . \Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE_DIR__') . $subscription->szUploadImageName) ?
                $url . \Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE_DIR__') . $subscription->szUploadImageName : '',


            'fPrice'            => $uosMapping->fPrice,
            'iPeriod'           => $uosMapping->iPeriod,
            'iLimitions'        => $uosMapping->iLimitions,
            'iYearlyPeriod'     => $uosMapping->iYearlyPeriod,
            'fYearlyPrice'      => $uosMapping->fYearlyPrice,


            'merchantName'        => $merchant->szName,
            'merchantImageName'   => $merchant->szUploadFileName && file_exists($path . \Config::get('constants.__UPLOAD_MERCHANT_IMAGE_DIR__') . $merchant->szUploadFileName) ?
                $url . \Config::get('constants.__UPLOAD_MERCHANT_IMAGE_DIR__') . $merchant->szUploadFileName : $url . 'images/coming-soon-blog.png',
            'merchantDescription' => $merchant->szShortDescription,

            'szProfileImg'      => $user->szUploadFileName && file_exists($path . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName) ?
                $url . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName : $url . \Config::get('constants.__IMAGE_DIR__') . 'user-image.jpg',
//            'szProfileImg'      => $user->id,


            'szShowLocationCodePopUp'   => $popup->szFlag,
        ];
    }

    public function includeCategory(Order $order) {
        $subscription = $order->subscription()->with('category')->get()->first();
        $category = $subscription->category;

        if ($category)
            return $this->item($category, new CategoryTransformer());
    }
}