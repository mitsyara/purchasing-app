# Repository Pattern Refactoring

Dự án đã được refactor theo **Repository Pattern** để tách biệt business logic khỏi presentation layer và data access layer.

## Cấu trúc thư mục

```
app/
├── Repositories/
│   ├── Contracts/           # Interfaces cho repositories
│   │   ├── BaseRepositoryInterface.php
│   │   ├── PurchaseOrderRepositoryInterface.php
│   │   └── ContactRepositoryInterface.php
│   ├── BaseRepository.php   # Abstract base repository
│   ├── PurchaseOrderRepository.php
│   └── ContactRepository.php
├── Services/
│   ├── Core/               # Refactored business logic services
│   │   ├── PurchaseOrderService.php
│   │   ├── ContactService.php
│   │   ├── ValidationService.php
│   │   ├── PaymentService.php
│   │   ├── ExchangeRateService.php
│   │   └── InventoryService.php
│   ├── InventoryLine/      # Legacy (will be deprecated)
│   ├── Payment/           # Legacy (will be deprecated)
│   ├── Project/
│   ├── PurchaseOrder/     # Legacy (will be deprecated)
│   ├── PurchaseShipment/
│   └── VcbExchangeRatesService.php  # Legacy (moved to Core/ExchangeRateService)
├── Helpers/                # Utility classes
│   ├── OrderNumberGenerator.php
│   ├── DateHelper.php
│   └── StringHelper.php
└── Providers/
    └── RepositoryServiceProvider.php  # DI container configuration
```

## Nguyên tắc áp dụng

### 1. Repository Pattern
- **Interface**: Định nghĩa contract cho data access
- **Implementation**: Cụ thể hóa data access logic
- **Base Repository**: Cung cấp CRUD operations cơ bản

### 2. Service Layer
- **Business Logic**: Tách ra khỏi Models và Controllers
- **Validation**: Centralized validation logic
- **Single Responsibility**: Mỗi service chịu trách nhiệm cho một domain

### 3. Dependency Injection
- Services và Repositories được inject thông qua constructor
- Configured trong `RepositoryServiceProvider`
- Testable và loosely coupled

## Sử dụng

### Repository
```php
// Interface
interface PurchaseOrderRepositoryInterface 
{
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder;
    public function updateTotals(int $orderId): bool;
}

// Implementation
class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    protected function getModelClass(): string
    {
        return PurchaseOrder::class;
    }
    
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }
}
```

### Service
```php
class PurchaseOrderService
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $repository
    ) {}
    
    public function processOrder(int $orderId, array $data): bool
    {
        $this->validateOrderData($data);
        
        // Business logic here
        return $this->repository->update($orderId, $processedData);
    }
}
```

### Filament Pages
```php
class CreatePurchaseOrder extends CreateRecord
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService
    ) {
        parent::__construct();
    }
    
    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $this->purchaseOrderService->syncOrderInfo($record->id);
    }
}
```

## Lợi ích

### 1. Separation of Concerns
- **Models**: Chỉ chứa relationships và basic accessors/mutators
- **Services**: Business logic và validation
- **Repositories**: Data access abstraction
- **Forms**: UI configuration

### 2. Testability
- Mock repositories dễ dàng cho unit testing
- Services có thể test độc lập
- Clear dependencies

### 3. Maintainability
- Code tổ chức rõ ràng
- Dễ dàng thay đổi data source
- Reusable business logic

### 4. Code Reusability
- Shared helpers cho common tasks
- Business logic có thể sử dụng ở nhiều nơi
- Repository methods có thể dùng chung

## Migration từ code cũ

### Services Consolidation

#### Legacy Services → Core Services
```php
// Cũ - Multiple service classes
new \App\Services\PurchaseOrder\CallAllPurchaseOrderServices($order);
new \App\Services\PurchaseOrder\UpdateOrderTotals($order);
new \App\Services\Payment\PurchaseOrderPaymentService($order);

// Mới - Consolidated trong Core services
$purchaseOrderService->syncOrderInfo($orderId);
$paymentService->syncPurchaseOrderPayment($order);
```

#### VCB Exchange Rates
```php
// Cũ - Direct service usage
$rates = \App\Services\VcbExchangeRatesService::fetch($date);

// Mới - Core service với enhanced features
$exchangeService = app(\App\Services\Core\ExchangeRateService::class);
$rates = $exchangeService->fetchRates($date);
$convertedAmount = $exchangeService->convert(100, 'USD', 'VND');
```

#### Inventory Management
```php
// Cũ - Constructor-based services
new \App\Services\InventoryLine\SyncInfoToDescendants($transaction);
new \App\Services\InventoryLine\SyncFromShipmentLine($shipmentLine);

// Mới - Method-based service
$inventoryService = app(\App\Services\Core\InventoryService::class);
$inventoryService->syncInfoToDescendants($transaction);
$inventoryService->syncFromShipmentLine($shipmentLine);
```

### Model Methods (deprecated)
```php
// Cũ - trong Model
public function generateOrderNumber(): string
{
    // Business logic
}

// Mới - sử dụng Service
$orderNumber = app(PurchaseOrderService::class)->generateOrderNumber($data);
```

### Filament Forms
```php
// Cũ - business logic trong form action
->action(function (F\TextInput $component, ?PurchaseOrder $record, callable $get) {
    $orderNumber = (new PurchaseOrder())->generateOrderNumber([...]);
    $component->state($orderNumber);
})

// Mới - sử dụng service
->action(function (F\TextInput $component, ?PurchaseOrder $record, callable $get) {
    $service = app(PurchaseOrderService::class);
    $orderNumber = $service->generateOrderNumber($data);
    $component->state($orderNumber);
})
```

## Best Practices

1. **Luôn sử dụng interfaces** cho repositories
2. **Service methods phải stateless** và focused
3. **Validate input** trong service layer
4. **Use dependency injection** thay vì service locator
5. **Keep models lean** - chỉ relationships và basic logic
6. **Centralize common logic** trong helpers
7. **Test business logic** trong services

## Helpers Available

### OrderNumberGenerator
```php
OrderNumberGenerator::generatePurchaseOrderNumber($companyId, $orderDate);
OrderNumberGenerator::makeUnique($baseNumber, $modelClass, $column, $excludeId);
```

### DateHelper
```php
DateHelper::isValidDate($date, $format);
DateHelper::isDateInRange($date, $startDate, $endDate);
DateHelper::getBusinessDaysBetween($startDate, $endDate);
```

### PaymentService
```php
PaymentService::syncPurchaseOrderPayment($order, $userId);
PaymentService::syncPurchaseShipmentPayment($shipment, $userId);
PaymentService::getPendingPayments();
PaymentService::getOverduePayments();
PaymentService::markAsPaid($paymentId, $data);
```

### ExchangeRateService
```php
ExchangeRateService::fetchRates($date);
ExchangeRateService::getCurrentRates();
ExchangeRateService::getRate($fromCurrency, $toCurrency, $date);
ExchangeRateService::convert($amount, $fromCurrency, $toCurrency, $date);
```

### InventoryService
```php
InventoryService::syncInfoToDescendants($transaction);
InventoryService::syncFromShipmentLine($shipmentLine);
InventoryService::calculateStockLevel($productId, $warehouseId);
InventoryService::getLowStockProducts($warehouseId, $threshold);
```