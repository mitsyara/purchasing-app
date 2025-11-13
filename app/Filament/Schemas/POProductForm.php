<?php

namespace App\Filament\Schemas;

use App\Filament\Clusters\Settings\Resources\Products\ProductResource;
use App\Models\{AssortmentProduct, Product, ProjectItem, PurchaseOrderLine, PurchaseOrder, Project, SalesOrder};
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
                ->columnSpanFull(),

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
                ->afterStateUpdatedJs(self::clearOppositeField('assortment_id'))
                ->columnSpanFull()
                ->requiredWithout(['assortment_id'])
                ->validationMessages([
                    'required_without' => __('At least one assortment or product must be selected.'),
                ]),

            // Quantity
            __number_field('qty')
                ->required(),

            // Unit price
            __number_field('unit_price')
                ->suffix(fn(\Livewire\Component $livewire, F\Field $component)
                => static::currencyContent($livewire, $component))
                ->required(),

            // Contract price
            __number_field('contract_price')
                ->suffix(fn(\Livewire\Component $livewire, F\Field $component)
                => static::currencyContent($livewire, $component)),
        ];
    }

    // Định nghĩa: lấy danh sách items của các loại order type
    protected static function getOrderItems(Model $order)
    {
        return match (true) {
            $order instanceof PurchaseOrder => $order->purchaseOrderLines(),
            $order instanceof SalesOrder => $order->salesOrderLines(),
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

    // Currency (concurrency)
    public static function currencyContent(\Livewire\Component $livewire, F\Field $component): mixed
    {
        if ($livewire instanceof RelationManager) {
            return $livewire->getOwnerRecord()?->currency;
        }

        // Đếm số cấp repeater cha
        $level = 0;
        $parent = $component->getParentRepeater();
        while ($parent) {
            $level++;
            $parent = $parent->getParentRepeater();
        }

        // Tạo prefix ../ cho mỗi cấp
        $prefix = str_repeat('../../', $level);

        // Trả về giá trị động
        $result = JsContent::make(sprintf('$get("%scurrency")', $prefix));
        return $result;
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
