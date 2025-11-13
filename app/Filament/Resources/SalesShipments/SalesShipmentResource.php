<?php

namespace App\Filament\Resources\SalesShipments;

use App\Filament\Resources\SalesShipments\Pages\ManageSalesShipments;
use App\Filament\Tables\DeliveryScheduleTable;
use App\Models\SalesDeliverySchedule;
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
                // Chọn khách hàng
                F\Select::make('customer_id')
                    ->label(__('Select Customer to Process'))
                    ->options(fn() => \App\Models\Contact::query()
                        ->where('is_cus', true)
                        ->orderBy('contact_name')
                        ->pluck('contact_name', 'id')->toArray())
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->live(onBlur: true)
                    ->partiallyRenderComponentsAfterStateUpdated(['delivery_schedules'])
                    ->afterStateUpdated(fn($state, $set) => $state ? null : $set('delivery_schedules', null)),

                S\Group::make([
                    // Chọn Delivery Schedules
                    F\ModalTableSelect::make('delivery_schedules')
                        ->label('Kế hoạch giao hàng')
                        ->relationship('deliverySchedules', 'id')
                        ->tableConfiguration(DeliveryScheduleTable::class)
                        ->getOptionLabelFromRecordUsing(fn(SalesDeliverySchedule $record): string
                        => "{$record->salesOrder->order_number} ({$record->etd})")
                        ->tableArguments(function (callable $get): array {
                            return [
                                'customer_id' => $get('customer_id'),
                            ];
                        })
                        ->multiple()
                        ->columnSpanFull()
                        ->disabled(fn(callable $get) => ! $get('customer_id'))
                        ->selectAction(fn(A\Action $action) => $action->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge))
                        ->belowContent(fn(callable $get) => !$get('customer_id')
                            ? S\Text::make('hint')->content(__('Select Customer to Process')) : null)
                        ->minItems(1)
                        ->required(),

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

                    F\TextInput::make('shipment_no'),

                    F\TextInput::make('tracking_number'),

                    F\TextInput::make('delivery_carrier'),

                    F\TextInput::make('delivery_staff'),

                    F\TextInput::make('shipping_address')
                        ->columnSpanFull(),

                    F\TextInput::make('billing_address')
                        ->columnSpanFull(),

                    __notes()
                        ->rows(4)
                        ->columnSpanFull(),

                ])
                    ->columns()
                    ->columnSpanFull(),
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
                        ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge),
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
}
