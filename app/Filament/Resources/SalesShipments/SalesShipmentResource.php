<?php

namespace App\Filament\Resources\SalesShipments;

use App\Filament\Resources\SalesShipments\Helpers\SalesShipmentResourceHelper;
use App\Filament\Resources\SalesShipments\Pages\ManageSalesShipments;
use App\Filament\Tables\DeliveryScheduleTable;
use App\Models\SalesDeliverySchedule;
use App\Models\SalesDeliveryScheduleLine;
use App\Models\SalesShipment;
use FFI;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;

class SalesShipmentResource extends Resource
{
    protected static ?string $model = SalesShipment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'sales';

    protected static ?int $navigationSort = 22;

    // Override Labels
    public static function getModelLabel(): string
    {
        return __('Delivery');
    }

    // Override Navigation Label
    public static function getNavigationLabel(): string
    {
        return __('Deliveries');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make()
                    ->tabs([
                        S\Tabs\Tab::make(__('Shipment Info'))
                            ->schema(static::shipmentInfoFields()),

                        S\Tabs\Tab::make(__('Shipment Lines'))
                            ->schema(static::inventoryTransactionsFields()),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('shipment_status')
                    ->label(__('Status'))
                    ->description(fn(SalesShipment $record): ?string => $record->atd?->format('d/m/Y'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('warehouse.warehouse_name')
                    ->label(__('Warehouse'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('customer.contact_name')
                    ->label(__('Customer'))
                    ->description(fn(SalesShipment $record): string => $record->shipping_address)
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
                        ->fillForm(fn(SalesShipment $record) => static::helper()->loadFormData($record))
                        ->mutateDataUsing(fn(A\EditAction $action, array $data) => static::helper()->syncData($action, $data)),
                    A\DeleteAction::make(),
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
            'index' => ManageSalesShipments::route('/'),
        ];
    }

    /**
     * Helper instance
     */
    protected static function helper(): SalesShipmentResourceHelper
    {
        return app(SalesShipmentResourceHelper::class);
    }

    /**
     * Shipment Info
     */
    public static function shipmentInfoFields(): array
    {
        return [
            S\Flex::make([
                // Chọn khách hàng
                F\Select::make('customer_id')
                    ->label(__('Customer'))
                    ->relationship(
                        name: 'customer',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn($query) => $query->where('is_cus', true)
                    )
                    ->preload()
                    ->searchable()
                    ->live()
                    ->partiallyRenderComponentsAfterStateUpdated(['delivery_schedules'])
                    ->afterStateUpdated(fn($set) => $set('delivery_schedules', null))
                    ->columnSpanFull()
                    ->required(),

                F\Select::make('warehouse_id')
                    ->label(__('Export Warehouse'))
                    ->relationship(
                        name: 'warehouse',
                        titleAttribute: 'warehouse_name',
                    )
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('delivery_schedules', null))
                    ->grow(false)
                    ->required(),
            ])
                ->from('lg')
                ->columnSpanFull(),

            S\Group::make([
                S\Flex::make([
                    F\ToggleButtons::make('shipment_status')
                        ->options(\App\Enums\ShipmentStatusEnum::class)
                        ->default(\App\Enums\ShipmentStatusEnum::Pending)
                        ->grouped()
                        ->grow(false)
                        ->required(),

                    F\DatePicker::make('atd')
                        ->label('Actual Delivered Date')
                        ->requiredIf('shipment_status', \App\Enums\ShipmentStatusEnum::Delivered->value),

                ])
                    ->from('lg')
                    ->columnSpanFull(),

                // Chọn Delivery Schedules
                F\ModalTableSelect::make('delivery_schedules')
                    ->label(__('Schedules'))
                    ->relationship(
                        name: 'deliverySchedules',
                        titleAttribute: 'id'
                    )
                    ->tableConfiguration(DeliveryScheduleTable::class)
                    ->getOptionLabelFromRecordUsing(fn(SalesDeliverySchedule $record): string
                    => $record->label)
                    ->tableArguments(function (callable $get): array {
                        return [
                            'customer_id' => $get('customer_id'),
                            'warehouse_id' => $get('warehouse_id'),
                        ];
                    })
                    ->multiple()
                    ->selectAction(fn(A\Action $action): A\Action => $action
                        ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
                        ->after(function (array $data) use ($action): void {
                            if (count($data['selection']) <= 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Please select at least one delivery schedule'))
                                    ->warning()
                                    ->send();
                                $action->halt();
                                return;
                            }

                            static::helper()->checkAddressConflicts($data['selection']);
                        }))
                    ->badgeColor('gray')
                    ->columnSpanFull()
                    ->required(),

                S\Group::make([
                    F\TextInput::make('shipment_no'),
                    F\TextInput::make('tracking_number'),
                    F\TextInput::make('delivery_carrier'),
                    F\TextInput::make('delivery_staff'),
                ])
                    ->columns(),
                __notes()
                    ->rows(4),

                F\TextInput::make('shipping_address')
                    ->datalist(function (callable $get) {
                        $service = app(\App\Services\SalesShipment\SalesShipmentService::class);
                        return $service->getDeliveryAddresses($get('delivery_schedules') ?? []);
                    })
                    ->columnSpanFull()
                    ->required(),

                // F\TextInput::make('billing_address')
                //     ->columnSpanFull(),

            ])
                ->columns()
                ->columnSpanFull(),
        ];
    }

    /**
     * Shipment Lines (mapping inventory transactions)
     */
    public static function inventoryTransactionsFields(): array
    {
        $service = app(\App\Services\SalesShipment\SalesShipmentService::class);
        $helper = static::helper();

        return [
            F\Repeater::make('transactions')
                ->hiddenLabel()
                ->table([
                    F\Repeater\TableColumn::make('Schedule'),
                    F\Repeater\TableColumn::make('Lot/Batch')->width('40%'),
                    F\Repeater\TableColumn::make('Qty')->width('80px'),
                ])
                ->schema([
                    F\Select::make('schedule_line_id')
                        ->options(function (callable $get, ?SalesShipment $record) use ($service) {
                            $shipmentId = $record?->id;
                            return $service->getScheduleLineOptions($get('../../delivery_schedules') ?? [], $shipmentId);
                        })
                        ->getOptionLabelUsing(fn($value) => $value ? $service->getScheduleLineLabel($value) : null)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($helper) {
                            if ($state && $get('inventory_transaction_id')) {
                                $set('qty', $helper->calculateOptimalQty($get));
                            } else {
                                $set('inventory_transaction_id', null);
                                $set('qty', null);
                            }
                        })
                        ->required(),

                    /**
                     * LOGIC ĐÃ SỬA - ĐƯỢC COVER BỞI FEATURE TEST:
                     * 
                     * 1. getFormOptionsForLotSelection() bao gồm:
                     *    - Tất cả lots có remaining > 0 
                     *    - Lots đang được sử dụng bởi shipment hiện tại (currentTransactionId)
                     * 
                     * 2. Rollback logic:
                     *    - Exclude child transactions của shipment đang edit
                     *    - Rollback qty về parent lots
                     * 
                     * 3. Test cases:
                     *    - LOT 1000 → Shipment xuất 500 → Edit thành 800: OK
                     *    - LOT 1000 → Shipment xuất 1000 → Edit vẫn hiển thị lot: OK  
                     *    - Change schedule line → Lot vẫn hiển thị: OK
                     * 
                     * Feature test: tests/Feature/SalesShipment/SalesShipmentEditTest.php
                     */
                    F\Select::make('inventory_transaction_id')
                        ->label('Lot/Batch')
                        ->options(function (callable $get, ?SalesShipment $record) use ($service) {
                            $shipmentId = $record?->id;
                            $warehouseId = $get('../../warehouse_id');
                            $scheduleLineId = $get('schedule_line_id');
                            $currentTransactionId = $get('inventory_transaction_id');

                            // Nếu chưa chọn schedule line, trả về empty
                            if (!$scheduleLineId || !$warehouseId) {
                                return [];
                            }

                            $productIds = $service->getProductIdsFromScheduleLine($scheduleLineId);

                            return $service->getFormOptionsForLotSelection(
                                $productIds,
                                $warehouseId,
                                $shipmentId,
                                $currentTransactionId
                            );
                        })
                        ->rules([
                            function (callable $get, F\Field $component) use ($helper) {
                                return $helper->validateUniqueScheduleLotPair($get, $component);
                            }
                        ])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?SalesShipment $record) use ($service) {
                            // Tự động tính qty khi chọn inventory transaction (nếu đã có schedule line)
                            if ($state && $get('schedule_line_id')) {
                                $shipmentId = $record?->id;
                                $optimalQty = $service->calculateOptimalQty(
                                    $state,
                                    $get('schedule_line_id'),
                                    $shipmentId
                                );
                                $set('qty', $optimalQty);
                            } else {
                                $set('qty', null);
                                $set('inventory_transaction_id', null);
                            }
                        })
                        ->required(),

                    __number_field('qty')
                        ->rules([
                            function (callable $get, F\Field $component) use ($helper) {
                                return $helper->validateTransactionQty($get, $component);
                            }
                        ])
                        ->required(),
                ])
                ->compact()
                ->minItems(1),
        ];
    }
}
