<?php

namespace App\Filament\Resources\InventoryTransfers\Helpers;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;

trait InventoryTransferResourceHelper
{
    /**
     * Lấy danh sách lot với thông tin số dư (chỉ lot có tồn > 0)
     */
    protected static function getLotOptionsWithBalance(?int $warehouseId): array
    {
        if (!$warehouseId) return [];

        return \App\Models\InventoryTransaction::query()
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Import)
            ->where('warehouse_id', $warehouseId)
            ->with('product')
            ->get()
            ->mapWithKeys(function ($lot) {
                $availableQty = static::calculateLotBalance($lot);
                // Chỉ hiển thị lot có tồn kho > 0
                if ($availableQty <= 0) return [];
                
                $balance = __number_string_converter($availableQty);
                $label = "{$lot->lot_fifo} | Tồn: {$balance}";
                return [$lot->id => $label];
            })
            ->filter() // Loại bỏ các entry rỗng
            ->toArray();
    }

    /**
     * Tính số dư của lot (phiên bản đơn giản không cần exclude)
     */
    protected static function calculateLotBalance(\App\Models\InventoryTransaction $lot): float
    {
        if ($lot->transaction_direction !== \App\Enums\InventoryTransactionDirectionEnum::Import) {
            return 0;
        }

        $originalQty = $lot->qty ?? 0;
        $exportedQty = \App\Models\InventoryTransaction::query()
            ->where('parent_id', $lot->id)
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export)
            ->sum('qty');

        return max(0, $originalQty - $exportedQty);
    }

    /**
     * Static method để tính tồn kho theo lot ID - có thể gọi từ bên ngoài
     */
    public static function getLotBalance(int $lotId): float
    {
        $lot = \App\Models\InventoryTransaction::find($lotId);
        if (!$lot) return 0;

        return static::calculateLotBalance($lot);
    }

    /**
     * Tính số lượng còn lại của lot (không tính record đang edit)
     * Sử dụng logic hierarchy: Tồn = Qty gốc - SUM(tất cả export children)
     */
    protected static function calculateAvailableLotQty(
        int $lotId,
        ?int $excludeTransferId = null,
        ?int $excludeTransferLineId = null
    ): float {
        $lot = \App\Models\InventoryTransaction::find($lotId);
        if (!$lot || $lot->transaction_direction !== \App\Enums\InventoryTransactionDirectionEnum::Import) {
            return 0;
        }

        $originalQty = $lot->qty ?? 0;

        // Tính tổng số lượng đã xuất từ tất cả export transactions có parent_id là lot này
        $exportedQtyQuery = \App\Models\InventoryTransaction::query()
            ->where('parent_id', $lotId)
            ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export);

        // Loại trừ export transactions từ transfer đang edit (nếu có)
        if ($excludeTransferId && $excludeTransferLineId) {
            $exportedQtyQuery->where(function ($query) use ($excludeTransferId, $excludeTransferLineId) {
                $query->where('sourceable_type', '!=', \App\Models\InventoryTransferLine::class)
                      ->orWhere(function ($subQuery) use ($excludeTransferLineId) {
                          $subQuery->where('sourceable_type', \App\Models\InventoryTransferLine::class)
                                   ->where('sourceable_id', '!=', $excludeTransferLineId);
                      });
            });
        }

        $exportedQty = $exportedQtyQuery->sum('qty');

        return max(0, $originalQty - $exportedQty);
    }

    /**
     * Form components cho thông tin transfer
     */
    protected static function transferInfo(): array
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
    protected static function lotSelection(): array
    {
        return [
            F\Repeater::make('transferLines')
                ->label('Transfer Lines')
                ->relationship('transferLines')
                ->table([
                    F\Repeater\TableColumn::make('Lot Number')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Product Description')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Quantity')
                        ->width('150px')
                        ->markAsRequired(),
                ])
                ->schema([
                    F\Select::make('lot_id')
                        ->label('Lot Number')
                        ->options(fn(callable $get) => static::getLotOptionsWithBalance($get('../../from_warehouse_id')))
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
                        ->disabled(),

                    __number_field('transfer_qty')
                        ->rules([
                            fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (!$value || !is_numeric($value)) return;

                                $lotId = $get('lot_id');
                                if (!$lotId) return;

                                $transferId = $get('../../id');
                                $transferLineId = $get('id');

                                $availableQty = static::calculateAvailableLotQty($lotId, $transferId, $transferLineId);

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
}