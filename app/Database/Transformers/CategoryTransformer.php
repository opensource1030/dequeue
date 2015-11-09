<?php

namespace App\Database\Transformers;

use League\Fractal\TransformerAbstract;
use App\Database\Models\Category;
use Config;

class CategoryTransformer extends TransformerAbstract {

    public function transform(Category $category) {

        $dir = Config::get('constants.__UPLOAD_CATEGORY_IMAGE_DIR__');
        $url = Config::get('constants.__MAIN_SITE_URL__') . $dir;
        $path = Config::get('constants.__MAIN_SITE_PATH__') . $dir;

        return [
            "id"		    => (int) $category->id,
            "szName"	    => $category->szName,
            "szDescription"	=> $category->szDescription,
            "szFileName"	=> $category->szFileName,
            "szUploadFileName"	=> $category->szUploadFileName && file_exists($path . $category->szUploadFileName) ?
                $url . $category->szUploadFileName : Config::get('constants.__MAIN_SITE_URL__') . 'images/coming-soon-blog.png',
            "dtCreated"		=> $category->dtCreated,
            "iActive"	    => $category->iActive,
        ];
    }
}