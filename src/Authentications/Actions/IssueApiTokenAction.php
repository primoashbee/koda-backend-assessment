<?php

declare(strict_types=1);

namespace Domain\Authentications\Actions;

use Domain\Users\Models\User;

class IssueApiTokenAction
{
    /**
     * Issue a Sanctum token for the user and return its plain-text value.
     */
    public function execute(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}
