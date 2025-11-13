<?php

namespace App\Filament\Clusters\Settings\Resources\Ports;

use App\Enums\PortTypeEnum;
use App\Enums\RegionEnum;
use App\Filament\Clusters\Settings\Resources\Ports\Pages\ManagePorts;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Port;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class PortResource extends Resource
{
    protected static ?string $model = Port::class;

    protected static ?int $navigationSort = 13;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('Company Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\ToggleButtons::make('port_type')
                    ->label('Port Type')
                    ->options(PortTypeEnum::class)
                    ->grouped()
                    ->required(),

                F\TextInput::make('port_code')
                    ->label('Port Code')
                    ->maxLength(10),

                F\TextInput::make('port_name')
                    ->label('Port Name')
                    ->columnSpanFull()
                    ->required(),

                F\TextInput::make('port_address')
                    ->label('Address')
                    ->columnSpanFull(),

                F\Select::make('region')
                    ->label('Region')
                    ->options(fn() => RegionEnum::class)
                    ->default(RegionEnum::Other),

                F\Select::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'country_name', fn($query) => $query->orderBy('is_fav', 'desc'))
                    ->searchable()
                    ->preload()
                    ->required(),

                F\TextInput::make('website')
                    ->label('Website')
                    ->url(),

                S\Group::make([
                    F\Repeater::make('phones')
                        ->simple(
                            F\TextInput::make('phone')
                                ->label('Phone')
                                ->required()
                                ->tel(),
                        ),
                    F\Repeater::make('emails')
                        ->simple(
                            F\TextInput::make('email')
                                ->label('Email')
                                ->required()
                                ->email(),
                        ),
                ])
                    ->columns()
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('port_type')
                    ->label('Type')
                    ->suffix(fn($record) => ' (' . $record->region?->getLabel() . ')')
                    ->sortable(query: fn($query, $direction) => $query->orderBy('port_type', $direction)->orderBy('region', $direction))
                    ->toggleable(),

                T\TextColumn::make('port_code')
                    ->label('Port Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('port_name')
                    ->label('Port')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('country.country_name')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('phones')
                    ->label('Phones')
                    ->listWithLineBreaks()
                    ->expandableLimitedList()
                    ->limitList(2)
                    ->toggleable(),

                T\TextColumn::make('emails')
                    ->label('Emails')
                    ->listWithLineBreaks()
                    ->expandableLimitedList()
                    ->limitList(2)
                    ->toggleable(),

                T\TextColumn::make('website')
                    ->label('Website')
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
            'index' => ManagePorts::route('/'),
        ];
    }
}
