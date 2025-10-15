<?php

namespace App\Livewire\CustomsData;

use Livewire\Component;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Tables\Filters\QueryBuilder\Constraints as C;

use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;

class Index extends Component implements HasTable, HasSchemas, HasActions
{
    use InteractsWithTable, InteractsWithSchemas, InteractsWithActions;

    protected string $model = \App\Models\CustomsData::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return \App\Models\CustomsData::query();
            })
            ->deferLoading()
            ->defaultSort('id', 'desc')
            ->deferFilters()

            ->columns([
                T\TextColumn::make('import_date')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Import Date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('importer')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Importer'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('product')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Product'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('qty')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Quantity'))
                    ->numeric()
                    ->suffix(fn($record): ?string => $record->unit ? ' ' . $record->unit : null)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('unit')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Unit'))
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('price')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Price'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('value')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exporter')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Exporter'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('export_country')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Export Country'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('incoterm')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Incoterm'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('hscode')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('HS Code'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                TF\Filter::make('customFilters')
                    ->schema([
                        F\Checkbox::make('is_vett'),
                        F\Checkbox::make('is_null')
                            ->label(__('No Category Assigned')),
                    ])
                    ->columns(['default' => 2])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['is_vett'],
                                fn(Builder $query): Builder
                                => $query
                                    ->whereAny(['importer', 'product'], 'like', '%thú y%'),
                            )
                            ->when(
                                $data['is_null'],
                                fn(Builder $query): Builder
                                => $query
                                    ->whereNull('customs_data_category_id')
                            );
                    }),

                TF\QueryBuilder::make()
                    ->constraints([
                        C\DateConstraint::make('import_date'),
                        C\TextConstraint::make('importer'),
                        C\TextConstraint::make('exporter'),
                        C\TextConstraint::make('product'),
                        C\RelationshipConstraint::make('category')
                            ->selectable(
                                C\RelationshipConstraint\Operators\IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->preload()
                                    ->searchable()
                                    ->multiple(),
                            ),
                        C\NumberConstraint::make('qty'),
                        C\NumberConstraint::make('price'),
                    ]),

            ], \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth(\Filament\Support\Enums\Width::FourExtraLarge)

            ->toolbarActions([
                $this->exportAction(),

                A\Action::make('dd')
                    ->action(fn() => $this->dispatch('export-started')),
            ]);
    }

    public function render()
    {
        return view('livewire.customs-data.index');
    }

    /**
     * Custom Export Action
     */
    public function exportAction(): A\Action
    {
        return A\Action::make('exportExcel')
            ->action(function (): void {
                $paginator = $this->getTableRecords();
                if ($paginator instanceof Collection) {
                    $currentRows = $paginator->count();
                } else {
                    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
                    $currentRows = $paginator->total();
                }

                if ($currentRows > 10000) {
                    Notification::make()
                        ->title(__('Exceeded Limit'))
                        ->body(__('Max 10k rows can be exported. Apply more filters to reduce the results.'))
                        ->warning()
                        ->send();
                    return;
                }

                // Dispatch Export Job
                $key = 'export-' . uniqid();
                $sessionKey = session()->getId();
                $query = $this->getTableQueryForExport();
                $data = [
                    'key' => $key,
                    'sessionKey' => $sessionKey,
                    'model' => $this->model,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ];
                \Illuminate\Support\Facades\Cache::put($key, $data, now()->addMinutes(5));
                \App\Jobs\CustomsDataExportJob::dispatch($key);

                $this->dispatch('export-started');

                Notification::make()
                    ->title(__('Export Started'))
                    ->body(__('Your export is being processed. You File will be downloaded shortly.'))
                    ->success()
                    ->send();
            })
            ->color(fn(A\Action $action) => $action->isDisabled() ? 'gray' : 'teal')->outlined()
            ->icon(Heroicon::ArrowDownTray)
            ->label(__('Download Data'))
            ->tooltip(__('10k rows max'));
    }

    /**
     * Excel Export, download directly without queue
     */
    public function exportExcel(): void
    {
        $columns = [
            'import_date' => 'Import Date',
            'importer' => 'Importer',
            'product' => 'Product',
            'qty' => 'Quantity',
            'unit' => 'Unit',
            'price' => 'Price (USD)',
            'value' => 'Total (USD)',
            'exporter' => 'Exporter',
            'export_country' => 'Country of Origin',
            'incoterm' => 'Incoterm',
            'hscode' => 'HS Code',
        ];
        $query = $this->getTableQueryForExport();
        $fileName = 'customs-data_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $writer = SimpleExcelWriter::streamDownload($fileName);
        $writer->addHeader([...array_values($columns)]);

        $query->chunk(1000, function ($records) use ($writer, $columns): void {
            foreach ($records as $record) {
                $data = [];
                foreach ($columns as $key => $label) {
                    $data[$key] = $record->$key;
                }
                $writer->addRow($data);
            }
            flush();
        });
        $writer->toBrowser();
    }
}
