<?php

declare(strict_types=1);

namespace Domain\Authentications\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = $request->string('email')
                ->lower()
                ->append('|', (string) $request->ip())
                ->transliterate()
                ->toString();

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
