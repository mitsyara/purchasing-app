<?php

namespace App\Filament\Resources\SalesDeliveryScheduleLines;

use App\Filament\Resources\SalesDeliveryScheduleLines\Pages\ManageSalesDeliveryScheduleLines;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Models\SalesDeliverySchedule;
use Filament\Actions as A;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\SalesDeliveryScheduleLine;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns as T;
use Illuminate\Support\Number;

class SalesDeliveryScheduleLineResource extends Resource
{
    protected static ?string $model = SalesDeliveryScheduleLine::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'sales';

    protected static ?int $navigationSort = 21;

    // Override Labels
    public static function getModelLabel(): string
    {
        return __('Delivery Schedule');
    }

    // Override Navigation Label
    public static function getNavigationLabel(): string
    {
        return __('Delivery Schedules');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => $query
                    ->with(['product', 'assortment'])
                    ->leftJoin('products', 'sales_delivery_schedule_lines.product_id', '=', 'products.id')
                    ->leftJoin('assortments', 'sales_delivery_schedule_lines.assortment_id', '=', 'assortments.id')
                    ->selectRaw('sales_delivery_schedule_lines.*, COALESCE(products.product_description, assortments.assortment_name) as combined_product')
            )
            ->columns([
                __index(),

                T\TextColumn::make('salesOrder.order_number')
                    ->label(__('Sales Order'))
                    ->default('N/A')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->color('info')
                    ->url(fn(SalesDeliveryScheduleLine $record): string
                    => SalesOrderResource::getUrl(
                        'edit',
                        [
                            'record' => $record->deliverySchedule->sales_order_id,
                            'relation' => 1
                        ]
                    ), true)
                    ->sortable()
                    ->searchable(),

                T\TextColumn::make('deliverySchedule.delivery_status')
                    ->action(static::editScheduleStatus())
                    ->sortable(),

                T\TextColumn::make('deliverySchedule.etd')
                    ->label(__('Schedule Date'))
                    ->sortable(query: fn(Builder $query, string $direction): Builder
                    => $query->whereHas('deliverySchedule', fn(Builder $sq)
                    => $sq->orderBy('from_date', $direction)
                        ->orderBy('to_date', $direction))),

                T\TextColumn::make('combined_product')
                    ->label(__('Product'))
                    ->color(fn($record) => $record->assortment_id ? 'danger' : null)
                    ->searchable(
                        query: fn(Builder $query, string $search): Builder
                        => $query->whereHas('product', fn(Builder $q) => $q->where('product_description', 'like', "%{$search}%"))
                            ->orWhereHas('assortment', fn(Builder $q) => $q->where('assortment_name', 'like', "%{$search}%"))
                    )
                    ->sortable(),

                T\TextColumn::make('qty')
                    ->label(__('Quantity'))
                    ->numeric()
                    ->sortable(),

                T\TextColumn::make('unit_price')
                    ->label(__('Price'))
                    ->money(fn($record) => $record->salesOrder->currency)
                    ->description(fn($record) => $record->contract_price
                        ? Number::currency($record->contract_price, $record->salesOrder->currency, $locale) : null)
                    ->sortable(),

                T\TextColumn::make('value')
                    ->money(fn($record) => $record->salesOrder->currency)
                    ->description(fn($record) => $record->contract_price
                        ? Number::currency($record->contract_value, $record->salesOrder->currency, $locale) : null)
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalesDeliveryScheduleLines::route('/'),
        ];
    }

    // Helpers

    /**
     * Edit Delivery Schedule Status
     */
    public static function editScheduleStatus(): A\Action
    {
        return A\Action::make('switch_status')
            ->modal()
            ->modalWidth(\Filament\Support\Enums\Width::Large)
            ->schema([
                \Filament\Forms\Components\ToggleButtons::make('delivery_status')
                    ->label(__('Delivery Status'))
                    ->options(\App\Enums\DeliveryStatusEnum::class)
                    ->grouped()
                    ->required(),
            ])
            ->fillForm(fn($record) => ['delivery_status' => $record->deliverySchedule->delivery_status])
            ->action(function (SalesDeliveryScheduleLine $record, array $data): void {
                if ($data['delivery_status']) {
                    $record->deliverySchedule->update(['delivery_status' => $data['delivery_status']]);
                }
            });
    }
}
