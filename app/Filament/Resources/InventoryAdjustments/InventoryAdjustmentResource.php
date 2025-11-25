<?php

namespace App\Filament\Resources\InventoryAdjustments;

use App\Filament\Resources\InventoryAdjustments\Pages\ManageInventoryAdjustments;
use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use App\Models\InventoryAdjustment;
use Filament\Actions as A;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns as T;
use Illuminate\Database\Eloquent\Builder;

class InventoryAdjustmentResource extends Resource
{
    protected static ?string $model = InventoryAdjustment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|\UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 32;

    public static function getNavigationParentItem(): ?string
    {
        return InventoryTransactionResource::getNavigationLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Group::make([
                    ...static::adjustmentInfo(),
                ])
                    ->columns()
                    ->columnSpanFull(),

                S\Fieldset::make('Details')
                    ->schema([
                        ...static::linesInfo(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                T\TextColumn::make('adjustment_date')
                    ->label('Adjustment Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('adjustment_status')
                    ->label('Status')
                    ->description(fn(InventoryAdjustment $record): string => $record->adjustment_date?->format('d/m/Y'))
                    ->sortable(),

                T\TextColumn::make('company.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('warehouse.warehouse_name')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(30)
                    ->searchable(),

                T\TextColumn::make('adjustmentsLines.product.product_description')
                    ->label('Products')
                    ->distinctList()
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->slideOver()
                        ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                        ->fillForm(fn(InventoryAdjustment $record) => static::helper()->loadFormData($record))
                        ->mutateDataUsing(fn(A\EditAction $action, array $data): array
                        => static::helper()->syncData($action, $data)),

                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                A\CreateAction::make()
                    ->slideOver()
                    ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                    ->mutateDataUsing(fn(A\CreateAction $action, array $data): array
                    => static::helper()->syncData($action, $data)),

                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryAdjustments::route('/'),
        ];
    }

    // Form fields

    /**
     * Form chính
     */
    public static function adjustmentInfo(): array
    {
        return [
            S\Flex::make([
                F\Select::make('company_id')
                    ->relationship(
                        name: 'company',
                        titleAttribute: 'company_name',
                        modifyQueryUsing: fn(Builder $query): Builder
                        => $query
                    )
                    ->required(),

                F\Select::make('warehouse_id')
                    ->relationship(
                        name: 'warehouse',
                        titleAttribute: 'warehouse_name',
                        modifyQueryUsing: fn(Builder $query): Builder
                        => $query
                    )
                    ->grow(false)
                    ->required(),

                F\DatePicker::make('adjustment_date')
                    ->default(today())
                    ->maxDate(today())
                    ->grow(false)
                    ->required(),
            ])
                ->from('md')
                ->columnSpanFull(),

            S\Group::make([
                F\ToggleButtons::make('adjustment_status')
                    ->options(\App\Enums\OrderStatusEnum::class)
                    ->default(\App\Enums\OrderStatusEnum::Draft)
                    ->grouped()
                    ->required(),

                F\TextInput::make('reason')
                    ->required(),
            ]),

            __notes()
                ->rows(5),

        ];
    }

    /**
     * Các lot điều chỉnh - chia thành 2 repeater cho In/Out
     */
    public static function linesInfo(): array
    {
        return [
            S\Tabs::make('adjustment_directions')
                ->tabs([
                    S\Tabs\Tab::make('Increase')
                        ->icon('heroicon-o-arrow-up')
                        ->schema([
                            ...static::inLinesSchema(),
                        ]),

                    S\Tabs\Tab::make('Decrease')
                        ->icon('heroicon-o-arrow-down')
                        ->schema([
                            ...static::outLinesSchema(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * Schema cho Adjustment IN (tăng tồn kho)
     */
    public static function inLinesSchema(): array
    {
        return [
            F\Repeater::make('lines_in')
                ->hiddenLabel()
                ->schema([
                    static::productSelectField('populateDataFromProductIn'),
                    static::productLifeCycleField(),
                    static::inLotsRepeater(),
                ])
                ->itemLabel(fn(array $state) => static::getProductLabel($state))
                ->addActionLabel(__('Add Product')),
        ];
    }

    /**
     * Schema cho Adjustment OUT (giảm tồn kho)
     */
    public static function outLinesSchema(): array
    {
        return [
            F\Repeater::make('lines_out')
                ->hiddenLabel()
                ->schema([
                    static::productSelectField('populateDataFromProductOut'),
                    static::outLotsRepeater(),
                ])
                ->itemLabel(fn(array $state) => static::getProductLabel($state))
                ->addActionLabel(__('Add Product')),
        ];
    }

    // Helpers

    /**
     * Common product select field
     */
    protected static function productSelectField(string $populateMethod): F\Select
    {
        return F\Select::make('product_id')
            ->label('Product')
            ->options(fn() => \App\Models\Product::pluck('product_description', 'id'))
            ->getOptionLabelUsing(fn($value) => \App\Models\Product::find($value)?->product_description)
            ->afterStateUpdated(fn(callable $set, $state) => static::helper()->{$populateMethod}($state, $set))
            ->preload()
            ->searchable()
            ->live()
            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
            ->required();
    }

    /**
     * Product life cycle hidden field
     */
    protected static function productLifeCycleField(): F\Hidden
    {
        return F\Hidden::make('product_life_cycle')
            ->afterStateHydrated(function (F\Field $component, $get): void {
                $product = \App\Models\Product::find($get('product_id'));
                $component->state($product?->product_life_cycle);
            });
    }

    /**
     * Common lot table columns
     */
    protected static function lotTableColumns(): array
    {
        return [
            F\Repeater\TableColumn::make('Lot No')->markAsRequired(),
            F\Repeater\TableColumn::make('Mfg Date')->width('130px')->markAsRequired(),
            F\Repeater\TableColumn::make('Exp Date')->width('130px')->markAsRequired(),
            F\Repeater\TableColumn::make('Qty')->width('100px')->markAsRequired(),
            F\Repeater\TableColumn::make('Price (VND)')->width('120px')->markAsRequired(),
        ];
    }

    /**
     * IN lots repeater
     */
    protected static function inLotsRepeater(): F\Repeater
    {
        return F\Repeater::make('lots')
            ->compact()
            ->table(static::lotTableColumns())
            ->schema([
                F\Hidden::make('id'),
                F\TextInput::make('lot_no')
                    ->label('Lot No')
                    ->distinct()
                    ->required(),
                F\DatePicker::make('mfg_date')
                    ->label(__('Mfg Date'))
                    ->maxDate(today())
                    ->afterStateUpdatedJs(fn() => \App\Filament\Resources\PurchaseShipments\Schemas\PurchaseShipmentForm::setProductExpDateByJs())
                    ->required(),
                F\DatePicker::make('exp_date')
                    ->label(__('Exp Date'))
                    ->required(),
                __number_field('adjustment_qty')
                    ->label('Qty (Positive)')
                    ->minValue(0.001)
                    ->required(),
                __number_field('io_price')
                    ->label('Price (VND)')
                    ->required(),
            ])
            ->columns(6)
            ->minItems(1)
            ->defaultItems(1)
            ->addActionLabel(__('Add Lot/Batch'));
    }

    /**
     * OUT lots repeater
     */
    protected static function outLotsRepeater(): F\Repeater
    {
        return F\Repeater::make('lots')
            ->compact()
            ->table(static::lotTableColumns())
            ->schema([
                F\Hidden::make('id'),
                F\Select::make('parent_transaction_id')
                    ->label('Available Lot')
                    ->options(fn(callable $get) => static::helper()->getAvailableLotsForOutCallable($get))
                    ->getOptionLabelUsing(fn($value) => \App\Models\InventoryTransaction::find($value)?->lot_fifo)
                    ->afterStateUpdated(fn($state, callable $set) => static::helper()->populateDataFromParentTransaction($state, $set))
                    ->live()
                    ->partiallyRenderComponentsAfterStateUpdated(['mfg_date', 'exp_date', 'lot_no'])
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->required(),
                F\DatePicker::make('mfg_date')->label(__('Mfg Date'))->disabled()->dehydrated(false),
                F\DatePicker::make('exp_date')->label(__('Exp Date'))->disabled()->dehydrated(false),
                __number_field('adjustment_qty')
                    ->label('Qty (Positive)')
                    ->minValue(0.001)
                    ->rules([static::availableQtyValidationRule()])
                    ->required(),
                __number_field('io_price')
                    ->label('Price (VND)')
                    ->required(),
            ])
            ->columns(6)
            ->minItems(1)
            ->defaultItems(1)
            ->addActionLabel(__('Add Lot/Batch'));
    }

    /**
     * Available quantity validation rule
     */
    protected static function availableQtyValidationRule(): \Closure
    {
        return fn(Get $get): \Closure
        => function (string $attribute, $value, \Closure $fail) use ($get) {
            $parentId = $get('parent_transaction_id');
            $value = $value ? __number_string_converter($value, false) : null;

            if ($parentId && $value > 0) {
                $parent = \App\Models\InventoryTransaction::find($parentId);
                $availableQty = $parent?->qty - $parent?->children()->sum('qty');

                if ($parent && $value > $availableQty) {
                    $fail("Adjustment Qty cannot be greater than available quantity ({$availableQty}).");
                }
            }
        };
    }

    /**
     * Get product label for repeater items
     */
    protected static function getProductLabel(array $state): ?string
    {
        $product = \App\Models\Product::find($state['product_id']);
        return $product ? $product->product_description : __('Select a product');
    }

    /**
     * Helper instance
     */
    protected static function helper(): Helpers\InventoryAdjustmentResourceHelper
    {
        return app(Helpers\InventoryAdjustmentResourceHelper::class);
    }
}
