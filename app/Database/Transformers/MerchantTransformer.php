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

	// protected $defaultIncludes = [
	// 	'locations'
	// ];

	/**
	 * Turn this item object into a generic array
	 *
	 * @return array
	 */
	public function transform(Merchant $merchant) {

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
            'szUploadFileName'		=> $merchant->szUploadFileName,
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
}