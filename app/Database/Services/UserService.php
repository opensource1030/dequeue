<?php

namespace App\Database\Services;

class UserService extends Service {

    function get_by_email($szEmail, $isDeleted = 0) {

        $user = $this->userRepository->findWhere([
            'isDeleted' => $isDeleted,
            'szEmail' => $szEmail,
        ])->first();

        return $user;
    }
}