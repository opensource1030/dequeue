<?php

namespace App\Database\Presenters;

use Prettus\Repository\Presenter\FractalPresenter;
use App\Database\Transformers\MerchantTransformer;

class MerchantPresenter extends FractalPresenter {

	public function getTransformer() {

		return new MerchantTransformer();
	}
}