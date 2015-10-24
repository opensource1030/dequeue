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

        return [
            'id'			=> (int) $user->id,
            'szFirstName'	=> $user->szFirstName,
            'szLastName'	=> $user->szLastName,
            'szEmail'		=> $user->szEmail,
            'szMobileKey'   => $user->szMobileKey,
            // 'szAddress1'	=> $user->szAddress1,
            // 'szAddress2'	=> $user->szAddress2,
            // 'szCity'		=> $user->szCity,
            // 'szState'		=> $user->szState,
            // 'szZipCode'		=> $user->szZipCode,
            // 'szPhoneNumber'	=> $user->szPhoneNumber,
            // 'dtCreated'		=> $user->dtCreated,
            // 'iActive'		=> $user->iActive,
            // 'szWebsite'		=> $user->szWebsite,
            // 'szLatitude'	=> $user->szLatitude,
            // 'szLongitude'	=> $user->szLongitude,
            // 'szFileName'	=> $user->szFileName,
            // 'szUploadFileName'		=> $user->szUploadFileName,
            // 'szShortDescription'	=> $user->szShortDescription,
            // 'szDescription'	=> $user->szDescription,
            // 'iOrder'		=> $user->iOrder,
            // 'szHighlight'	=> $user->szHighlight,
            // 'szOnDemandPopup'		=> $user->szOnDemandPopup,
            // 'dtOnDemandFromdate'	=> $user->dtOnDemandFromdate,
            // 'dtOnDemandTodate'		=> $user->dtOnDemandTodate
        ];
    }
}