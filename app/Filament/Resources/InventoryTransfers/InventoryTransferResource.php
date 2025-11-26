<?php

namespace App\Filament\Resources\InventoryTransfers;

use App\Filament\Resources\InventoryTransfers\Helpers\InventoryTransferResourceHelper;
use App\Filament\Resources\InventoryTransfers\Pages\ManageInventoryTransfers;
use App\Models\InventoryTransfer;
use App\Services\InventoryTransfer\InventoryTransferService;
use App\Filament\BaseResource as Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns as T;

class InventoryTransferResource extends Resource
{
    use InventoryTransferResourceHelper;

    protected static ?string $model = InventoryTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make('Inventory Transfer')
                    ->tabs([
                        S\Tabs\Tab::make('Transfer Info')
                            ->schema([
                                ...static::transferInfoSchema(),
                            ]),

                        S\Tabs\Tab::make('Lot Selection')
                            ->schema([
                                ...static::lotSelectionSchema(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Select lot form
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('company.company_code')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('transfer_status')
                    ->label('Status')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('transfer_date')
                    ->label('Transfer Date')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('fromWarehouse.warehouse_name')
                    ->label('From Warehouse')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('toWarehouse.warehouse_name')
                    ->label('To Warehouse')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('transferLines.lot.lot_no')
                    ->label('Lines')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                T\TextColumn::make('total_transfer_qty')
                    ->label('Total Qty')
                    ->getStateUsing(fn(InventoryTransfer $record) => $record->transferLines()->sum('transfer_qty'))
                    ->numeric(decimalPlaces: 3)
                    ->alignEnd(),

                T\TextColumn::make('total_extra_cost')
                    ->label('Extra Cost')
                    ->money('VND')
                    ->sortable(),

                T\TextColumn::make('average_extra_cost_per_unit')
                    ->label('Avg Cost/Unit')
                    ->money('VND')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->slideOver()
                        ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                        ->using(function (InventoryTransfer $record, array $data) {
                            $record->update($data);

                            // Đồng bộ inventory transactions
                            $service = app(InventoryTransferService::class);
                            $service->syncInventoryTransactions($record);

                            return $record;
                        }),
                    A\DeleteAction::make()
                        ->before(function (InventoryTransfer $record) {
                            // Xử lý trước khi xoá
                            $service = app(InventoryTransferService::class);
                            $service->handleTransferDeletion($record);
                        }),
                ]),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryTransfers::route('/'),
        ];
    }

}
