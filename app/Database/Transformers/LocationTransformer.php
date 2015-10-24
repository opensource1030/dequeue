<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;

class LocationTransformer extends TransformerAbstract {

	public function transform(\App\Database\Models\Location $location) {

		return [
			"id"			=> (int) $location->id,
			"szAddress1"	=> $location->szAddress1,
			"szAddress2"	=> $location->szAddress2,
			"szCity"		=> $location->szCity,
			"szState"		=> $location->szState,
			"szZipCode"		=> $location->szZipCode,
			"szPhoneNumber"	=> $location->szPhoneNumber,
			"szLatitude"	=> $location->szLatitude,
			"szLongitude"	=> $location->szLongitude,
			"szPointContact"		=> $location->szPointContact,
			"szLocationPhoto"		=> $location->szLocationPhoto,
		];
	}
}