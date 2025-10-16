<?php

namespace App\Livewire\CustomsData;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Livewire\Component;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Filters\QueryBuilder\Constraints as C;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Tables\Filters as TF;
use Filament\Tables\Columns as T;
use Filament\Actions as A;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class Index extends Component implements HasTable, HasSchemas, HasActions
{
    use InteractsWithTable, InteractsWithSchemas, InteractsWithActions;

    protected string $model = \App\Models\CustomsData::class;

    public function render()
    {
        return view('livewire.customs-data.index');
    }

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
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Import Date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('importer')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Importer'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('product')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Product'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('qty')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Quantity'))
                    ->numeric()
                    ->suffix(fn($record): ?string => $record->unit ? ' ' . $record->unit : null)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('unit')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Unit'))
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('price')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Price'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('value')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Total'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('exporter')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->wrap()
                    ->label(__('Exporter'))
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('export_country')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Export Country'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('incoterm')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label(__('Incoterm'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('hscode')
                    // ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
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
            ]);
    }

    #[\Livewire\Attributes\On('fileReady')]
    public function fileReady(): void
    {
        $key = 'export-result-' . session()->getId();

        if (!Cache::has($key)) {
            Log::channel('export')->warning('No export session found: ' . session()->getId());
            Notification::make()
                ->title(__('No Export Found'))
                ->body(__('No export session found. Please try exporting again.'))
                ->warning()
                ->send();
            return;
        }

        // Lấy dữ liệu và xoá Cache
        $data = Cache::pull($key);
        $signedUrl = $data['url'];
        $filePath = $data['file'];

        Log::channel('export')->info('Send file. Cleared export session: ' . $key, [
            'url' => $signedUrl,
            'file' => $filePath,
        ]);

        Notification::make('Export Ready')
            ->title(__('Your export is ready'))
            ->body(__('Click the button below to download your file. The link will expire in 5 minutes.'))
            ->actions([
                A\Action::make('downloadFile')
                    ->label(__('Download File'))
                    ->button()
                    ->color('success')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url($signedUrl)
                    ->openUrlInNewTab()
                    ->close()
                    ->after(fn() => $this->removeFile($filePath)),

                A\Action::make('skipExport')
                    ->label(__('Skip'))
                    ->button()
                    ->color('danger')
                    ->icon(Heroicon::XMark)
                    ->action(fn() => $this->removeFile($filePath, true))
                    ->close(),
            ])
            ->duration(1000 * 60 * 5) // 5 minutes
            ->send();
    }

    public function removeFile(string $filePath, bool $now = false): void
    {
        // Schedule xoá file
        $delay = $now ? null : 5;
        \App\Jobs\CleanUpCustomsDataExportFileJob::dispatch($filePath)->delay($delay);
    }

    /**
     * Custom Export Action
     */
    public function exportAction(): A\Action
    {
        return A\Action::make('exportExcel')
            ->action(function (): void {
                $paginator = $this->getTableRecords();
                $sessionKey = session()->getId();

                if (Cache::get("export-result-{$sessionKey}")) {
                    Notification::make()
                        ->title(__('Export In Progress'))
                        ->body(__('You have an ongoing export. Please wait until it is finished before starting a new one.'))
                        ->warning()
                        ->send();
                    return;
                }

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
                        ->danger()
                        ->send();
                    return;
                }

                // Dispatch Export Job
                $key = 'export-' . uniqid();
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

                $this->dispatch('resetPolling');

                Notification::make()
                    ->title(__('Export Started'))
                    ->body(__('Your export is being processed. You will be notified when it is ready.'))
                    ->success()
                    ->send();
            })
            ->color('success')->link()
            ->icon(Heroicon::ArrowDownTray)
            ->label(__('Download Data'))
            ->rateLimit(2);
    }
}
