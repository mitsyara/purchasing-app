<?php

namespace App\Filament\Schemas;

use App\Filament\Clusters\Settings\Resources\Products\ProductResource;
use App\Models\{AssortmentProduct, Product, ProjectItem, PurchaseOrderLine, PurchaseOrder, Project};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\{Schema, JsContent};
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components as F;
use Filament\Infolists\Components as I;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Illuminate\Support\Collection;

class POProductForm
{
    // Configure schema
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::formSchema());
    }

    // Repeater headers
    public static function repeaterHeaders(): array
    {
        return [
            F\Repeater\TableColumn::make('Assortment')->width('280px'),
            F\Repeater\TableColumn::make('Product'),
            F\Repeater\TableColumn::make('Qty')->markAsRequired()->width('180px'),
            F\Repeater\TableColumn::make('Unit Price')->markAsRequired()->width('180px'),
            F\Repeater\TableColumn::make('Contract Price')->width('180px'),
        ];
    }

    // Main form schema
    public static function formSchema(): array
    {
        return [
            // Product UOM
            F\Hidden::make('product_uom')
                ->afterStateHydrated(fn(callable $get, $component) =>
                $component->state(Product::find($get('product_id'))?->product_uom))
                ->dehydrated(false),

            // Assortment select
            F\Select::make('assortment_id')
                ->label(__('Assortment'))
                ->relationship(
                    name: 'assortment',
                    titleAttribute: 'assortment_name',
                    modifyQueryUsing: function (Builder $query, string $operation, \Livewire\Component $livewire, ?Model $record): Builder {
                        if ($livewire instanceof RelationManager) {
                            $order = $livewire->getOwnerRecord();
                            $excluded = self::excludedAssortmentIds($order, $record);
                            if ($excluded->isNotEmpty()) {
                                $query->whereNotIn('id', $excluded);
                            }
                        }
                        return $operation === 'create'
                            ? $query->where('is_active', true)
                            : $query;
                    }
                )
                ->searchable()
                ->preload()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->afterStateUpdatedJs(self::clearOppositeField('product_id'))
                ->columnSpanFull()
                ->requiredWithout(['product_id']),

            // Product select
            F\Select::make('product_id')
                ->label(__('Product'))
                ->relationship(
                    name: 'product',
                    titleAttribute: 'product_description',
                    modifyQueryUsing: function (Builder $query, string $operation, \Livewire\Component $livewire, ?Model $record): Builder {
                        if ($livewire instanceof RelationManager) {
                            $order = $livewire->getOwnerRecord();
                            $excluded = self::excludedProductIds($order, $record);
                            if ($excluded->isNotEmpty()) {
                                $query->whereNotIn('id', $excluded);
                            }
                        }
                        return $operation === 'create'
                            ? $query->where('is_active', true)
                            : $query;
                    }
                )
                ->searchable()
                ->preload()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->createOptionForm(ProductResource::form(new Schema())->getComponents())
                ->editOptionForm(ProductResource::form(new Schema())->getComponents())
                ->afterStateUpdated(fn($state, $set) => $set('product_uom', Product::find($state)?->product_uom))
                ->afterStateUpdatedJs(self::clearOppositeField('assortment_id'))
                ->columnSpanFull()
                ->requiredWithout(['assortment_id']),

            // Quantity
            __number_field('qty')
                ->suffix(fn($get) => $get('product_id')
                    ? JsContent::make('$get("product_uom")')
                    : ($get('assortment_id') ? 'N/A' : null))
                ->required(),

            // Unit price
            __number_field('unit_price')
                ->suffix(fn(\Livewire\Component $livewire) =>
                $livewire instanceof RelationManager
                    ? $livewire->getOwnerRecord()?->currency
                    : JsContent::make('$get("../../currency")'))
                ->required(),

            // Contract price
            __number_field('contract_price')
                ->suffix(fn(\Livewire\Component $livewire) =>
                $livewire instanceof RelationManager
                    ? $livewire->getOwnerRecord()?->currency
                    : JsContent::make('$get("../../currency")')),
        ];
    }

    // Định nghĩa: lấy danh sách items của các loại order type
    protected static function getOrderItems(Model $order)
    {
        return match (true) {
            $order instanceof PurchaseOrder => $order->purchaseOrderLines(),
            $order instanceof Project => $order->projectItems(),
            default => collect(),
        };
    }

    // Lấy danh sách assortment bị loại
    protected static function excludedAssortmentIds(Model $order, ?Model $record): Collection
    {
        $orderItems = self::getOrderItems($order);

        $directAssortmentIds = $orderItems
            ->when($record, fn($q) => $q->whereNot('id', $record->id))
            ->pluck('assortment_id')
            ->filter();

        $selectedProductIds = $orderItems
            ->when($record, fn($q) => $q->whereNot('id', $record->id))
            ->whereNotNull('product_id')
            ->pluck('product_id');

        if ($selectedProductIds->isEmpty()) {
            return $directAssortmentIds;
        }

        $conflictAssortmentIds = AssortmentProduct::whereIn('product_id', $selectedProductIds)
            ->pluck('assortment_id');

        return $directAssortmentIds->merge($conflictAssortmentIds);
    }

    // Lấy danh sách product bị loại
    protected static function excludedProductIds(Model $order, ?Model $record): Collection
    {
        $orderItems = self::getOrderItems($order);

        $directProductIds = $orderItems
            ->when($record, fn($q) => $q->whereNot('id', $record->id))
            ->pluck('product_id')
            ->filter();

        $assortmentProductIds = $orderItems
            ->when($record, fn($q) => $q->whereNot('id', $record->id))
            ->whereNotNull('assortment_id')
            ->with('assortment.products:id')
            ->get()
            ->flatMap(fn($item) => $item->assortment?->products?->pluck('id') ?? []);

        return $directProductIds->merge($assortmentProductIds);
    }

    // JS helper
    protected static function clearOppositeField(string $field): string
    {
        return <<<JS
            if (\$state) {
                \$set('$field', null);
            }
        JS;
    }

    // Assortment field Tham khảo
    public static function assortmentField(): F\Select
    {
        return F\Select::make('assortment_id')
            ->label(__('Assortment'))
            ->relationship(
                name: 'assortment',
                titleAttribute: 'assortment_name',
                modifyQueryUsing: function (Builder $query, string $operation, \Livewire\Component $livewire, ?Model $record) {
                    if ($livewire instanceof RelationManager) {
                        $purchaseOrder = $livewire->getOwnerRecord();
                        $assortmentIds = $purchaseOrder->purchaseOrderLines()
                            ->when($record, fn(Builder $q) => $q->whereNot('id', $record->id))
                            ->pluck('assortment_id')
                            ->filter();
                        if ($assortmentIds->isNotEmpty()) {
                            $query->whereNotIn('id', $assortmentIds);
                        }
                    }
                    return $operation === 'create'
                        ? $query->where('is_active', true)
                        : $query;
                }
            )
            ->searchable()
            ->preload()
            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
            ->afterStateUpdatedJs(self::clearOppositeField('product_id'))
            ->live()
            ->skipRenderAfterStateUpdated()
            ->suffixAction(
                Action::make('viewProductList')
                    ->modal()
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->schema(fn(F\Field $component) => [
                        I\RepeatableEntry::make('products')
                            ->hiddenLabel()
                            ->getStateUsing(
                                Product::whereIn(
                                    'id',
                                    AssortmentProduct::whereAssortmentId($component->getState())->pluck('product_id')
                                )->get()
                            )
                            ->schema([
                                I\TextEntry::make('product_full_name')
                                    ->hiddenLabel()
                                    ->copyable(),
                            ])
                            ->contained(false),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->disabled(fn(F\Field $component): bool => empty($component->getState())),
            )
            ->columnSpanFull()
            ->requiredWithout(['product_id']);
    }
}
