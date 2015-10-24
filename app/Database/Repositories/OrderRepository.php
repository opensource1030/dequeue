<?php

namespace App\Database\Repositories;

class OrderRepository extends Repository {

    function model() {

        return "App\\Database\\Models\\Order";
    }
}