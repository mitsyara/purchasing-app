<?php

namespace App\Filament\Resources\PurchaseShipments\Tables;

use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseShipment;
use App\Services\PurchaseShipment\CallAllServices;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class PurchaseShipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('shipment_status')
                    ->label(__('Status'))
                    ->sortable(),

                T\TextColumn::make('tracking_no')
                    ->label(__('Tracking No.'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('purchaseOrder.order_number')
                    ->label(__('Order No.'))
                    ->searchable()
                    ->sortable()
                    ->hiddenOn([
                        \App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager::class,
                    ]),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->searchable()
                    ->sortable()
                    ->hiddenOn([
                        \App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager::class,
                    ]),

                T\TextColumn::make('supplier.contact_name')
                    ->label(__('Supplier'))
                    ->searchable()
                    ->sortable()
                    ->hiddenOn([
                        \App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager::class,
                    ]),

                __date_range_column('etd'),
                __date_range_column('eta'),

                T\TextColumn::make('customs_declaration_no')
                    ->label(__('Customs No.'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('customs_clearance_status')
                    ->label(__('Declaration Status'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('total_value')
                    ->label(__('Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

                T\TextColumn::make('total_contract_value')
                    ->label(__('Contract Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    // Custom actions
                    A\ActionGroup::make([
                        //
                    ])
                        ->dropdown(false),

                    A\ViewAction::make(),
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(Width::SevenExtraLarge)
                        ->after(function (PurchaseShipment $record) {
                            new CallAllServices($record);
                        }),
                    A\DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
