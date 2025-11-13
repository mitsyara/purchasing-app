<?php

namespace App\Filament\Resources\SalesOrders\RelationManagers;

use App\Models\SalesDeliverySchedule;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns as T;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;

class DeliverySchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliverySchedules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Schedules');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Tabs::make(__('Shipment'))
                ->tabs([
                    S\Tabs\Tab::make(__('Shipment Info'))
                        ->schema([
                            ...$this->shipmentInfoFields(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    S\Tabs\Tab::make(__('Products'))
                        ->schema(fn(?SalesDeliverySchedule $record) => [
                            ...$this->shipmentLines($record),
                        ])
                        ->columns(),
                ])
                ->columnSpanFull(),

        ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Schedule'))
            ->pluralModelLabel(static::title())
            ->columns([
                __index(),

                T\TextColumn::make('delivery_status')
                    ->description(fn($record) => $record->etd)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('warehouse.warehouse_name')
                    ->label(__('Warehouse'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('customer.contact_name')
                    ->description(fn($record) => $record->delivery_address)
                    ->label(__('Delivery Address'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('product_list')
                    ->label(__('Products'))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->searchable(query: fn(Builder $query, string $search): Builder
                    => $query->whereHas('products', fn(Builder $q) => $q->where('product_description', 'like', "%{$search}%"))
                        ->orWhereHas('assortments', fn(Builder $q) => $q->where('assortment_name', 'like', "%{$search}%")))
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->modal()->slideOver()
                    ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                    ->after(fn(SalesDeliverySchedule $record) => $this->syncPrices($record)),
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                        ->after(fn(SalesDeliverySchedule $record) => $this->syncPrices($record)),
                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([]);
    }

    // Form Helpers

    protected function shipmentInfoFields(): array
    {
        return [
            F\ToggleButtons::make('delivery_status')
                ->label(__('Delivery Status'))
                ->options(\App\Enums\DeliveryStatusEnum::class)
                ->default(\App\Enums\DeliveryStatusEnum::Scheduled)
                ->grouped()
                ->required(),

            S\Flex::make([
                F\Select::make('export_warehouse_id')
                    ->label(__('Export Warehouse'))
                    ->relationship('warehouse', 'warehouse_name')
                    ->default(fn() => $this->getOwnerRecord()->export_warehouse_id)
                    ->required(),
                F\DatePicker::make('from_date')

                    ->label(__('From Date'))
                    ->default(fn() => $this->getOwnerRecord()->order_date ?? today())
                    ->minDate(fn() => $this->getOwnerRecord()->order_date),

                F\DatePicker::make('to_date')
                    ->label(__('To Date'))
                    ->requiredWithout('from_date')
                    ->validationMessages([
                        'required_without' => __('At least one date is required.'),
                    ]),
            ])
                ->from('md')
                ->columnSpanFull(),

            F\TextInput::make('delivery_address')
                ->label(__('Delivery Address'))
                ->datalist(fn() => $this->getWarehousesOptions())
                ->columnSpanFull()
                ->required(),

            __notes()
                ->rows(5)
                ->columnSpanFull(),
        ];
    }

    /**
     * Hàng hoá trong lô hàng
     */
    public function shipmentLines(?SalesDeliverySchedule $shipment): array
    {
        return [
            F\Repeater::make('deliveryLines')
                ->label(__('Products'))
                ->relationship()
                ->hiddenLabel()
                ->table([
                    F\Repeater\TableColumn::make('Assortment')->width('280px'),
                    F\Repeater\TableColumn::make('Product'),
                    F\Repeater\TableColumn::make('Qty')->markAsRequired()->width('180px'),
                ])
                ->schema([
                    F\Select::make('assortment_id')
                        ->label(__('Assortment'))
                        ->relationship(
                            name: 'assortment',
                            titleAttribute: 'assortment_name',
                            modifyQueryUsing: fn($query): Builder => $query
                                ->whereIn('id', $this->getAssortmentIdsFromOrder())
                        )
                        ->afterStateUpdated(fn(F\Field $component, callable $set)
                        => $set('qty', $this->calculateRemainingQuantity(
                            value: $component->getState(),
                            field: $component->getStatePath(false),
                            shipment: $shipment,
                            toString: true
                        )))
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdatedJs(<<<'JS'
                            $state ? $set('product_id', null) : null;
                        JS),

                    F\Select::make('product_id')
                        ->label(__('Product'))
                        ->relationship(
                            name: 'product',
                            titleAttribute: 'product_name',
                            modifyQueryUsing: fn($query): Builder => $query
                                ->whereIn('id', $this->getProductIdsFromOrder())
                        )
                        ->afterStateUpdated(fn(F\Field $component, callable $set)
                        => $set('qty', $this->calculateRemainingQuantity(
                            value: $component->getState(),
                            field: $component->getStatePath(false),
                            shipment: $shipment,
                            toString: true
                        )))
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdatedJs(<<<'JS'
                            $state ? $set('assortment_id', null) : null;
                        JS)
                        ->requiredWithout('assortment_id')
                        ->validationMessages([
                            'required_without' => __('At least one of Assortment or Product must be selected.'),
                        ]),

                    __number_field('qty')
                        ->label(__('Quantity'))
                        ->rules([
                            function (callable $get) use ($shipment) {
                                return function ($attribute, $value, $fail) use ($get, $shipment) {
                                    $field = $get('assortment_id') ? 'assortment_id' : 'product_id';
                                    $qtyValue = $get($field);
                                    $remaining = $this->calculateRemainingQuantity(
                                        value: $qtyValue,
                                        field: $field,
                                        shipment: $shipment,
                                        toString: false
                                    );

                                    if ($value > $remaining) {
                                        $fail(__('Max quantity :quantity', [
                                            'quantity' => $remaining,
                                        ]));
                                    }
                                };
                            },
                        ])
                        ->required(),
                ])
                ->minItems(1)
                ->addable(fn(array $state): bool
                => count($state) < (count($this->getAssortmentIdsFromOrder())
                    + count($this->getProductIdsFromOrder())))
                ->compact()
                ->columnSpanFull(),
        ];
    }

    /**
     * Lấy danh sách địa chỉ kho từ khách hàng (đơn hàng)
     */
    public function getWarehousesOptions(): array|Arrayable|null
    {
        $customer = $this->getOwnerRecord()->customer;
        if ($customer) {
            return array_unique(array_merge(($customer->warehouse_addresses ?? []), [$customer->office_address]));
        }
        return null;
    }

    /**
     * Lấy danh sách productIds từ đơn hàng
     */
    public function getProductIdsFromOrder(): array
    {
        $salesOrder = $this->getOwnerRecord();
        return $salesOrder->salesOrderLines()->pluck('product_id')
            ->filter()->values()->toArray();
    }

    /**
     * Lấy danh sách assortmentIds từ đơn hàng
     */
    public function getAssortmentIdsFromOrder(): array
    {
        $salesOrder = $this->getOwnerRecord();
        return $salesOrder->salesOrderLines()->pluck('assortment_id')
            ->filter()->values()->toArray();
    }

    /**
     * Tính toán số lượng còn lại có thể giao cho sản phẩm đã chọn
     */
    public function calculateRemainingQuantity(
        mixed $value,
        string $field,
        ?SalesDeliverySchedule $shipment,
        ?bool $toString = false
    ): null|string|float {
        // Lấy đơn hàng cha
        $salesOrder = $this->getOwnerRecord();

        // Danh sách DeliveryLines không tính Shipment hiện tại
        $otherDeliveryLines = $salesOrder->deliveryScheduleLines()
            ->when($shipment, fn(Builder $query) => $query->where('sales_delivery_schedule_id', '!=', $shipment->id));

        $totalOrdered = match ($field) {
            'assortment_id' => $salesOrder->salesOrderLines()->where('assortment_id', $value)->sum('qty'),
            'product_id' => $salesOrder->salesOrderLines()->where('product_id', $value)->sum('qty'),
        };

        $totalCreated = match ($field) {
            'assortment_id' => $otherDeliveryLines->where('assortment_id', $value)->sum('qty'),
            'product_id' => $otherDeliveryLines->where('product_id', $value)->sum('qty'),
        };

        if (!$value) return null;
        $remaining = max($totalOrdered - $totalCreated, 0);

        return __number_string_converter($remaining, $toString);
    }

    public function syncPrices(SalesDeliverySchedule $schedule): void
    {
        $salesOrder = $this->getOwnerRecord();

        // Lấy các dòng order và delivery
        $orderLines = $salesOrder->salesOrderLines;
        $deliveryLines = $schedule->deliveryLines;

        // Duyệt từng dòng giao hàng
        foreach ($deliveryLines as $deliveryLine) {
            // Tìm dòng order tương ứng theo assortment_id (hoặc product_id)
            $matchedOrderLine = $orderLines->first(function ($orderLine) use ($deliveryLine) {
                return $orderLine->assortment_id === $deliveryLine->assortment_id
                    || ($orderLine->product_id && $orderLine->product_id === $deliveryLine->product_id);
            });

            // Nếu tìm thấy dòng order tương ứng => cập nhật giá
            if ($matchedOrderLine) {
                $deliveryLine->unit_price = $matchedOrderLine->unit_price;
                $deliveryLine->contract_price = $matchedOrderLine->contract_price;
                // Lưu lại
                $deliveryLine->save();
            }
        }
    }
}
