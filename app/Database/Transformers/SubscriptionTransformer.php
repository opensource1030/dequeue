<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Subscription;

use URL;
use Config;
use Log;

class SubscriptionTransformer extends TransformerAbstract {

    protected $availableIncludes = [];

    protected $defaultIncludes = [
        'category'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @param Subscription $subscription
     * @return array
     */
    public function transform(Subscription $subscription) {

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
            'iPeriod'       => $subscription->iPeriod,
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
            'szUpgradeDescription'  => $subscription->szUpgradeDescription,
            'szExchangeDescription' => $subscription->szExchangeDescription,
        ];
    }

    public function includeCategory(Subscription $subscription) {
        $category = $subscription->category()->get()->first();
        if ($category)
            return $this->item($category, new CategoryTransformer());
    }
}