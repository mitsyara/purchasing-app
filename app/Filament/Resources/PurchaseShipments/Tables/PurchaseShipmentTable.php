<?php

namespace App\Filament\Resources\PurchaseShipments\Tables;

use App\Filament\Resources\PurchaseOrders\RelationManagers\PurchaseShipmentsRelationManager;
use App\Filament\Resources\PurchaseShipments\Pages\ManagePurchaseShipments;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Services\PurchaseShipment\CallAllServices;
use Filament\Tables\Table;

use Filament\Forms\Components as F;
use Filament\Actions as A;
use Filament\Schemas\JsContent;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Validation\Rules\Unique;

class PurchaseShipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    // Custom actions for Clearance staff only
                    A\ActionGroup::make([
                        A\Action::make('markAsDelivered')
                            ->modal()->color('teal')
                            ->icon(Heroicon::CheckCircle)
                            ->requiresConfirmation()
                            ->action(fn(PurchaseShipment $record)
                            => $record->markAsDelivered())
                            ->disabled(fn(PurchaseShipment $record): bool
                            => in_array($record->shipment_status, [
                                \App\Enums\ShipmentStatusEnum::Delivered,
                                \App\Enums\ShipmentStatusEnum::Cancelled,
                            ])),

                        static::assignLotAction()
                            ->modalWidth(Width::FourExtraLarge),

                    ])
                        ->dropdown(false)
                        ->visible(fn($livewire) => $livewire instanceof ManagePurchaseShipments),

                    A\ViewAction::make(),
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(Width::SevenExtraLarge)
                        ->after(function (PurchaseShipment $record) {
                            new CallAllServices($record);
                        }),
                    A\DeleteAction::make()
                        ->visible(fn($livewire) => $livewire instanceof PurchaseShipmentsRelationManager),
                ])
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make()
                        ->visible(fn($livewire) => $livewire instanceof PurchaseShipmentsRelationManager),
                ]),
            ]);
    }

    public static function assignLotAction(): A\Action
    {
        return A\Action::make('assignLot')
            ->modal()->color('pink')
            ->icon(Heroicon::DocumentText)
            ->schema([
                F\Repeater::make('purchaseShipmentLines')
                    ->relationship()
                    ->hiddenLabel()
                    ->schema([
                        F\Hidden::make('id'),
                        F\Hidden::make('product_id'),
                        F\Hidden::make('qty'),
                        F\Hidden::make('product_unit_label')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn(PurchaseShipmentLine $record, F\Hidden $component)
                            => $component->state($record->product->product_unit_label)),
                        F\Hidden::make('product_life_cycle')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn(PurchaseShipmentLine $record, F\Hidden $component)
                            => $component->state($record->product->product_life_cycle)),

                        F\Repeater::make('transactions')
                            ->relationship()
                            ->hiddenLabel()
                            ->table([
                                F\Repeater\TableColumn::make('Lot/Batch No')
                                    ->markAsRequired(),
                                F\Repeater\TableColumn::make('Qty')
                                    ->width('180px')
                                    ->markAsRequired(),
                                F\Repeater\TableColumn::make('Mfg Date')
                                    ->width('160px')
                                    ->markAsRequired(),
                                F\Repeater\TableColumn::make('Exp Date')
                                    ->width('160px')
                                    ->markAsRequired(),
                            ])
                            ->schema([
                                F\TextInput::make('lot_no')
                                    ->label(__('Lot/Batch No'))
                                    ->unique(modifyRuleUsing: function(callable $get, Unique $rule): Unique {
                                        return $rule->where('product_id', $get('../../product_id'));
                                    })
                                    ->required(),

                                __number_field('qty')
                                    ->suffix(fn() => JsContent::make(<<<'JS'
                                        $get('../../product_unit_label')
                                    JS))
                                    ->rules([
                                        //
                                    ])
                                    ->required(),

                                F\DatePicker::make('mfg_date')
                                    ->label(__('Mfg Date'))
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const mfgDate = new Date($state);
                                        const lifeCycle = $get('../../product_life_cycle') ?? 0;
                                        const expDate = new Date(mfgDate);
                                        expDate.setDate(mfgDate.getDate() + lifeCycle);
                                        $set('exp_date', expDate.toISOString().split('T')[0]);
                                    JS)
                                    ->required(),

                                F\DatePicker::make('exp_date')
                                    ->label(__('Exp Date'))
                                    ->required(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel(__('Add Lot/Batch'))
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->itemLabel(function (array $state): string {
                        $productId = $state['product_id'] ?? null;
                        $product = \App\Models\Product::find($productId);
                        $qty = $state['qty'] ? __number_string_converter_vi($state['qty']) : 0;
                        return $product->product_full_name . SPACING . " Qty: ({$qty} {$product->product_unit_label})" ?? 'N/A';
                    })
                    ->collapsible(),
            ])
            ->fillForm(fn(PurchaseShipment $record) => $record->toArray());
    }
}
