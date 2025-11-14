<?php

namespace App\Filament\Resources\SalesShipments;

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

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make()
                    ->tabs([
                        S\Tabs\Tab::make(__('Shipment Info'))
                            ->schema(static::shipmentInfoFields()),

                        S\Tabs\Tab::make(__('Shipment Lines'))
                            ->schema(static::mappingInventoryTransactions())
                            ->visibleJs(fn($get) => is_array($get('delivery_schedules')) && !empty($get('delivery_schedules'))),
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                    // ->before(fn(array $data) => dd($data))
                    ,
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
                    ->live(onBlur: true)
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
                    ->live(onBlur: true)
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
                            // Kiểm tra trùng address
                            $addresses = SalesDeliverySchedule::query()->whereIn('id', $data['selection'])
                                ->pluck('delivery_address');
                            if (
                                count($addresses) > 1
                                && $addresses->some(fn($addr) => $addr !== $addresses->first())
                            ) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Selected schedules have different delivery addresses'))
                                    ->warning()
                                    ->send();
                            }
                            if (count($data['selection']) <= 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Please select at least one delivery schedule'))
                                    ->warning()
                                    ->send();
                                $action->halt();
                            }
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
                    ->datalist(fn($get) => SalesDeliverySchedule::query()->whereIn('id', $get('delivery_schedules'))
                        ->pluck('delivery_address')->unique()->values())
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
    public static function mappingInventoryTransactions(): array
    {
        return [
            F\Repeater::make('transactions')
                ->hiddenLabel()
                ->table([
                    F\Repeater\TableColumn::make(__('Schedule')),
                    F\Repeater\TableColumn::make(__('Lot/Batch')),
                    F\Repeater\TableColumn::make(__('Qty'))->width('80px'),
                ])
                ->schema([
                    F\Select::make('schedule_line_id')
                        ->options(fn(callable $get) => SalesDeliveryScheduleLine::query()
                            ->whereIn('sales_delivery_schedule_id', $get('../../delivery_schedules') ?? [])
                            ->get()
                            ->pluck('label', 'id'))
                        ->live(onBlur: true)
                        ->required(),

                    F\Select::make('lot_no')
                        ->label(__('Lot/Batch'))
                        ->options(function (callable $get) {
                            if (!$get('../../delivery_schedules')) {
                                return [];
                            }
                            $scheduleLine = SalesDeliveryScheduleLine::find($get('schedule_line_id'));
                            $productIds = ($scheduleLine->assortment_id ?? null) ?
                                $scheduleLine->assortment->products()->pluck('products.id')->toArray()
                                : [$scheduleLine->product_id ?? null];

                            return \App\Models\InventoryTransaction::query()
                                ->whereIn('product_id', $productIds)
                                ->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Import)
                                ->withSum('children as shipped_qty', 'qty')
                                ->havingRaw('qty - shipped_qty > 0 OR shipped_qty IS NULL')
                                ->get()
                                ->pluck('lot_description', 'inventory_transactions.id')
                                ->toArray();
                        })
                        ->required(),

                    __number_field('qty')
                        ->required(),
                ])
                ->compact()
                ->minItems(1),
        ];
    }
}
