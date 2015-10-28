<?php

namespace App\Database\Services;

class SubscriptionService extends Service {

#TODO total subscribers - subscription / loadAllMerchantPasses()
    function find_by_merchant($data) {
        $merchantId = $data['idMerchant'];
        $szMobileKey = $data['szMobileKey'];

        $user = $this->userRepository
            ->findWhere(['szMobileKey' => $szMobileKey])
            ->first();

        if (!isset($user['id']) || $user['id'] == 0) {
            throw new \Exception('Invalid User');
        }

        $orders = $this->orderRepository
            ->findWhere([
                'iCompleted'    => 1,
                'szPassType'    => 'one time pass',
//                'idUser'        => $user->id,
            ])
            ->unique('idSubscription');

        $oneTimePassArr = $orders->pluck('idSubscription')->all();

        $merchant = $this->merchantRepository->findWhere([
            'id'        => $merchantId,
            'isDeleted' => 0,
            'iActive'   => 1,
            'iDraft'    => 0,
        ])->first();

        if ($merchant) {
            $model = $this->subscriptionRepository->getModel()->newQuery()
                ->where('idMerchant', $merchant->id)
                ->where('isDeleted', 0)
                ->where('iActive', 1)
                ->where('iPromotional', 0)
                ->where('isGifted', 0)
                ->whereNotIn('id', $oneTimePassArr)
                ->orderBy('iOrder')
                ->get();

//            $total_subscription=  $kSubscription->total_number_of_subscriber_of_pass($data);
//            $arr['total_subscription']=$total_subscription;

            return $model;
        } else {
            return array();
        }
    }

#TODO total subscribers - merchant / getAllPassesByMobileKey()
    function find_by_mobile_key($data) {
        $idMerchant = $data['idMerchant'];
        $szMobileKey = $data['szMobileKey'];
        $isAdmin = $data['isAdmin'];

        $queryBuilder = $this->subscriptionRepository->getModel()->newQuery()
            ->from('tblsubscriptions as s')
            ->join('tblmerchant as m', 's.idMerchant', '=', 'm.id')
            ->where('s.isDeleted', 0)
            ->where('m.id', $idMerchant);

        if ($isAdmin == 0) {
            $queryBuilder = $queryBuilder->where('m.iActive', 1);
        }

        $queryBuilder = $queryBuilder->select('s.*');

        \Log::info($queryBuilder->toSql());
        $model = $queryBuilder->get();

//            $total_subscription=  $kSubscription->total_number_of_subscriber_of_pass($data);
//            $arr['total_subscription']=$total_subscription;

        return $model;

    }

#TODO return several fields - subscription / getAllSubscriptionData()
    function search_by_text($data, $activeOnly = false) {
        $queryBuilder = $this->subscriptionRepository->getModel()->newQuery()
            ->from('tblsubscriptions as s')
            ->select('s.*')
            ->leftJoin('tblmerchant as m', function ($join) {
                $join->on('s.idMerchant', '=', 'm.id');
//                        ->where('m.iActive', '=', 1)
//                        ->where('m.iDraft', '=', 0);
            })
            ->where('m.isDeleted', 0)
            ->where('s.isDeleted', 0);

        if ($activeOnly) {
//            $queryBuilder = $queryBuilder->whereHas('merchant', function ($query) { // not eager loading
//            ::with(['merchant' => function ($query) {
//                    $query->select(['id', 'szWebSite']);
//                $query->where('iActive', 1)->where('iDraft', 0);
//            }])
            $queryBuilder = $queryBuilder
                ->where('s.iActive', 1)
                ->where('s.iDraft', 0)
                ->where('m.iActive', 1)
                ->where('m.iDraft', 0);
        }

        if (isset($data['szSearchValue']) && $data['szSearchValue']) {
            $szSearchValue = $data['szSearchValue'];

            $queryBuilder = $queryBuilder->where(function ($query) use ($szSearchValue) {
                $query->orWhere('s.szTilte', 'like', "%{$szSearchValue}%")
                    ->orWhere('s.szDescription', 'like', "%{$szSearchValue}%")
                    ->orWhere('m.szName', 'like', "%{$szSearchValue}%");
            });
        }

//        $query = $queryBuilder->toSql();
//        Log::info($query);

        $model = $queryBuilder->get();

        return $model;
    }

    function search_by_zipcode($data) {

//        Log::info(LocationHelper::getCoordinate('', '02115'));
//        return array();

        $queryBuilder = $this->subscriptionRepository->getModel()->newQuery()
            ->from('tblsubscriptions as s')
            ->leftJoin('tblmerchant as m', function ($join) {
                $join->on('s.idMerchant', '=', 'm.id');
            });

        $location = null;
        if (isset($data['zipCode']) && $data['zipCode']) {
            $location = LocationHelper::getCoordinate('', $data['zipCode']);
        }
        if($location) {
            $distance="(( 3959 * acos( cos( radians(".$location['lat'].") ) * cos( radians( m.szLatitude ) )
* cos( radians(m.szLongitude) - radians(".$location['long'].")) + sin(radians(".$location['lat']."))
* sin( radians(m.szLatitude)))) * 1.609344)
AS distance";
            $queryBuilder = $queryBuilder
                ->select('s.*', DB::raw($distance))
                ->orderBy('distance', 'asc');
        }
        else {
            $queryBuilder = $queryBuilder
                ->distinct()// ('s.id')
                ->select('s.*')
                ->orderBy('s.id', 'asc');
        }

        $queryBuilder = $queryBuilder->where('s.isDeleted', 0);

        $query = $queryBuilder->toSql();
//        Log::info($location);
//        Log::info($query);

        $model = $queryBuilder->get();

        return $model;
    }

#TODO add user_pay_info_available if szMobileKey is available - subscription / loadByCleanTitle
    function find_by_title($data) {
        $model = $this->subscriptionRepository->findWhere([
            'szCleanTitle' => $data['szCleanTitle'],
            'isDeleted' => 0,
        ]);
        return $model;
    }

    function find_by_category($data) {
        $where = [
            'isDeleted'     => 0,
            'iActive'       => 1,
            'idCategory'    => (int) $data['categoryId']
        ];

        if (isset($data['merchantId'])) {
            $merchantId = (int) $data['merchantId'];
            if ($merchantId > 0) {
                $where['idMerchant'] = $merchantId;
            }
        }

        $model = $this->subscriptionRepository->findWhere($where);

        return $model;
    }

    function newCleanTitle($szTitle, $id = 0, $idMerchant) {

        $title = preg_replace('/\s+/', '_', $szTitle);
        $title = preg_replace('/\W+/', '', $title);
        $title = preg_replace('/[\_]+/', '-', $title);
        $title = preg_replace('/\-$/', '', $title);
        $title = strtolower($title);

        if($idMerchant > 0)
        {
            $merchant = $this->merchantRepository->find($idMerchant);
            $merchantName = $merchant->szName;

            $merchantName = preg_replace('/\s+/', '_', $merchantName);
            $merchantName = preg_replace('/\W+/', '', $merchantName);
            $merchantName = preg_replace('/[\_]+/', '-', $merchantName);
            $merchantName = preg_replace('/\-$/', '', $merchantName);
            $merchantName = strtolower($merchantName);
        }

        $title = $merchantName . '-' . $title;

        $title_old = $title;
        $i = 0;

        do {
            $title_old = $title;
            $subscription = $this->subscriptionRepository->findWhere([
                'szCleanTitle'  => $title,
                'id'            => ['id', '!=', $id],
            ])->first();

            $title = $title . '-' . $i;
        } while ($subscription);

        $title = $title_old;

        return $title;
    }
}