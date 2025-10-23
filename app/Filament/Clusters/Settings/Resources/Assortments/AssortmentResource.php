<?php

namespace App\Filament\Clusters\Settings\Resources\Assortments;

use App\Filament\Clusters\Settings\Resources\Assortments\Pages\ManageAssortments;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Tables\ProductTable;
use App\Models\Assortment;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssortmentResource extends Resource
{
    protected static ?string $model = Assortment::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static ?string $cluster = SettingsCluster::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Product Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\TextInput::make('assortment_code')
                    ->label(__('Assortment Code'))
                    ->unique()
                    ->required(),

                F\TextInput::make('assortment_name')
                    ->label(__('Assortment Name'))
                    ->unique()
                    ->required(),

                F\Select::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'category_name')
                    ->searchable()
                    ->preload(),

                F\ModalTableSelect::make('products')
                    ->label(__('Products'))
                    ->relationship('products', 'product_full_name')
                    ->tableConfiguration(ProductTable::class)
                    ->tableArguments(fn($get) => ['belong_category_id' => $get('category_id')])
                    // ->searchable()
                    ->helperText(__('Select products to include in this assortment.'))
                    ->multiple()
                    ->columnSpanFull(),

                __notes()
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('assortment_code')
                    ->label(__('Assortment'))
                    ->description(fn(Assortment $record): string => $record->assortment_name)
                    ->searchable(
                        query: fn(Builder $query, string $search): Builder =>
                        $query->whereAny(['assortment_code', 'assortment_name'], 'like', "%{$search}%")
                    )
                    ->sortable(),

                T\TextColumn::make('category.category_name')
                    ->label(__('Category'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('products.product_code')
                    ->label(__('Products'))
                    ->limitList(3),
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
            'index' => ManageAssortments::route('/'),
        ];
    }
}
