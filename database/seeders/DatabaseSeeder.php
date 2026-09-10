<?php

namespace Database\Seeders;

use Domain\Projects\Database\Seeders\ProjectSeeder;
use Domain\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::query()->firstOrNew(['email' => 'test@example.com']);
        $testUser->name = 'Test User';
        $testUser->password = 'password';
        $testUser->email_verified_at ??= now();
        $testUser->save();

        $this->call(ProjectSeeder::class);
    }
}
