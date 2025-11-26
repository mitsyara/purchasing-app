<?php

namespace App\Filament\Resources\InventoryTransactions;

use App\Filament\Resources\InventoryTransactions\Pages\ManageInventoryTransactions;
use App\Models\InventoryTransaction;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Infolists\Components as I;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionResource extends Resource
{
    use Helpers\InventoryTransactionResourceHelper;

    protected static ?string $model = InventoryTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(static::getTransactionInfolist());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => $query->with(['company', 'warehouse', 'product', 'checkedBy'])
                    ->withSum('exportedChildren as exported_qty', 'qty')
                    ->addSelect([
                        'inventory_transactions.*',
                    ])
                    ->selectRaw(
                        'inventory_transactions.qty - COALESCE(
                            (SELECT SUM(qty)
                            FROM inventory_transactions children
                            WHERE children.parent_id = inventory_transactions.id
                            AND children.transaction_direction = "export"),
                        0) as remaining_qty'
                    )
            )
            ->columns([
                __index(),

                T\TextColumn::make('transaction_direction')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('transaction_date')
                    ->label('Checked At')
                    ->date('d/m/Y')
                    ->description(fn(InventoryTransaction $record): ?string => $record->checkedBy?->name)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('warehouse.warehouse_name')
                    ->label('Warehouse')
                    ->description(fn(InventoryTransaction $record): ?string => $record->company?->company_code)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('product.product_full_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('lot_no')
                    ->label('Lot No')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('qty')
                    ->label('Quantity')
                    ->color(fn(InventoryTransaction $record): ?string => match ($record->transaction_direction) {
                        \App\Enums\InventoryTransactionDirectionEnum::Import => 'success',
                        \App\Enums\InventoryTransactionDirectionEnum::Export => 'danger',
                        default => null,
                    })
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exported_qty')
                    ->label('Exported')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn($livewire) => $livewire->activeTab === __('Import')),

                T\TextColumn::make('remaining_qty')
                    ->label('Remaining')
                    ->numeric()
                    ->color(fn($state): ?string => $state <= 0 ? 'danger' : ($state > 0 ? 'success' : null))
                    ->sortable()
                    ->toggleable()
                    ->visible(fn($livewire) => $livewire->activeTab === __('Import')),

                T\TextColumn::make('io_price')
                    ->label('In/Out Price')
                    ->money(fn(InventoryTransaction $record): ?string => $record->io_currency ?? 'VND')
                    ->color(fn(InventoryTransaction $record): ?string => match ($record->transaction_direction) {
                        \App\Enums\InventoryTransactionDirectionEnum::Import => 'danger',
                        \App\Enums\InventoryTransactionDirectionEnum::Export => 'info',
                        default => null,
                    })
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('break_price')
                    ->label('Break Price')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('mfg_date')
                    ->label('Mfg Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exp_date')
                    ->label('Exp Date')
                    ->date('d/m/Y')
                    ->color(fn($state): ?string => $state < today() ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('checkedBy.name')
                    ->label('Checked By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('company.company_code')
                    ->label('Company')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TF\BaseFilter::make('custom_filters')
                    ->schema([
                        S\Flex::make([
                            F\ToggleButtons::make('transaction_direction')
                                ->label('Direction')
                                ->options([
                                    \App\Enums\InventoryTransactionDirectionEnum::Import->value => 'Import',
                                    \App\Enums\InventoryTransactionDirectionEnum::Export->value => 'Export',
                                ])
                                ->colors([
                                    \App\Enums\InventoryTransactionDirectionEnum::Import->value => 'success',
                                    \App\Enums\InventoryTransactionDirectionEnum::Export->value => 'danger',
                                ])
                                ->grouped()
                                ->grow(false),

                            F\Select::make('category_id')
                                ->label('Category')
                                ->options(
                                    fn(): array => \App\Models\Category::query()
                                        ->orderBy('category_code')
                                        ->pluck('category_name', 'id')
                                        ->toArray()
                                )
                                ->placeholder(__('All')),

                            F\Select::make('assortment_id')
                                ->label('Assortment')
                                ->options(
                                    fn(): array => \App\Models\Assortment::query()
                                        ->orderBy('assortment_code')
                                        ->pluck('assortment_name', 'id')
                                        ->toArray()
                                )
                                ->placeholder(__('All'))
                                ->grow(false),

                        ])
                            ->from('md')
                            ->columnSpanFull(),

                        F\CheckboxList::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(fn(): array => \App\Models\Warehouse::query()
                                ->orderBy('warehouse_name')
                                ->pluck('warehouse_name', 'id')
                                ->toArray())
                            ->bulkToggleable(),

                        F\CheckboxList::make('company_id')
                            ->label('Company')
                            ->options(fn(): array => \App\Models\Company::query()
                                ->orderBy('company_code')
                                ->pluck('company_code', 'id')
                                ->toArray())
                            ->columns(2)
                            ->bulkToggleable(),

                    ])
                    ->columns()
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        // Apply custom filtering logic based on form data
                        return $query
                            ->when(
                                $data['transaction_direction'] ?? null,
                                fn(Builder $query, $type) =>
                                $query->where('transaction_direction', $type)
                            )
                            ->when(
                                $data['warehouse_id'] ?? null,
                                fn(Builder $query, $warehouseIds) =>
                                $query->whereIn('warehouse_id', $warehouseIds)
                            )
                            ->when(
                                $data['company_id'] ?? null,
                                fn(Builder $query, $companyIds) =>
                                $query->whereIn('company_id', $companyIds)
                            )
                            ->when(
                                $data['category_id'] ?? null,
                                fn(Builder $query, $categoryId) =>
                                $query->whereHas(
                                    'product',
                                    fn(Builder $query) =>
                                    $query->where('category_id', $categoryId)
                                )
                            )
                            ->when(
                                $data['assortment_id'] ?? null,
                                fn(Builder $query, $assortmentId) =>
                                $query->whereIn(
                                    'product_id',
                                    \App\Models\AssortmentProduct::query()
                                        ->where('assortment_id', $assortmentId)
                                        ->pluck('product_id')
                                        ->toArray()
                                )
                            );
                    }),
            ])
            ->filtersFormColumns(2)
            ->filtersFormWidth('xl')

            // ->recordActions([
            //     A\ActionGroup::make([
            //         A\Action::make('mark_as_checked')
            //             ->label('Checked')
            //             ->icon(Heroicon::OutlinedCheckCircle)
            //             ->color(fn(InventoryTransaction $record) => $record->is_checked ? null : 'success')
            //             ->disabled(fn(InventoryTransaction $record): bool => $record->is_checked)
            //             ->action(function (InventoryTransaction $record): void {
            //                 $record->checked();
            //                 Notification::make()
            //                     ->title('Transaction marked as checked.')
            //                     ->success()
            //                     ->send();
            //             })
            //             ->modalHeading('Mark as Checked')
            //             ->modalDescription('Are you sure you want to mark this transaction as checked?')
            //             ->requiresConfirmation(),

            //         A\Action::make('mark_as_unchecked')
            //             ->label('Unchecked')
            //             ->icon(Heroicon::OutlinedXCircle)
            //             ->color(fn(InventoryTransaction $record) => !$record->is_checked ? null : 'danger')
            //             ->disabled(fn(InventoryTransaction $record): bool => !$record->is_checked
            //                 && (auth()->user()->isAdmin() || auth()->id() === $record->checked_by))
            //             ->action(function (InventoryTransaction $record): void {
            //                 $record->unchecked();
            //                 Notification::make()
            //                     ->title('Transaction marked as unchecked.')
            //                     ->success()
            //                     ->send();
            //             })
            //             ->modalHeading('Mark as Unchecked')
            //             ->modalDescription('Are you sure you want to mark this transaction as unchecked?')
            //             ->requiresConfirmation(),
            //     ]),
            // ])
            // ->toolbarActions([
            //     A\BulkActionGroup::make([
            //         A\BulkAction::make('mark_as_checked')
            //             ->label('Checked')
            //             ->icon(Heroicon::OutlinedCheckCircle)
            //             ->color('success')
            //             ->action(function (\Illuminate\Support\Collection $records): void {
            //                 $userId = auth()->id();
            //                 // Mark each selected record as checked
            //                 InventoryTransaction::query()->whereIn('id', $records->toArray())
            //                     ->where('is_checked', false)
            //                     ->get()
            //                     ->each(fn(InventoryTransaction $record) => $record->checked($userId));

            //                 Notification::make()
            //                     ->title('Selected transactions have been marked as checked')
            //                     ->success()
            //                     ->send();
            //             })
            //             ->modalHeading('Mark as Checked')
            //             ->modalDescription('Are you sure you want to mark the selected transactions as checked?')
            //             ->requiresConfirmation(),

            //         A\BulkAction::make('mark_as_unchecked')
            //             ->label('Unchecked')
            //             ->icon(Heroicon::OutlinedXCircle)
            //             ->color('danger')
            //             ->action(function (\Illuminate\Support\Collection $records): void {
            //                 // Mark each selected record as unchecked
            //                 InventoryTransaction::query()->whereIn('id', $records->toArray())
            //                     ->where('is_checked', true)
            //                     ->get()
            //                     ->each(fn(InventoryTransaction $record) => $record->unchecked());

            //                 Notification::make()
            //                     ->title('Selected transactions have been marked as unchecked')
            //                     ->success()
            //                     ->send();
            //             })
            //             ->modalHeading('Mark as Unchecked')
            //             ->modalDescription('Are you sure you want to mark the selected transactions as unchecked?')
            //             ->requiresConfirmation(),
            //     ]),
            // ])

        ;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryTransactions::route('/'),
        ];
    }
}
