<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Subscription;

use Config;

class SubscriptionDetailTransformer extends TransformerAbstract {

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Subscription $subscription) {

        $merchant = $subscription->merchant()->get()->first();

        return [
            'id'			=> (int) $subscription->id,
            'idParentPass'	=> $subscription->idParentPass,
            'idCategory'	=> $subscription->idCategory,
            'idMerchant'	=> $subscription->idMerchant,
            'szTilte'	    => $subscription->szTilte,
            'szCleanTitle'	=> $subscription->szCleanTitle,
            'szDescription'	=> $subscription->szDescription,
            'szShortDescription'	=> $subscription->szShortDescription,
            'fPrice'	    => $subscription->fPrice,
            'iLimitions'	=> $subscription->iLimitions,
            'iLimitionCount'		=> $subscription->iLimitionCount,
            'dtCreated'		=> $subscription->dtCreated,
            'iActive'		=> $subscription->iActive,
            'szFileName'	=> $subscription->szFileName, // URL::to
            'szUploadImageName'	=> $subscription->szUploadImageName ? Config::get('constants.__MAIN_SITE_URL__') . Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE_DIR__') . $subscription->szUploadImageName : '',
            'iYearlyPeriod'		=> $subscription->iYearlyPeriod,
            'fYearlyPrice'	=> $subscription->fYearlyPrice,
            'iOrder'		=> $subscription->iOrder,
            'szOfferHighlight'	=> $subscription->szOfferHighlight,
            'iPromotional'	    => $subscription->iPromotional,
            'iActivationCount'  => $subscription->iActivationCount,
            'szCouponCode'      => $subscription->szCouponCode,
            'isGifted'      => $subscription->isGifted,


            'szMerchantName'    => $merchant->szName,
            'szWebsite'         => $merchant->szWebsite,
        ];
    }
}