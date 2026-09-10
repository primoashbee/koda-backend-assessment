<?php

declare(strict_types=1);

namespace Domain\Users\Repositories;

use Domain\Users\Models\User;

class UserRepository
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::firstWhere('email', $email);
    }
}
