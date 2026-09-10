<?php

declare(strict_types=1);

namespace Domain\Authentications\Actions;

use Domain\Authentications\DTO\LoginDTO;
use Domain\Users\Models\User;
use Domain\Users\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {}

    /**
     * Resolve the user matching the given credentials.
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $loginDTO): User
    {
        $user = $this->userRepository->findByEmail($loginDTO->email);

        if (! $user || ! Hash::check($loginDTO->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }
}
