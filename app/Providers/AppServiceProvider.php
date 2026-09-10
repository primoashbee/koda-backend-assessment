<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->customResponses();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register the `{message, data}` JSON response macros used by the API controllers.
     */
    protected function customResponses(): void
    {
        Response::macro('paginated', function (
            LengthAwarePaginator $paginator,
            string $message = 'Data fetched successfully',
        ): JsonResponse {
            return response()->json([
                'message' => $message,
                'data' => $paginator->items(),
                'meta' => [
                    'currentPage' => $paginator->currentPage(),
                    'lastPage' => $paginator->lastPage(),
                    'perPage' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ]);
        });

        Response::macro('success', function (
            mixed $data = null,
            string $message = 'Request processed successfully',
        ): JsonResponse {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ]);
        });

        Response::macro('created', function (
            mixed $data = null,
            string $message = 'Data created successfully',
        ): JsonResponse {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ], 201);
        });

        Response::macro('updated', function (
            mixed $data = null,
            string $message = 'Data updated successfully',
        ): JsonResponse {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ]);
        });

        Response::macro('deleted', function (
            mixed $data = null,
            string $message = 'Data deleted successfully',
        ): JsonResponse {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ]);
        });
    }
}
