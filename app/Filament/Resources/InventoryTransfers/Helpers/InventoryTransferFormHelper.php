<?php

namespace App\Filament\Resources\InventoryTransfers\Helpers;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;

trait InventoryTransferFormHelper
{
    /**
     * Form components cho thông tin transfer
     */
    protected static function transferInfoSchema(): array
    {
        return [
            S\Group::make([
                S\Flex::make([
                    F\Select::make('company_id')
                        ->label('Company')
                        ->relationship(
                            name: 'company',
                            titleAttribute: 'company_code',
                        )
                        ->live()
                        ->partiallyRenderAfterStateUpdated()
                        ->required(),

                    F\ToggleButtons::make('transfer_status')
                        ->label('Transfer Status')
                        ->options(\App\Enums\OrderStatusEnum::class)
                        ->default(\App\Enums\OrderStatusEnum::Draft)
                        ->grouped()
                        ->grow(false)
                        ->required(),

                    F\DatePicker::make('transfer_date')
                        ->label('Transfer Date')
                        ->default(today())
                        ->maxDate(today())
                        ->grow(false)
                        ->required(),

                ])
                    ->from('md')
                    ->columnSpanFull(),

                S\Group::make([
                    F\Select::make('from_warehouse_id')
                        ->label('From Warehouse')
                        ->relationship(
                            name: 'fromWarehouse',
                            titleAttribute: 'warehouse_name',
                        )
                        ->afterStateUpdatedJs(<<<'JS'
                                    $state == $get('to_warehouse_id') ? $set('to_warehouse_id', null) : null;
                                JS)
                        ->grow(false)
                        ->required(),

                    F\Select::make('to_warehouse_id')
                        ->label('To Warehouse')
                        ->relationship(
                            name: 'toWarehouse',
                            titleAttribute: 'warehouse_name',
                        )
                        ->afterStateUpdatedJs(<<<'JS'
                                    $state == $get('from_warehouse_id') ? $set('from_warehouse_id', null) : null;
                                JS)
                        ->grow(false)
                        ->required(),
                ]),

                __notes()->rows(5),

            ])
                ->columns()
                ->columnSpanFull(),

            // Extra costs form
            F\Repeater::make('extra_costs')
                ->label('Extra Costs')
                ->table([
                    F\Repeater\TableColumn::make('Reason'),
                    F\Repeater\TableColumn::make('Amount (VND)')
                        ->width('150px')
                        ->markAsRequired(),
                ])
                ->compact()
                ->schema([
                    F\TextInput::make('reason')
                        ->label('Reason')
                        ->required(),

                    __number_field('amount')
                        ->dehydrateStateUsing(fn($state) => $state ? (float) $state : null)
                        ->required(),
                ])
                ->addActionLabel('Add Cost')
                ->defaultItems(0)
                ->columnSpanFull(),
        ];
    }

    /**
     * Form components cho lựa chọn lot
     */
    protected static function lotSelectionSchema(): array
    {
        return [
            F\Repeater::make('transferLines')
                ->label('Transfer Lines')
                ->relationship('transferLines')
                ->table([
                    F\Repeater\TableColumn::make('Lot Number')
                        ->width('fit-content')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Product Description')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Quantity')
                        ->width('120px')
                        ->markAsRequired(),
                ])
                ->schema([
                    F\Select::make('lot_id')
                        ->label('Lot Number')
                        ->options(fn(callable $get) => static::getLotOptionsWithBalance(
                            $get('../../from_warehouse_id'),
                            static::getExcludeTransactionIds($get('../../id')) // lấy excludeIds từ transfer
                        ))
                        ->live()
                        ->partiallyRenderComponentsAfterStateUpdated(['product'])
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (!$state) return;
                            $lot = \App\Models\InventoryTransaction::find($state);
                            $set('product', $lot?->product?->product_description ?? '');
                        })
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required(),

                    F\TextInput::make('product')
                        ->label('Product Description')
                        ->dehydrated(false)
                        ->afterStateHydrated(fn(callable $get, F\Field $component) =>
                        $component->state(
                            \App\Models\InventoryTransaction::find($get('lot_id'))?->product?->product_description ?? ''
                        ))
                        ->disabled(),

                    __number_field('transfer_qty')
                        ->rules([
                            fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (!$value || !is_numeric($value)) return;

                                $lotId = $get('lot_id');
                                if (!$lotId) return;

                                $transferId = $get('../../id');
                                $excludeTransactionIds = static::getExcludeTransactionIds($transferId);

                                $availableQty = static::calculateAvailableLotQty($lotId, $excludeTransactionIds);

                                if ($value > $availableQty) {
                                    $fail("Số lượng chuyển kho không được vượt quá số lượng có sẵn trong lot ({$availableQty}).");
                                }
                            },
                        ])
                        ->required(),
                ])
                ->compact()
                ->columnSpanFull(),
        ];
    }

    /**
     * Lấy danh sách transaction IDs cần loại trừ dựa trên transfer ID
     */
    protected static function getExcludeTransactionIds(?int $transferId): array
    {
        if (!$transferId) return [];

        return \App\Models\InventoryTransaction::query()
            ->where('sourceable_type', \App\Models\InventoryTransferLine::class)
            ->whereIn('sourceable_id', function ($query) use ($transferId) {
                $query->select('id')
                    ->from('inventory_transfer_lines')
                    ->where('inventory_transfer_id', $transferId);
            })
            ->pluck('id')
            ->toArray();
    }
}
