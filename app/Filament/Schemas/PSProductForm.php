<?php

namespace App\Filament\Schemas;

use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\JsContent;
use Illuminate\Support\Collection;

class PSProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\Select::make('product_id')
                    ->label(__('Product'))
                    ->options(function (callable $get, \Livewire\Component $livewire): array|Collection {
                        $order = \App\Models\PurchaseOrder::find($get('../../purchase_order_id'));

                        if ($livewire instanceof RelationManager) {
                            $order = $livewire->getOwnerRecord();
                        }

                        $productIds = $order?->purchaseOrderLines()->pluck('product_id') ?? [];

                        return \App\Models\Product::whereIn('id', $productIds)->pluck('product_full_name', 'id');
                    })
                    ->live()
                    ->afterStateUpdated(fn($get, $set) => static::updateUnitPrice($get, $set))
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->required(),

                __number_field('qty')
                    ->required(),

                F\TextInput::make('unit_price')
                    ->hidden(),

                __number_field('break_price')
                    ->suffix('VND')
                    ->prefix(fn($get) => $get('product_id')
                    ? JsContent::make(<<<'JS'
                        $get('unit_price')
                    JS)
                    : null)
                    ->required(),
            ]);
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
            // F\Repeater\TableColumn::make('Unit Price')->width('180px'),
            F\Repeater\TableColumn::make('Break Price')
                ->markAsRequired()
                ->width('280px'),
        ];
    }

    public static function updateUnitPrice($get, $set): void
    {
        $set('unit_price', '100,00 USD');
    }
}
