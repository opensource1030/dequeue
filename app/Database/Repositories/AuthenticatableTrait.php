<?php

namespace App\Database\Repositories;

use Illuminate\Support\Facades\Schema;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use StringHelper;

trait AuthenticatableTrait {

    /**
     * sign in
     *
     * @param array
     * @return mixed
     */
    public function signIn($data)
    {
        $model = $this->model->newQuery()
            ->where('szEmail', $data['szEmail'])
            ->where('szPassword', StringHelper::encryptString($data['szPassword']))
            ->first();

        if (isset($model['id']) && $model['id'] > 0) {

            if ($model->szMobileKey == '' || $model->szMobileKey == 'NULL') {
                $model->fill([
                    'szMobileKey'    => StringHelper::uniqueKey()
                ]);
                $model->save();

                event(new RepositoryEntityUpdated($this, $model));
            }
        } else {
            $model = array();
        }

        return $this->parserResult($model);
    }

    /**
     * change password
     *
     * @param array $data
     * @return mixed
     */
    public function changePassword($data)
    {
        $model = $this->model->newQuery()
            ->where('szMobileKey', $data['szMobileKey'])
            ->first();

        if (isset($model['id']) && $model['id'] > 0) {
            $model->fill([
                'szPassword'    => $data['szPassword']
            ]);
            $model->save();
            $this->resetModel();
            event(new RepositoryEntityUpdated($this, $model));
        } else {
            $model = array();
        }

        return $this->parserResult($model);
    }

    /**
     * check email exist
     *
     * @param $email
     * @param $id
     * @return bool
     */
    public function existsEmail($email, $id) {

        $queryBuilder = $this->model->newQuery()
            ->where('szEmail', $email);

        if (Schema::hasColumn($this->model->getTable(), 'isDeleted')) {
            $queryBuilder = $queryBuilder->where('isDeleted', '0');
        }

        if ($id && $id > 0) {
            $queryBuilder = $queryBuilder->where('id', '!=', $id);
        }

//        \Log::info($queryBuilder->toSql());

        $model = $queryBuilder->get()->first();

        if ($model && $model->id > 0)
            return true;
        else
            return false;
    }
}