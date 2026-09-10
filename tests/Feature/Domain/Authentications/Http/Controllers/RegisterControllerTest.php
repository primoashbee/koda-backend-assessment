<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

pest()->group('authentications', 'domain');

it('creates the user and returns 201 with an api token', function () {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'User registered successfully')
        ->assertJsonStructure(['message', 'data' => ['user' => ['id', 'name', 'email', 'createdAt'], 'token']])
        ->assertJsonPath('data.user.name', 'Jane Doe')
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonMissingPath('data.user.password');

    $user = User::firstWhere('email', 'jane@example.com');
    expect($user)->not->toBeNull()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and(PersonalAccessToken::findToken($response->json('data.token'))?->tokenable?->is($user))->toBeTrue();
});

it('returns 422 when required fields are missing', function () {
    $response = $this->postJson(route('api.v1.auth.register'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
    $this->assertDatabaseCount('users', 0);
});

it('returns 422 when a field is invalid', function (array $overrides, string $field, string $message) {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        ...$overrides,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
    $this->assertDatabaseCount('users', 0);
})->with([
    'name longer than 255 characters' => [['name' => str_repeat('a', 256)], 'name', 'The name field must not be greater than 255 characters.'],
    'malformed email' => [['email' => 'not-an-email'], 'email', 'The email field must be a valid email address.'],
    'password shorter than 8 characters' => [['password' => 'short'], 'password', 'The password field must be at least 8 characters.'],
]);

it('returns 422 when the email is already registered', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'The email has already been taken.']);
    $this->assertDatabaseCount('users', 1);
});
