<?php

namespace App\User;

use App\Infrastructure\UserRepository as Repository;

final class UserService
{
    public function create(): void
    {
        new Repository();
    }
}
