<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectShipment;
use App\Models\ProjectShipmentItem;
use App\Traits\Filament\HasShipmentFormFields;
use App\Traits\Filament\HasShipmentLineValidation;
use App\Services\Project\ProjectService;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Closure;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Livewire\Component as Livewire;

use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DBSchema;

class ProjectShipmentsRelationManager extends RelationManager
{
    use HasShipmentFormFields, HasShipmentLineValidation;

    protected static string $relationship = 'projectShipments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Shipments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Tabs::make(__('Shipment'))
                ->tabs([
                    S\Tabs\Tab::make(__('Shipment Info'))
                        ->schema([
                            S\Section::make(__('Import'))
                                ->schema([
                                    ...$this->shipmentImportFields(),
                                ])
                                ->compact()
                                ->collapsible()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->columnSpanFull(),


                            S\Section::make(__('Export'))
                                ->schema([
                                    //
                                ])
                                ->compact()
                                ->collapsible()
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    S\Tabs\Tab::make(__('Products'))
                        ->schema([
                            $this->shipmentLines()
                        ])
                        ->columns(),

                    S\Tabs\Tab::make(__('Costs & Notes'))
                        ->schema([
                            ...$this->costsAndNotesFields(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Shipment'))
            ->columns([
                __index(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->after(function (ProjectShipment $record): void {
                        app(ProjectService::class)->syncProjectShipmentInfo($record->id);
                    })
                    ->modal()->slideOver(),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->after(function (ProjectShipment $record): void {
                        app(ProjectService::class)->syncProjectShipmentInfo($record->id);
                    })
                    ->modal()->slideOver(),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    // Helpers
    public function shipmentImportFields(): array
    {
        return [
            ...$this->shipmentBasicFields(),
            
            ...$this->shipmentStaffFields(),


            S\Flex::make([
                F\Select::make('port_id')
                    ->label(__('Port'))
                    ->relationship(
                        name: 'port',
                        titleAttribute: 'port_name',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->default(fn($livewire) => $livewire->getOwnerRecord()?->import_port_id)
                    ->required(function ($livewire): bool {
                        /** @var Project $order */
                        $order = $livewire->getOwnerRecord();
                        return !$order?->is_skip_invoice && $order?->is_foreign;
                    }),

                __number_field('exchange_rate')
                    ->label(__('Exchange Rate'))
                    ->suffix(JsContent::make(<<<'JS'
                                $get('currency') && $get('currency') !== 'VND' ? 'VND/' + $get('currency') : null
                            JS))
                    ->prefixActions([
                        A\Action::make('getExchangeRate')
                            ->label(__('Get Rate'))
                            ->icon(Heroicon::Banknotes)
                            ->action(fn($get, $set) => $this->getExchangeRate($get, $set))
                            ->link()
                            ->disabled(fn($get): bool => $get('currency') === 'VND')
                    ]),

                F\Toggle::make('is_exchange_rate_final')->label(__('Final Rate?'))
                    ->inline(false)
                    ->inlineLabel(false)
                    ->grow(false)
                    ->disabled(fn($get) => $get('currency') === 'VND'),
            ])
                ->from('sm')
                ->columnSpanFull(),

            F\TextInput::make('currency')
                ->label(__('Currency'))
                ->dehydrated(false)
                ->afterStateHydrated(fn(F\Field $component, RelationManager $livewire, ?ProjectShipment $record)
                => $component->state($record?->currency ?? $livewire->getOwnerRecord()?->currency))
                ->readOnly()
                ->hidden(),

            // Sử dụng helper function cho ETD/ETA fields
            ...$this->etdEtaFields(),

            $this->atdAtaFields()->columnSpanFull(),

            F\Checkbox::make('declaration_required')
                ->label(__('Declaration Required'))
                ->dehydrated(false)
                ->afterStateHydrated(function (F\Field $component, RelationManager $livewire): void {
                    $project = $livewire->getOwnerRecord();
                    $component->state($project?->is_foreign
                        && !$project?->is_skip_invoice);
                })
                ->hidden(),

            S\Group::make([
                F\TextInput::make('customs_declaration_no')
                    ->label(__('Declaration No.'))
                    ->maxLength(255),

                F\DatePicker::make('customs_declaration_date')
                    ->label(__('Declaration Date'))
                    ->placeholder('YYYY-MM-DD')
                    ->format('Y-m-d')
                    ->displayFormat('Y-m-d'),

                F\Select::make('customs_clearance_status')
                    ->label(__('Clearance Status'))
                    ->options(\App\Enums\CustomsClearanceStatusEnum::class)
                    ->required(),

                F\DatePicker::make('customs_clearance_date')
                    ->label(__('Clearance Date'))
                    ->maxDate(today()),
            ])
                ->disabled(fn($get) => !$get('declaration_required'))
                ->columns()
                ->columnSpanFull(),

        ];
    }

    public function shipmentLines(): F\Repeater
    {
        return F\Repeater::make('projectShipmentItems')
            ->label(__('Products'))
            ->relationship()
            ->hiddenLabel()
            ->table($this->shipmentLinesTableHeaders())
            ->schema(fn(?ProjectShipment $shipment) => [
                $this->createProductSelectField($shipment),

                $this->createQuantityField($shipment),

                __number_field('unit_price')
                    ->label(__('Unit Price'))
                    ->suffix(fn(Livewire $livewire) => $livewire->getOwnerRecord()?->currency ?? 'VND')
                    ->required(),

                __number_field('contract_price')
                    ->label(__('Contract Price'))
                    ->suffix(fn(Livewire $livewire) => $livewire->getOwnerRecord()?->currency ?? 'VND'),

            ])
            ->minItems(1)
            ->columnSpanFull()
            ->addActionLabel(__('Add Product'))
            ->required()
        ;
    }

    public function getExchangeRate(Get $get, Set $set): void
    {
        $currency = $get('currency');
        $date = $get('customs_clearance_date') ?? $get('customs_declaration_date') ?? null;
        if ($date && $currency && $currency !== 'VND') {
            $exchangeRateService = app(\App\Services\Common\ExchangeRateService::class);
            $rate = $exchangeRateService->getRate($currency, 'VND', $date);
            if ($rate) $rate = __number_string_converter($rate);
            if ($rate) {
                $set('exchange_rate', $rate);
            }
        } else {
            \Filament\Notifications\Notification::make()
                ->title(__('Cannot fetch exchange rate'))
                ->body(__('Please ensure that Declaration Date or Clearance Date is set.'))
                ->warning()
                ->send();
        }
    }
}
