<?php

namespace App\Filament\Clusters\Settings\Resources\Products;

use App\Filament\Clusters\Settings\Resources\Categories\CategoryResource;
use App\Filament\Clusters\Settings\Resources\Products\Pages\ManageProducts;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Schemas\SettingsClusters\PackingSchema;
use App\Filament\Tables\ProductTable;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Forms\Components as F;
use Filament\Actions as A;
use Filament\Schemas\Components as S;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Product Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\TextInput::make('product_code')
                    ->label(__('Product Code'))
                    ->unique(),

                F\TextInput::make('product_name')
                    ->label(__('Product Name'))
                    ->unique()
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
                    ->preload(),

                F\Select::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'category_name')
                    ->createOptionForm(fn($schema) => CategoryResource::form($schema)->getComponents())
                    ->editOptionForm(fn($schema) => CategoryResource::form($schema)->getComponents())
                    ->searchable()
                    ->preload(),

                F\Select::make('packing_id')
                    ->label(__('Packing'))
                    ->relationship('packing', 'packing_name')
                    ->createOptionForm(fn($schema) => PackingSchema::configure($schema))
                    ->editOptionForm(fn($schema) => PackingSchema::configure($schema))
                    ->searchable()
                    ->preload(),

                F\TextInput::make('product_life_cycle')
                    ->label(__('Product Life Circle'))
                    ->suffix(__('days'))
                    ->numeric()
                    ->minValue(0),

                F\TagsInput::make('product_certificates')
                    ->label(__('Product Certificates'))
                    ->helperText(__('Enter certificates separated by commas.'))
                    ->columnSpanFull()
                    ->separator(',')
                    ->splitKeys([',', ';', 'enter']),

                __notes()
                    ->columnSpanFull(),
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
            'index' => ManageProducts::route('/'),
        ];
    }
}
