<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\User;
use Log;

class UserTransformer extends TransformerAbstract {

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
    public function transform(User $user) {

        $url = \Config::get('constants.__MAIN_SITE_URL__');
        $path = \Config::get('constants.__MAIN_SITE_PATH__');

        return [
            'id'			=> (int) $user->id,
            'szFirstName'	=> $user->szFirstName,
            'szLastName'	=> $user->szLastName,
            'szEmail'		=> $user->szEmail,
            'szMobileKey'   => $user->szMobileKey,
            'szAddress1'	=> $user->szAddress1,
            'szAddress2'	=> $user->szAddress2,
            'szCity'		=> $user->szCity,
            'szState'		=> $user->szState,
            'szZipCode'		=> $user->szZipCode,
            'szPhoneNumber'	=> $user->szPhoneNumber,
            'dtCreated'		=> $user->dtCreated,
            'isDeleted'		=> $user->isDeleted,
            'fUserCredit'		=> $user->fUserCredit,
            'fReferralCredit'	=> $user->fReferralCredit,
            'fTotalCredit'	    => $user->fTotalCredit,
            'szUploadFileName'		=> $user->szUploadFileName && file_exists($path . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName) ?
                $url . \Config::get('constants.__UPLOAD_USER_IMAGE_DIR__') . $user->szUploadFileName : '',
        ];
    }
}