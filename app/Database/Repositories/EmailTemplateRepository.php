<?php

namespace App\Database\Repositories;

class EmailTemplateRepository extends Repository {

    function model() {

        return "App\\Database\\Models\\EmailTemplate";
    }
}