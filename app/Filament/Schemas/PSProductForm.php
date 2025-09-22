<?php

namespace App\Filament\Schemas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Livewire\Component as Livewire;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;

class PSProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            F\Select::make('product_id')
                ->label(__('Product'))
                ->options(fn($get, Livewire $livewire) => static::getProductOptions($get, $livewire))
                ->live()
                ->afterStateUpdated(fn($get, $set, $livewire, ?Model $record)
                => static::updateShipmentLineInfo($get, $set, $livewire, $record))
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->required(),

            __number_field('qty')
                ->required()
                ->rules([
                    fn(Get $get, ?Model $record, Livewire $livewire): \Closure =>
                    fn(string $attribute, $value, \Closure $fail) =>
                    static::validateQty($get, $livewire, $record, $value, $fail),
                ]),

            __number_field('break_price')
                ->prefix(function ($get) {
                    $locale = app()->getLocale();
                    return $get('product_id') ? JsContent::make(<<<'JS'
                            $get('currency') && $get('unit_price')
                            ? new Intl.NumberFormat("en-US", {
                                style: "currency",
                                currency: $get('currency')
                            }).format($get('unit_price'))
                            : null
                        JS) : null;
                })
                ->suffix('VND')
                ->required(),

            // Hidden fields
            S\Group::make([
                F\Hidden::make('purchase_order_id')->dehydrated(),
                F\Hidden::make('purchase_order_line_id')->dehydrated(),
                F\Hidden::make('company_id')->dehydrated(),
                F\Hidden::make('currency')->dehydrated(),
                F\Hidden::make('average_cost')->dehydrated(),
                F\Hidden::make('unit_price')->dehydrated(),
            ])
                ->hidden(),
        ]);
    }

    public static function repeaterHeaders(): array
    {
        return [
            F\Repeater\TableColumn::make('Product'),
            F\Repeater\TableColumn::make('Qty')
                ->markAsRequired()
                ->width('180px'),
            F\Repeater\TableColumn::make('Break Price')
                ->markAsRequired()
                ->width('280px'),
        ];
    }

    /* ==================== Helper Methods ==================== */

    public static function updateShipmentLineInfo(Get $get, Set $set, Livewire $livewire, ?Model $record): void
    {
        $orderLine = static::getOrderLine($get, $livewire);

        $set('purchase_order_id', $orderLine?->purchase_order_id);
        $set('purchase_order_line_id', $orderLine?->id);
        $set('company_id', $orderLine?->company_id);
        $set('currency', $orderLine?->currency);
        $set('unit_price', $orderLine?->unit_price);

        // Remaining Order's qty
        $remaining = $orderLine ? static::getRemainingQty($orderLine, $record) : 0;
        $set('qty', $remaining > 0 ? __number_string_converter_vi($remaining) : null);
    }

    protected static function getProductOptions(callable $get, Livewire $livewire): array|Collection
    {
        $order = \App\Models\PurchaseOrder::find($get('../../purchase_order_id'));

        if ($livewire instanceof RelationManager) {
            $order = $livewire->getOwnerRecord();
        }

        $productIds = $order?->purchaseOrderLines()->pluck('product_id') ?? [];

        return \App\Models\Product::whereIn('id', $productIds)->pluck('product_full_name', 'id');
    }

    protected static function getOrderLine(Get $get, Livewire $livewire): ?\App\Models\PurchaseOrderLine
    {
        $selectedOrder = (int) $get('../../purchase_order_id');
        if ($livewire instanceof RelationManager) {
            $selectedOrder = $livewire->getOwnerRecord()?->id ?? 0;
        }

        $selectedProduct = (int) $get('product_id');

        return \App\Models\PurchaseOrderLine::where('purchase_order_id', $selectedOrder)
            ->where('product_id', $selectedProduct)
            ->first();
    }

    protected static function getRemainingQty(\App\Models\PurchaseOrderLine $orderLine, ?Model $record): float
    {
        $createdQty = \App\Models\PurchaseShipmentLine::query()
            ->when($record, fn($query) => $query->whereNot('id', $record?->id))
            ->where('purchase_order_id', $orderLine->purchase_order_id)
            ->where('product_id', $orderLine->product_id)
            ->sum('qty') ?? 0;

        return (float) $orderLine->qty - (float) $createdQty;
    }

    protected static function validateQty(
        Get $get,
        Livewire $livewire,
        ?Model $record,
        mixed $value,
        \Closure $fail
    ): void {
        $orderLine = static::getOrderLine($get, $livewire);

        if (! $orderLine) {
            return;
        }

        $remaining = static::getRemainingQty($orderLine, $record);

        if ($value > $remaining) {
            $fail("Qty exceeds Order's qty. Remaining: {$remaining}");
        }
    }
}
