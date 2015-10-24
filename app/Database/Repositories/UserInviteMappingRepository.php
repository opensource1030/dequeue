<?php

namespace App\Database\Repositories;

class UserInviteMappingRepository extends Repository {

    function model() {

        return "App\\Database\\Models\\UserInviteMapping";
    }
}