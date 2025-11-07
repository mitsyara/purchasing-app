<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\PurchaseShipmentRepositoryInterface;

// Repository Implementations
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\ContactRepository;
use App\Repositories\PurchaseShipmentRepository;

// Services
use App\Services\Core\PurchaseOrderService;
use App\Services\Core\PurchaseShipmentService;
use App\Services\Core\ContactService;
use App\Services\Core\ValidationService;
use App\Services\Core\PaymentService;
use App\Services\Core\ExchangeRateService;
use App\Services\Core\InventoryService;
use App\Services\Core\ProjectService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Implementations
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->bind(PurchaseShipmentRepositoryInterface::class, PurchaseShipmentRepository::class);

        // Register Services as Singletons
        $this->app->singleton(PurchaseOrderService::class, function ($app) {
            return new PurchaseOrderService(
                $app->make(PurchaseOrderRepositoryInterface::class)
            );
        });

        $this->app->singleton(PurchaseShipmentService::class, function ($app) {
            return new PurchaseShipmentService(
                $app->make(PurchaseOrderService::class),
                $app->make(PurchaseShipmentRepositoryInterface::class)
            );
        });

        $this->app->singleton(ContactService::class, function ($app) {
            return new ContactService(
                $app->make(ContactRepositoryInterface::class)
            );
        });

        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService();
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });

        $this->app->singleton(ExchangeRateService::class, function ($app) {
            return new ExchangeRateService();
        });

        $this->app->singleton(InventoryService::class, function ($app) {
            return new InventoryService();
        });

        $this->app->singleton(ProjectService::class, function ($app) {
            return new ProjectService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PurchaseOrderRepositoryInterface::class,
            ContactRepositoryInterface::class,
            PurchaseOrderService::class,
            PurchaseShipmentService::class,
            ContactService::class,
            ValidationService::class,
            PaymentService::class,
            ExchangeRateService::class,
            InventoryService::class,
        ];
    }
}