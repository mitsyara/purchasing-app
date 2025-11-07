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
        // Đăng ký Services như singleton để tái sử dụng
        $this->app->singleton(\App\Services\Contact\ContactService::class);
        $this->app->singleton(\App\Services\PurchaseOrder\PurchaseOrderService::class);
        $this->app->singleton(\App\Services\Inventory\InventoryService::class);
        $this->app->singleton(\App\Services\Payment\PaymentService::class);
        $this->app->singleton(\App\Services\Project\ProjectService::class);
        $this->app->singleton(\App\Services\Common\ValidationService::class);
        $this->app->singleton(\App\Services\Common\ExchangeRateService::class);
        
        // Đăng ký PurchaseShipmentService với dependency injection
        $this->app->singleton(\App\Services\PurchaseShipment\PurchaseShipmentService::class, function ($app) {
            return new \App\Services\PurchaseShipment\PurchaseShipmentService(
                $app->make(\App\Services\Inventory\InventoryService::class)
            );
        });
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
