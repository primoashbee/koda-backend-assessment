<?php

declare(strict_types=1);

namespace Domain\Authentications\Actions;

use Domain\Authentications\DTO\RegisterUserDTO;
use Domain\Users\Models\User;
use Domain\Users\Repositories\UserRepository;

class RegisterUserAction
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {}

    public function execute(RegisterUserDTO $registerUserDTO): User
    {
        return $this->userRepository->create([
            'name' => $registerUserDTO->name,
            'email' => $registerUserDTO->email,
            'password' => $registerUserDTO->password,
        ]);
    }
}
