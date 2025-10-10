<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        //// Strict mode
        $isDevelopment = !app()->isProduction();
        // Enable strict model mode for development
        \Illuminate\Database\Eloquent\Model::shouldBeStrict($isDevelopment);
        // Prevent lazy loading in dev (helps detect N+1)
        \Illuminate\Database\Eloquent\Model::preventLazyLoading($isDevelopment);
        // Automatically eager load relationships to reduce N+1 queries
        \Illuminate\Database\Eloquent\Model::automaticallyEagerLoadRelationships();

        // Vite FE Prefetch, 3 per batch
        \Illuminate\Support\Facades\Vite::prefetch(concurrency: 3);

        // Define Super Administrator
        \Illuminate\Support\Facades\Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->id === 1) {
                return true;
            }
        });

        // App Policies auto-discover
        \Illuminate\Support\Facades\Gate::guessPolicyNamesUsing(function (string $modelClass) {
            return str_replace('Models', 'Policies', $modelClass) . 'Policy';
        });
    }
}
