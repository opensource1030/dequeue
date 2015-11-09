<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Subscription;

use URL;
use Config;
use Log;

class SubscriptionTransformer extends TransformerAbstract {

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
    public function transform(Subscription $model) {

        return [
            'id'			=> (int) $model->id,
            'idParentPass'	=> $model->idParentPass,
            'idCategory'	=> $model->idCategory,
            'idMerchant'	=> $model->idMerchant,
            'szTilte'	    => $model->szTilte,
            'szCleanTitle'	=> $model->szCleanTitle,
            'szDescription'	=> $model->szDescription,
            'szShortDescription'	=> $model->szShortDescription,
            'fPrice'	    => $model->fPrice,
            'iPeriod'       => $model->iPeriod,
            'iLimitions'	=> $model->iLimitions,
            'iLimitionCount'		=> $model->iLimitionCount,
            'dtCreated'		=> $model->dtCreated,
            'iActive'		=> $model->iActive,
            'szFileName'	=> $model->szFileName, // URL::to
            'szUploadImageName'	=> $model->szUploadImageName ? Config::get('constants.__MAIN_SITE_URL__') . Config::get('constants.__UPLOAD_SUBSCRIPTION_IMAGE_DIR__') . $model->szUploadImageName : '',
            'iYearlyPeriod'		=> $model->iYearlyPeriod,
            'fYearlyPrice'	=> $model->fYearlyPrice,
            'iOrder'		=> $model->iOrder,
            'szOfferHighlight'	=> $model->szOfferHighlight,
            'iPromotional'	    => $model->iPromotional,
            'iActivationCount'  => $model->iActivationCount,
            'szCouponCode'      => $model->szCouponCode,
            'isGifted'      => $model->isGifted,
        ];
    }
}