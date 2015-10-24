<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use Config;

class CategoryTransformer extends TransformerAbstract {

    public function transform(\App\Database\Models\Category $model) {

        $dir = Config::get('constants.__UPLOAD_CATEGORY_IMAGE_DIR__');
        $url = Config::get('constants.__MAIN_SITE_URL__') . $dir;
        $path = Config::get('constants.__MAIN_SITE_PATH__') . $dir;

        return [
            "id"		    => (int) $model->id,
            "szName"	    => $model->szName,
            "szDescription"	=> $model->szDescription,
            "szFileName"	=> $model->szFileName,
            "szUploadFileName"	=> $model->szUploadFileName && file_exists($path . $model->szUploadFileName) ?
                $url . $model->szUploadFileName : Config::get('constants.__MAIN_SITE_URL__') . 'images/coming-soon-blog.png',
            "dtCreated"		=> $model->dtCreated,
            "iActive"	    => $model->iActive,
        ];
    }
}