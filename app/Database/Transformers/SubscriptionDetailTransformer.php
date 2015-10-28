<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Subscription;

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

        $merchant = $subscription->merchant()->get();


//                s.id,
//	 			s.szTilte,
//				s.szCleanTitle,
//				s.szDescription,
//				s.fPrice,
//				s.iPeriod,
//				s.iLimitions,
//				s.dtCreated,
//				s.iActive,
//				s.szFileName,
//				s.szUploadImageName,
//				s.szShortDescription,
//				s.iYearlyPeriod,
//				s.fYearlyPrice,
//				s.idCategory,
//				s.iOrder,
//				s.szOfferHighlight,
//				s.idMerchant,
//				s.iPromotional,
//				s.iLimitionCount,
//				s.iCountFinalExpiry,
//				s.iUserLimit,
//                s.szPassType,
//                s.iActivationCount as packageActivationCount,
//                s.szCouponCode,
//				m.szUploadFileName As szMerchantImage,
//				m.szName As szMerchantName,
//				s.szOnDemandPopup,
//				s.dtOnDemandFromdate,
//				s.dtOnDemandTodate,
//				m.szOnDemandPopup AS szMerchantOnDemandPopup,
//				m.dtOnDemandFromdate AS dtMerchantOnDemandFromdate,
//				m.dtOnDemandTodate AS dtMerchantOnDemandTodate

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
        ];
    }
}