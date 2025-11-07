<?php

namespace App\Traits\Filament;

use App\Filament\Resources\Projects\RelationManagers\ProjectShipmentsRelationManager;
use App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager;
use App\Models\Project;
use App\Models\ProjectShipmentItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseShipmentLine;
use App\Models\AssortmentProduct;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component as Livewire;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait cho validation và recommendation của shipment line quantity
 * Logic đơn giản và rõ ràng cho cả product và assortment
 */
trait HasShipmentLineValidation
{
    // Định nghĩa các class có thể dùng Trait
    protected array $allowedClasses = [
        PurchaseShipmentsRelationManager::class,
        ProjectShipmentsRelationManager::class,
    ];

    /**
     * Config định nghĩa các relationships cho từng model type
     */
    protected function getModelConfig(): array
    {
        return [
            \App\Models\PurchaseOrder::class => [
                'order_lines_relation' => 'purchaseOrderLines',
                'shipments_relation' => 'purchaseShipments',
                'shipment_lines_relation' => 'purchaseShipmentLines',
                'shipment_line_model' => \App\Models\PurchaseShipmentLine::class,
                'shipment_id' => 'purchase_shipment_id',
            ],
            \App\Models\Project::class => [
                'order_lines_relation' => 'projectItems',
                'shipments_relation' => 'projectShipments',
                'shipment_lines_relation' => 'projectShipmentItems',
                'shipment_line_model' => \App\Models\ProjectShipmentItem::class,
                'shipment_id' => 'project_shipment_id',
            ],
        ];
    }

    /**
     * Lấy config cho model hiện tại
     */
    protected function getCurrentModelConfig(): array
    {
        $owner = $this->getOwnerRecord();
        $config = $this->getModelConfig();

        foreach ($config as $modelClass => $modelConfig) {
            if ($owner instanceof $modelClass) {
                return $modelConfig;
            }
        }

        throw new \Exception('Model type not supported: ' . get_class($owner));
    }

    /**
     * Kiểm tra xem implement đúng chỗ không?
     * Được gọi trong __construct của class sử dụng trait
     */
    protected function validateTraitUsage(): void
    {
        if (!in_array(static::class, $this->allowedClasses, true)) {
            throw new \Exception(static::class . " is not allowed to use trait " . __TRAIT__);
        }
    }

    /**
     * Tính toán available quantity cho shipment line hiện tại
     * Logic:
     * 1. Xác định order line tương ứng (theo product hoặc assortment)
     * 2. Lấy tổng quantity đã shipment (ngoại trừ shipment hiện tại)
     * 3. Lấy tổng quantity trong repeater hiện tại (tới key hiện tại, gom theo orderline)
     * 4. Available = order line qty - đã ship của shipment khác - tổng trong repeater hiện tại
     */
    public function lineInfo(Field $component, ?Model $shipment, int $productId): ?array
    {
        // Nếu ko có product, trả null
        if (!$productId) {
            return null;
        }

        $owner = $this->getOwnerRecord();
        $config = $this->getCurrentModelConfig();

        // Tìm orderline tương ứng với product
        $orderLine = $this->findOrderLineForProduct($owner, $productId, $config);

        if (!$orderLine) {
            return [
                'available_qty' => 0,
                'order_line_qty' => 0,
                'shipped_qty' => 0,
                'current_repeater_qty' => 0,
                'unit_price' => 0,
                'contract_price' => 0,
            ];
        }

        // Parse orderline qty từ string về float để tính toán
        $orderLineQty = (float) $orderLine->qty;

        // Lấy tổng đã shipped (ngoại trừ shipment hiện tại)
        $shippedQty = $this->getShippedQuantity($owner, $productId, $shipment, $config);

        // Lấy tổng trong repeater hiện tại (tới key hiện tại, gom theo orderline)
        $currentRepeaterQty = $this->getCurrentRepeaterQuantity($component, $productId);

        // Tính available quantity bằng float
        $availableQty = $orderLineQty - $shippedQty - $currentRepeaterQty;

        // Trả về kết quả với format đúng (convert lại string cho display)
        return [
            'available_qty' => $availableQty,
            'order_line_qty' => $orderLineQty,
            'shipped_qty' => $shippedQty,
            'current_repeater_qty' => $currentRepeaterQty,
            'unit_price' => $orderLine->unit_price,
            'contract_price' => $orderLine->contract_price,
        ];
    }

    /**
     * Tìm order line tương ứng với product ID
     */
    protected function findOrderLineForProduct($owner, int $productId, array $config): ?Model
    {
        $orderLinesRelation = $config['order_lines_relation'];

        // Tìm direct product line
        $directLine = $owner->{$orderLinesRelation}()
            ->where('product_id', $productId)
            ->first();

        if ($directLine) {
            return $directLine;
        }

        // Tìm assortment line chứa product này
        $assortmentLine = $owner->{$orderLinesRelation}()
            ->whereNotNull('assortment_id')
            ->whereHas('assortment.products', function ($query) use ($productId) {
                $query->where('products.id', $productId);
            })
            ->first();

        return $assortmentLine;
    }

    /**
     * Lấy tổng quantity đã shipped cho orderline (ngoại trừ shipment hiện tại)
     * Logic: Gom theo orderline, nếu là assortment thì gom tất cả products trong assortment
     */
    protected function getShippedQuantity(Model $owner, int $productId, ?Model $currentShipment, array $config): float
    {
        $shipmentLinesRelation = $config['shipment_lines_relation'];

        $shipmentId = $config['shipment_id'];

        // Tìm orderline tương ứng
        $orderLine = $this->findOrderLineForProduct($owner, $productId, $config);

        if (!$orderLine) {
            return 0;
        }

        // Lấy danh sách product IDs cần tính
        $targetProductIds = $this->getTargetProductIds($orderLine, $productId);

        $query = $owner->{$shipmentLinesRelation}()
            ->whereIn('product_id', $targetProductIds);

        // Loại bỏ lines thuộc shipment hiện tại
        if ($currentShipment) {
            $query->where($shipmentId, '!=', $currentShipment->id);
        }

        return $query->sum('qty') ?? 0;
    }

    /**
     * Lấy tổng quantity trong repeater hiện tại (tới key hiện tại)
     * Logic:
     * 1. Lấy toàn bộ repeater data
     * 2. Lấy product_id hiện tại, xác định nó thuộc orderline nào (direct product hoặc assortment)
     * 3. Nếu là assortment: gom tất cả products thuộc cùng assortment
     * 4. Loop foreach tính tổng quantity tới key hiện tại
     */
    protected function getCurrentRepeaterQuantity(Field $component, int $productId): float
    {
        $owner = $this->getOwnerRecord();
        $config = $this->getCurrentModelConfig();

        // Lấy repeater data
        $allRepeaterItems = $component->getParentRepeater()->getState();

        // Key statePath, tách dấu chấm lấy phần tử thứ 2 từ dưới lên
        $keys = explode('.', $component->getStatePath());
        $currentKey = array_slice($keys, -2, 1)[0];

        // Tìm orderline tương ứng với product hiện tại
        $orderLine = $this->findOrderLineForProduct($owner, $productId, $config);

        if (!$orderLine) {
            return 0;
        }

        // Xác định danh sách product IDs cần tính tổng
        $targetProductIds = $this->getTargetProductIds($orderLine, $productId);

        $totalQty = 0;

        // Loop foreach qua tất cả repeater items
        foreach ($allRepeaterItems as $key => $item) {
            // Kiểm tra nếu đã tới key hiện tại thì dừng
            if ($key == $currentKey) {
                break;
            }

            // Nếu item có product_id thuộc target products thì cộng qty
            if (isset($item['product_id']) && in_array((int) $item['product_id'], $targetProductIds)) {
                // Parse qty từ string về float (vì có thể là formatted string từ __number_field)
                $qty = isset($item['qty']) ? (float) __number_string_converter($item['qty'], false) : 0;
                $totalQty += $qty;
            }
        }

        return $totalQty;
    }

    /**
     * Lấy danh sách product IDs cần tính tổng dựa trên orderline
     * Nếu là direct product: chỉ product đó
     * Nếu là assortment: tất cả products trong assortment
     */
    protected function getTargetProductIds(Model $orderLine, int $currentProductId): array
    {
        // Nếu orderline có direct product_id
        if ($orderLine->product_id) {
            return [(int) $orderLine->product_id];
        }

        // Nếu orderline có assortment_id
        if ($orderLine->assortment_id) {
            $productIds = \App\Models\AssortmentProduct::where('assortment_id', $orderLine->assortment_id)
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            return $productIds ?: [$currentProductId];
        }

        // Fallback: chỉ current product
        return [$currentProductId];
    }

    /**
     * Handler cho afterStateUpdated của product select field
     * Tự động set qty, unit_price, contract_price dựa trên calculation
     */
    protected function handleProductSelectionUpdate(?string $state, Field $component, Set $set, ?Model $shipment): void
    {
        $calculation = $this->lineInfo(
            $component,
            $shipment,
            (int) $state,
        );

        // Set recommended qty nếu có available qty
        if ($calculation && $calculation['available_qty'] > 0) {
            $availableQty = ($calculation['available_qty'] ?? null)
                ? __number_string_converter($calculation['available_qty']) : null;
            $set('qty', $availableQty);
        } else {
            $set('qty', null);
        }

        // Set unit price và contract price từ order line
        $unitPrice = ($calculation['unit_price'] ?? null)
            ? __number_string_converter($calculation['unit_price']) : null;
        $contractPrice = ($calculation['contract_price'] ?? null)
            ? __number_string_converter($calculation['contract_price']) : null;

        $set('unit_price', $unitPrice);
        $set('contract_price', $contractPrice);
    }

    /**
     * Validation rule cho quantity field
     * Kiểm tra không vượt quá available quantity
     */
    protected function createQuantityValidationRule(Get $get, Field $component, ?Model $shipment): Closure
    {
        $self = $this;
        return function (string $attribute, $value, Closure $fail) use ($get, $shipment, $component, $self) {
            $calculation = $self->lineInfo(
                $component,
                $shipment,
                (int) $get('product_id'),
            );

            $validQty = ($calculation['order_line_qty'] ?? 0) - ($calculation['shipped_qty'] ?? 0);

            if ($calculation['available_qty'] < 0) {
                $fail("Remaining: {$validQty}");
            }
        };
    }

    /**
     * Filter products cho shipment
     * bao gồm cả direct products và assortment products bằng relationship định nghĩa sẵn theo config
     */
    protected function filterProductsForShipment(Builder $query): Builder
    {
        $owner = $this->getOwnerRecord();

        if (!$owner) {
            // No products if no owner
            return $query->whereRaw('1 = 0');
        }

        try {
            $config = $this->getCurrentModelConfig();
            $orderLinesRelation = $config['order_lines_relation'];

            // Lấy direct product IDs từ order lines
            $directProductIds = $owner->{$orderLinesRelation}()
                ->whereNotNull('product_id')
                ->pluck('product_id');

            // Lấy assortment product IDs từ order lines
            $assortmentIds = $owner->{$orderLinesRelation}()
                ->whereNotNull('assortment_id')
                ->pluck('assortment_id');

            // Lấy products từ assortments
            $assortmentProductIds = collect();
            if ($assortmentIds->isNotEmpty()) {
                $assortmentProductIds = \App\Models\AssortmentProduct::whereIn('assortment_id', $assortmentIds)
                    ->pluck('product_id');
            }

            // Combine cả direct products và assortment products
            $allProductIds = $directProductIds->merge($assortmentProductIds)->unique();

            return $query->whereIn('id', $allProductIds);
        } catch (\Exception $e) {
            // Fallback nếu có lỗi
            return $query->whereRaw('1 = 0');
        }
    }
}
