<?php

namespace App\Filament\Resources\InventoryAdjustments;

use App\Filament\Resources\InventoryAdjustments\Pages\ManageInventoryAdjustments;
use App\Models\InventoryAdjustment;
use App\Filament\BaseResource as Resource;
use App\Filament\Resources\InventoryTransfers\InventoryTransferResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;

class InventoryAdjustmentResource extends Resource
{
    use Helpers\InventoryAdjustmentResourceFormHelper;

    protected static ?string $model = InventoryAdjustment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|\UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 32;

    public static function getNavigationParentItem(): ?string
    {
        return InventoryTransferResource::getNavigationLabel();
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

    

    /**
     * Helper instance
     */
    protected static function helper(): Helpers\InventoryAdjustmentResourceHelper
    {
        return app(Helpers\InventoryAdjustmentResourceHelper::class);
    }
}
