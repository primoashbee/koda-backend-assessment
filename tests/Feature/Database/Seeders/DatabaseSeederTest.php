<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;

it('seeds one test user who can log in with the password "password"', function () {
    $this->seed();

    $testUser = User::sole();
    expect($testUser->email)->toBe('test@example.com')
        ->and($testUser->name)->toBe('Test User')
        ->and(Hash::check('password', $testUser->password))->toBeTrue();
    $this->assertDatabaseCount('projects', 12);
});

it('reuses the existing test user instead of failing when run again', function () {
    $this->seed();

    $this->seed();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('projects', 12);
});
