<?php

namespace App\Filament\Schemas;

use App\Filament\Clusters\Settings\Resources\Products\ProductResource;
use App\Models\AssortmentProduct;
use App\Models\Product;
use Filament\Resources\RelationManagers\RelationManager;

use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Builder;

use Filament\Schemas\Schema;
use Filament\Schemas\JsContent;
use Filament\Support\Icons\Heroicon;

use Filament\Forms\Components as F;
use Filament\Infolists\Components as I;

class POProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::formSchema());
    }

    public static function formSchema(): array
    {
        return [
            F\Hidden::make('product_uom')
                ->afterStateHydrated(fn($get) => \App\Models\Product::find($get('product'))?->product_uom)
                ->dehydrated(false),

            F\Select::make('product_id')
                ->label(__('Product'))
                ->relationship(
                    name: 'product',
                    titleAttribute: 'product_description',
                    modifyQueryUsing: function (
                        Builder $query,
                        string $operation,
                        \Livewire\Component $livewire,
                        ?PurchaseOrderLine $record,
                    ): Builder {
                        if ($livewire instanceof RelationManager) {
                            $purchaseOrder = $livewire->getOwnerRecord();
                            $productIds = $purchaseOrder->purchaseOrderLines()
                                ->when($record, fn(Builder $q) => $q->whereNot('id', $record->id))
                                ->pluck('product_id')
                                ->filter();
                            if ($productIds) {
                                $query = $query->whereNotIn('id', $productIds);
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
                ->afterStateUpdated(fn($state, $set) => $set('product_uom', \App\Models\Product::find($state)?->product_uom))
                ->afterStateUpdatedJs(<<<'JS'
                    $state ? $set('assortment_id', null) : null;
                JS)
                ->columnSpanFull()
                ->requiredWithout(['assortment_id']),

            __number_field('qty')
                ->suffix(fn($get) => $get('product_id') ? JsContent::make(<<<'JS'
                    $get('product_uom')
                JS) : null)
                ->required(),

            __number_field('unit_price')
                ->suffix(fn(\Livewire\Component $livewire)
                => $livewire instanceof RelationManager
                    ? $livewire->getOwnerRecord()->currency
                    : JsContent::make(<<<'JS'
                        $get('../../currency')
                JS))
                ->required(),

            __number_field('contract_price')
                ->suffix(fn(\Livewire\Component $livewire)
                => $livewire instanceof RelationManager
                    ? $livewire->getOwnerRecord()->currency
                    : JsContent::make(<<<'JS'
                        $get('../../currency')
                    JS)),
        ];
    }

    public static function repeaterHeaders(): array
    {
        return [
            // F\Repeater\TableColumn::make('Assortment')
            //     ->width('280px'),
            F\Repeater\TableColumn::make('Product'),
            F\Repeater\TableColumn::make('Qty')
                ->markAsRequired()
                ->width('180px'),
            F\Repeater\TableColumn::make('Unit Price')
                ->markAsRequired()
                ->width('180px'),
            F\Repeater\TableColumn::make('Contract Price')
                ->width('180px'),
        ];
    }

    public static function assortmentField(): F\Select
    {
        return F\Select::make('assortment_id')
            ->label(__('Assortment'))
            ->relationship(
                name: 'assortment',
                titleAttribute: 'assortment_name',
                modifyQueryUsing: function (
                    Builder $query,
                    string $operation,
                    \Livewire\Component $livewire,
                    ?PurchaseOrderLine $record,
                ): Builder {
                    if ($livewire instanceof RelationManager) {
                        $purchaseOrder = $livewire->getOwnerRecord();
                        $assortmentIds = $purchaseOrder->purchaseOrderLines()
                            ->when($record, fn(Builder $q) => $q->whereNot('id', $record->id))
                            ->pluck('assortment_id')
                            ->filter();
                        if ($assortmentIds) {
                            $query = $query->whereNotIn('id', $assortmentIds);
                        }
                    }
                    return $operation === 'create' ? $query->where('is_active', true) : $query;
                }
            )
            ->searchable()
            ->preload()
            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
            ->afterStateUpdatedJs(<<<'JS'
                    $state ? $set('product_id', null) : null;
                JS)
            ->live()
            ->skipRenderAfterStateUpdated()
            ->suffixAction(
                \Filament\Actions\Action::make('viewProductList')
                    ->modal()->icon(Heroicon::Eye)->color('primary')
                    ->schema(fn(F\Field $component) => [
                        I\RepeatableEntry::make('products')
                            ->hiddenLabel()
                            ->getStateUsing(
                                Product::whereIn('id', AssortmentProduct::whereAssortmentId($component->getState())->pluck('product_id'))
                                    ->get()
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
