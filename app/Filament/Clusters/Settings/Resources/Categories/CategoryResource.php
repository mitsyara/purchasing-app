<?php

namespace App\Filament\Clusters\Settings\Resources\Categories;

use App\Filament\Clusters\Settings\Resources\Categories\Pages\ManageCategories;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Product Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make()
                    ->tabs([
                        S\Tabs\Tab::make(__('Category Details'))
                            ->schema([
                                F\TextInput::make('category_code')
                                    ->label(__('Category Code'))
                                    ->unique()
                                    ->required(),

                                F\TextInput::make('category_name')
                                    ->label(__('Category Name'))
                                    ->unique()
                                    ->required(),

                                F\Select::make('parent_id')
                                    ->label(__('Belongs To'))
                                    ->hint(__('* If applicable.'))
                                    ->options(fn(?Category $record)
                                    => Category::when($record?->id, fn(Builder $sq, $id) => $sq->whereNot('id', $id))->pluck('category_name', 'id'))
                                    ->afterStateUpdated(fn($state, $set) => static::afterParentStateUpdated($set, $state))
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['vat_id', 'is_gmp_required']),

                                F\Select::make('vat_id')
                                    ->label(__('VAT'))
                                    ->options(fn() => \App\Models\Vat::pluck('vat_value', 'id'))
                                    ->prefix('%')
                                    ->requiredWithout('parent_id')
                                    ->disabled(fn(Get $get) => (bool)$get('parent_id')),

                                F\Checkbox::make('is_gmp_required')
                                    ->label(__('GMP Required'))
                                    ->disabled(fn(Get $get) => (bool)$get('parent_id')),

                                F\TagsInput::make('category_keywords')
                                    ->label(__('Category Keywords'))
                                    ->helperText(__('Separate keywords with commas.'))
                                    ->separator(',')
                                    ->splitKeys([',', 'enter'])
                                    ->afterLabel([
                                        \Filament\Actions\Action::make('clear')
                                            ->action(fn(F\TagsInput $component) => $component->state([]))
                                    ])
                                    ->columnSpanFull(),

                                __notes()
                                    ->columnSpanFull(),
                            ])
                            ->columns(),

                        S\Tabs\Tab::make(__('Products'))
                            ->schema([]),

                    ])
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('category_code')
                    ->label(__('Category Code'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('category_name')
                    ->label(__('Category Name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('category_keywords')
                    ->label(__('Keywords'))
                    ->badge()
                    ->separator(',')
                    ->toggleable()
                    ->limit(3),

                T\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),

                T\TextColumn::make('parent.category_name')
                    ->label(__('Belongs To'))
                    ->toggleable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }

    // Helpers
    public static function afterParentStateUpdated(Set $set, ?string $state): void
    {
        if (!$state) return;
        $parent = Category::find($state);
        $set('vat_id', $parent?->vat_id);
        $set('is_gmp_required', $parent?->is_gmp_required);
    }
}
