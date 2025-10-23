<?php

namespace App\Filament\Clusters\Settings\Resources\Products;

use App\Filament\Clusters\Settings\Resources\Categories\CategoryResource;
use App\Filament\Clusters\Settings\Resources\Products\Pages\ManageProducts;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Schemas\SettingsClusters\PackingSchema;
use App\Filament\Tables\ProductTable;
use App\Models\Product;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 0;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    // protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'other';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make('Products')
                    ->schema([
                        S\Tabs\Tab::make(__('Product Info'))
                            ->schema([
                                ...static::productFields(),
                            ])
                            ->columns(),

                        S\Tabs\Tab::make(__('Specialized Traders'))
                            ->schema([
                                static::specializedTraderRepeater(),
                            ]),

                        S\Tabs\Tab::make(__('Specialized Customers'))
                            ->schema([
                                static::specializedCustomerRepeater(),
                            ]),
                    ])
                    ->columnSpanFull()
            ])
            ->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ProductTable::configure($table)->getColumns())
            ->filters([
                TF\SelectFilter::make('mfg_id')
                    ->label(__('Manufacturer'))
                    ->relationship(
                        name: 'mfg',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('is_mfg', true)
                            ->whereIn('id', Product::distinct()->pluck('mfg_id')->filter()->toArray()),
                    )
                    ->searchable(['contact_code', 'contact_name'])
                    ->preload(),

                TF\SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'category_name')
                    ->searchable(['category_code', 'category_name'])
                    ->preload(),
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modal()
                        ->slideOver(),
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
            'index' => ManageProducts::route('/'),
        ];
    }

    // Helper methods

    public static function productFields(): array
    {
        return [
            F\TextInput::make('product_code')
                ->label(__('Product Code'))
                ->unique(),

            F\TextInput::make('product_name')
                ->label(__('Product Name'))
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn(Unique $rule, callable $get): Unique
                    => $rule
                        ->when(
                            $get('mfg_id'),
                            fn(Unique $rule, $mfg_id)
                            => $rule->where('mfg_id', $mfg_id)
                        )
                        ->when(
                            $get('packing_id'),
                            fn(Unique $rule, $packing_id)
                            => $rule->where('packing_id', $packing_id)
                        )
                )
                ->required(),

            F\Checkbox::make('is_active')
                ->label(__('Is Active'))
                ->default(true),
            F\Checkbox::make('is_fav')
                ->label(__('Is Favorite'))
                ->default(false),

            F\Select::make('mfg_id')
                ->label(__('Manufacturer'))
                ->relationship('mfg', 'contact_name')
                ->searchable()
                ->preload()
                ->required(),

            F\Select::make('category_id')
                ->label(__('Category'))
                ->relationship('category', 'category_name')
                ->createOptionForm(fn($schema) => CategoryResource::form($schema)->getComponents())
                ->editOptionForm(fn($schema) => CategoryResource::form($schema)->getComponents())
                ->searchable()
                ->preload()
                ->required(),

            F\Select::make('packing_id')
                ->label(__('Packing'))
                ->relationship('packing', 'packing_name')
                ->createOptionForm(fn($schema) => PackingSchema::configure($schema))
                ->editOptionForm(fn($schema) => PackingSchema::configure($schema))
                ->searchable()
                ->preload()
                ->required(),

            F\TextInput::make('product_life_cycle')
                ->label(__('Product Life Circle'))
                ->suffix(__('days'))
                ->numeric()->integer()
                ->prefixAction(
                    A\Action::make('dayConverter')
                        ->modal()->icon(Heroicon::ArrowPathRoundedSquare)
                        ->modalWidth(Width::Medium)
                        ->schema([
                            S\FusedGroup::make([
                                __number_field('number')
                                    ->integer()
                                    ->required(),
                                F\Select::make('of')
                                    ->options([
                                        1 => __('Days'),
                                        30 => __('Months'),
                                        365 => __('Years'),
                                    ])
                                    ->default(1)
                                    ->selectablePlaceholder(false)
                                    ->required(),
                            ])
                                ->label(__('Duration'))
                                ->columns(['default' => 2]),
                        ])
                        ->action(function (array $data, F\TextInput $component): void {
                            $component->state($data['number'] * $data['of']);
                        })
                        ->modalSubmitActionLabel(__('Convert'))
                        ->modalWidth(\Filament\Support\Enums\Width::Small)
                )
                ->minValue(0)
                ->dehydrated(fn($state) => filled($state) && (int) $state > 0),

            F\TagsInput::make('product_certificates')
                ->label(__('Product Certificates'))
                ->helperText(__('Enter certificates separated by commas.'))
                ->columnSpanFull()
                ->separator(',')
                ->splitKeys([',', ';', 'enter']),

            __notes()
                ->columnSpanFull(),

        ];
    }

    public static function specializedTraderRepeater(): F\Repeater
    {
        return F\Repeater::make('productStrongTraders')
            ->relationship()
            ->hiddenLabel()
            ->simple(
                F\Select::make('contact_id')
                    ->hiddenLabel()
                    ->relationship(
                        name: 'contact',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('is_trader', true)
                            ->whereNotNull('contact_name')
                    )
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->searchable()
                    ->preload()
                    ->required(),
            )
            ->defaultItems(0);
    }

    public static function specializedCustomerRepeater(): F\Repeater
    {
        return F\Repeater::make('productStrongCustomers')
            ->relationship()
            ->hiddenLabel()
            ->simple(
                F\Select::make('contact_id')
                    ->hiddenLabel()
                    ->relationship(
                        name: 'contact',
                        titleAttribute: 'contact_name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('is_cus', true)
                            ->whereNotNull('contact_name')
                    )
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->searchable()
                    ->preload()
                    ->required(),
            )
            ->defaultItems(0);
    }
}
