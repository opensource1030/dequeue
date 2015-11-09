<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Merchant;
use Log;

class MerchantTransformer extends TransformerAbstract {

	/**
	 * List of resources possible to include
	 *
	 * @var array
	 */
	protected $availableIncludes = [
		'locations'
	];

	 protected $defaultIncludes = [
	 	'categories'
	 ];

	/**
	 * Turn this item object into a generic array
	 *
	 * @return array
	 */
	public function transform(Merchant $merchant) {

        $url = \Config::get('constants.__MAIN_SITE_URL__');
        $path = \Config::get('constants.__MAIN_SITE_PATH__');

		return [
			'id'			=> (int) $merchant->id,
			'szName'		=> $merchant->szName,
			'szFirstName'	=> $merchant->szFirstName,
			'szLastName'	=> $merchant->szLastName,
            'szCompanyName'	=> $merchant->szCompanyName,
            'szEmail'		=> $merchant->szEmail,
            'szAddress1'	=> $merchant->szAddress1,
            'szAddress2'	=> $merchant->szAddress2,
            'szCity'		=> $merchant->szCity,
            'szState'		=> $merchant->szState,
            'szZipCode'		=> $merchant->szZipCode,
            'szPhoneNumber'	=> $merchant->szPhoneNumber,
            'szLatitude'	=> $merchant->szLatitude,
            'szLongitude'	=> $merchant->szLongitude,
            'dtCreated'		=> $merchant->dtCreated,
            'iActive'		=> $merchant->iActive,
            'isDeleted'		=> $merchant->isDeleted,
            'szWebsite'		=> $merchant->szWebsite,
            'szNote'        => $merchant->szNote,
            'szFileName'	=> $merchant->szFileName,
            'szUploadFileName'		=> $merchant->szUploadFileName && file_exists($path . \Config::get('constants.__UPLOAD_MERCHANT_IMAGE_DIR__') . $merchant->szUploadFileName) ?
                $url . \Config::get('constants.__UPLOAD_MERCHANT_IMAGE_DIR__') . $merchant->szUploadFileName : $url . 'images/coming-soon-blog.png',
            'szShortDescription'	=> $merchant->szShortDescription,
            'szDescription'	=> $merchant->szDescription,
            'iOrder'		=> $merchant->iOrder,
            'szHighlight'	=> $merchant->szHighlight,
            'szOnDemandPopup'		=> $merchant->szOnDemandPopup,
            'dtOnDemandFromdate'	=> $merchant->dtOnDemandFromdate,
            'dtOnDemandTodate'		=> $merchant->dtOnDemandTodate
		];
	}

	/**
	 * Include Locations
	 *
	 * @param Merchant $merchant
	 * @return \League\Fractal\Resource\Item
	 */
	public function includeLocations(Merchant $merchant) {

		$locations = $merchant->locations()->get();

		// Log::info($merchant->id);
		// Log::info($locations);
		
		return $this->collection($locations, new LocationTransformer, 'merchant_location');
	}

    public function includeCategories(Merchant $merchant) {

//        \Log::info('includeCategories called');
        $subscriptions = $merchant->subscriptions()->with('category')->get();
        $categories = $subscriptions->pluck('category');
        $unique = $categories->unique('id')->all();
//        $categories = [];
//        foreach ($subscriptions as $subscription) {
//            $categories[] = $subscription->category;
//        }

//        var_dump($subscription);
//        if ($categories)
//            return $this->collection($categories, new CategoryTransformer());

//        var_dump($unique);
        if ($unique)
            return $this->collection($unique, new CategoryTransformer());
    }
}