<?php 

namespace App\Database\Repositories;

class MerchantRepository extends Repository {

    use AuthenticatableTrait;

	function model() {

		return "App\\Database\\Models\\Merchant";
	}
}