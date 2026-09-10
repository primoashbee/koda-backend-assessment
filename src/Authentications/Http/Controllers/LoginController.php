<?php

declare(strict_types=1);

namespace Domain\Authentications\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Domain\Authentications\Actions\AuthenticateUserAction;
use Domain\Authentications\Actions\IssueApiTokenAction;
use Domain\Authentications\DTO\LoginDTO;
use Domain\Authentications\Http\Requests\LoginRequest;
use Domain\Users\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

#[Group('Authentication', weight: 1)]
class LoginController extends Controller
{
    /**
     * Verify the user's credentials and issue an API token.
     *
     * @response array{message: string, data: array{user: UserResource, token: string}}
     */
    public function __invoke(
        LoginRequest $request,
        AuthenticateUserAction $authenticateUserAction,
        IssueApiTokenAction $issueApiTokenAction,
    ): JsonResponse {
        $loginDTO = new LoginDTO(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $user = $authenticateUserAction->execute($loginDTO);

        return response()->success([
            'user' => UserResource::make($user),
            'token' => $issueApiTokenAction->execute($user),
        ], 'Logged in successfully');
    }
}
