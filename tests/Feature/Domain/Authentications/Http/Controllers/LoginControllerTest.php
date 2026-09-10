<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

pest()->group('authentications', 'domain');

it('returns 200 with an api token for valid credentials', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Logged in successfully')
        ->assertJsonStructure(['message', 'data' => ['user' => ['id', 'name', 'email', 'createdAt'], 'token']])
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonMissingPath('data.user.password');
    expect(PersonalAccessToken::findToken($response->json('data.token'))?->tokenable?->is($user))->toBeTrue();
});

it('returns 422 when the password is incorrect', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.'])
        ->assertJsonMissingPath('data.token');
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('returns 422 when the email is not registered', function () {
    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'nobody@example.com',
        'password' => 'secret-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.'])
        ->assertJsonMissingPath('data.token');
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('returns 422 when required fields are missing', function () {
    $response = $this->postJson(route('api.v1.auth.login'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
});

it('returns 429 after five login attempts within a minute', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $response->assertTooManyRequests();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
