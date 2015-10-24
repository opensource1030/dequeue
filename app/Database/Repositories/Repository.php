<?php

namespace App\Database\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;

abstract class Repository extends BaseRepository {

    /**
     * @return Model|\Illuminate\Database\Eloquent\Model
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function getModel() {

        if (!$this->model instanceof Model) {
            $this->makeModel();
        }

        return $this->model;
    }
}