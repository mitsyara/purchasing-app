# Script Refactor All Resources

Tôi sẽ áp dụng cấu trúc đồng nhất cho tất cả Resources. Dưới đây là những Resources đã hoàn thành và cần làm:

## ✅ **Hoàn thành:**
1. **InventoryTransfers** - Refactored hoàn toàn
2. **SalesShipments** - Đang refactor

## 🔄 **Cần refactor:**

### **PurchaseOrders** (có sẵn Schema pattern - OK)
- Đã có PurchaseOrderForm, PurchaseOrderInfolist, PurchaseOrdersTable
- ✅ **Cấu trúc tốt, không cần refactor**

### **SalesOrders** (có Schema pattern - OK)  
- Đã có SalesOrderForm, SalesOrdersTable
- ✅ **Cấu trúc tốt, không cần refactor**

### **Projects** (có Schema pattern - OK)
- Đã có ProjectForm, ProjectInfolist, ProjectsTable
- ✅ **Cấu trúc tốt, không cần refactor**

### **Contacts** (có Schema pattern - OK)
- Đã có ContactForm, ContactTable
- ✅ **Cấu trúc tốt, không cần refactor**

### **PurchaseShipments** (có Schema pattern - OK)
- Đã có PurchaseShipmentForm, PurchaseShipmentInfolist, PurchaseShipmentTable
- ✅ **Cấu trúc tốt, không cần refactor**

### **InventoryAdjustments** ⚠️
- Có cấu trúc phức tạp, cần refactor cẩn thận
- 🔄 **Cần refactor nhưng phức tạp**

### **InventoryTransactions** 🔄
- Chưa có Helper, cần tạo mới
- 🔄 **Cần refactor**

### **Roles** 🔄
- Simple Resource, dễ refactor
- 🔄 **Cần refactor**

### **SalesDeliveryScheduleLines** 🔄
- Simple Resource, dễ refactor  
- 🔄 **Cần refactor**

## 📋 **Kế hoạch thực hiện:**

### **Phase 1 - Simple Resources:**
1. InventoryTransactions
2. Roles  
3. SalesDeliveryScheduleLines

### **Phase 2 - Complex Resources:**
1. InventoryAdjustments (cẩn thận)

### **Phase 3 - Final Check:**
1. Review tất cả Resources
2. Update documentation
3. Create Query classes nếu cần

## 🎯 **Template chuẩn:**

```php
// Resource
class SomeResource extends Resource
{
    use Helpers\SomeResourceHelper;
    // ... config
}

// FormHelper (trait)
trait SomeFormHelper
{
    protected static function someSchema(): array { /* forms */ }
}

// ResourceHelper (trait)  
trait SomeResourceHelper
{
    use SomeFormHelper;
    protected static function helperMethod() { /* logic */ }
}

// Service (class)
class SomeService 
{
    public function businessMethod() { /* pure logic */ }
}

// Query (class)
class SomeQuery extends Builder
{
    public function queryMethod() { /* database */ }
}
```