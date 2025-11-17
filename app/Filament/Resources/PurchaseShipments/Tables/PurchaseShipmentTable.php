<?php

namespace App\Filament\Resources\PurchaseShipments\Tables;

use App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager;
use Filament\Tables\Table;
use App\Models\PurchaseShipment;
use Filament\Support\Icons\Heroicon;
use App\Services\PurchaseShipment\PurchaseShipmentService;
use App\Services\Inventory\InventoryService;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Illuminate\Database\Eloquent\Builder;

class PurchaseShipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder
            => $query->whereNotNull('purchase_order_id'))
            ->columns([
                __index(),

                T\TextColumn::make('shipment_status')
                    ->label(__('Shipment Status'))
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
                        PurchaseShipmentsRelationManager::class,
                    ]),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->searchable()
                    ->sortable()
                    ->hiddenOn([
                        PurchaseShipmentsRelationManager::class,
                    ]),

                T\TextColumn::make('supplier.contact_name')
                    ->label(__('Supplier'))
                    ->searchable()
                    ->sortable()
                    ->hiddenOn([
                        PurchaseShipmentsRelationManager::class,
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
                    // Custom actions for Clearance staff only
                    A\ActionGroup::make([
                        A\Action::make('markAsDelivered')
                            ->modal()->color('teal')
                            ->icon(Heroicon::CheckCircle)
                            ->requiresConfirmation()
                            ->action(fn(PurchaseShipment $record)
                            => app(PurchaseShipmentService::class)->markDelivered($record))
                            ->disabled(fn(PurchaseShipment $record): bool
                            => in_array($record->shipment_status, [
                                \App\Enums\ShipmentStatusEnum::Delivered,
                                \App\Enums\ShipmentStatusEnum::Cancelled,
                            ])),
                    ])
                        ->dropdown(false),

                    // A\ViewAction::make(),

                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->after(function (PurchaseShipment $record) {
                            app(PurchaseShipmentService::class)->syncShipmentInfo($record->id);

                            // Sync inventory lines from shipment lines
                            if ($record->purchaseShipmentLines()->exists()) {
                                $inventoryService = app(InventoryService::class);
                                $record->purchaseShipmentLines()
                                    ->each(fn($line) => $inventoryService->syncFromShipmentLine($line));
                            }
                        }),
                ])
            ])
            ->toolbarActions([])
            // ->recordAction('edit')
        ;
    }
}
