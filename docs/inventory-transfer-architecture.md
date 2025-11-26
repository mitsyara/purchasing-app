# Cấu trúc Code Đồng nhất - Inventory Transfer

## 📁 **Cấu trúc tổ chức mới:**

### **1. Services (Pure Business Logic)**
```
app/Services/InventoryTransfer/
└── InventoryTransferService.php
```
- ✅ **Chỉ chứa business logic**
- ✅ Xử lý đồng bộ InventoryTransaction
- ✅ Tính toán costs, validation business rules
- ❌ **Không** chứa logic UI/Form

### **2. Resource Helpers (Filament UI Logic)**
```
app/Filament/Resources/InventoryTransfers/Helpers/
├── InventoryTransferFormHelper.php      // Form schemas
└── InventoryTransferResourceHelper.php  // Resource support
```

#### **InventoryTransferFormHelper.php:**
- ✅ `transferInfoSchema()` - Form components cho thông tin transfer
- ✅ `lotSelectionSchema()` - Form components cho lựa chọn lot
- ✅ **Chỉ** chứa Filament form components

#### **InventoryTransferResourceHelper.php:**
- ✅ `getLotOptionsWithBalance()` - Support methods cho Resource
- ✅ `calculateAvailableLotQty()` - Tính toán cho validation
- ✅ Wrapper methods để maintain compatibility
- ✅ Sử dụng Query và FormHelper

### **3. Model Queries (Database Logic)**
```
app/Models/Queries/
└── InventoryTransactionQuery.php
```
- ✅ `calculateLotBalance()` - Tính số dư lot
- ✅ `calculateAvailableLotBalance()` - Tính số dư có exclude
- ✅ `getLotsWithBalanceInWarehouse()` - Lấy lots có tồn
- ✅ **Tất cả** database logic tập trung tại đây

## 🔄 **Flow hoạt động:**

### **1. Resource Form:**
```php
Resource → FormHelper → Query
```
- Resource gọi `FormHelper::transferInfoSchema()`
- FormHelper gọi `Query::getLotsWithBalanceInWarehouse()`

### **2. Business Logic:**
```php
Resource → Service → Query
```
- Resource gọi `InventoryTransferService::sync()`
- Service xử lý business logic và gọi Query khi cần

### **3. Validation:**
```php
FormHelper → ResourceHelper → Query
```
- FormHelper validation rules gọi ResourceHelper
- ResourceHelper gọi Query để tính toán

## ✅ **Ưu điểm cấu trúc mới:**

### **Separation of Concerns:**
- **Service**: Pure business logic
- **FormHelper**: UI form components  
- **ResourceHelper**: Support logic for Resource
- **Query**: Database operations

### **Maintainability:**
- Logic rõ ràng, dễ debug
- Dễ test từng phần riêng biệt
- Code reusable giữa các Resource

### **Consistency:**
- Tất cả Resources follow same pattern
- Standardized naming conventions
- Clear responsibility boundaries

## 🎯 **Template cho Resources khác:**

```php
// Service - Business Logic
class SomeService {
    public function businessMethod() { /* pure logic */ }
}

// FormHelper - UI Components
trait SomeFormHelper {
    protected static function someSchema(): array { /* form components */ }
}

// ResourceHelper - Support Logic  
trait SomeResourceHelper {
    use SomeFormHelper;
    protected static function helperMethod() { /* support logic */ }
}

// Query - Database Logic
class SomeQuery extends Builder {
    public function queryMethod() { /* database logic */ }
}
```

Cấu trúc này đảm bảo:
- ✅ **Đồng nhất** across all Resources
- ✅ **Maintainable** và **Testable**
- ✅ **Scalable** khi project lớn
- ✅ **Clear separation** of responsibilities