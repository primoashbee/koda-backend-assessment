<?php

declare(strict_types=1);

namespace Domain\Authentications\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Domain\Authentications\Actions\IssueApiTokenAction;
use Domain\Authentications\Actions\RegisterUserAction;
use Domain\Authentications\DTO\RegisterUserDTO;
use Domain\Authentications\Http\Requests\RegisterRequest;
use Domain\Users\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

#[Group('Authentication', weight: 1)]
class RegisterController extends Controller
{
    /**
     * Register a new user and issue an API token.
     *
     * @response JsonResponse<array{message: string, data: array{user: UserResource, token: string}}, 201>
     */
    public function __invoke(
        RegisterRequest $request,
        RegisterUserAction $registerUserAction,
        IssueApiTokenAction $issueApiTokenAction,
    ): JsonResponse {
        $registerUserDTO = new RegisterUserDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $user = $registerUserAction->execute($registerUserDTO);

        return response()->created([
            'user' => UserResource::make($user),
            'token' => $issueApiTokenAction->execute($user),
        ], 'User registered successfully');
    }
}
