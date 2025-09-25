<?php

namespace App\Filament\Clusters\Settings\Resources\Companies;

use App\Filament\Clusters\Settings\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Company Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Tabs::make('Company Details')
                    ->tabs([
                        S\Tabs\Tab::make('General')
                            ->schema([
                                F\TextInput::make('company_code')
                                    ->required(),

                                F\TextInput::make('company_tax_id')
                                    ->label('Tax ID')
                                    ->regex('/^(?:\d{10}|\d{10}-\d{3})$/'),

                                F\TextInput::make('company_name')
                                    ->columnSpanFull()
                                    ->required(),

                                S\Flex::make([
                                    F\TextInput::make('company_address'),

                                    F\Select::make('country_id')
                                        ->label('Country')
                                        ->relationship(
                                            name: 'country',
                                            titleAttribute: 'country_name',
                                            modifyQueryUsing: fn($query) => $query->orderBy('is_fav', 'desc')
                                        )
                                        ->default(fn() => \App\Models\Country::where('alpha3', 'VNM')->first()?->id)
                                        ->searchable()
                                        ->preload()
                                        ->grow(false)
                                        ->required(),
                                ])
                                    ->columnSpanFull(),

                                S\FusedGroup::make([
                                    F\TextInput::make('company_owner_title')
                                        ->label('Owner Title')
                                        ->columnSpan(2)
                                        ->requiredWith('company_owner'),

                                    F\Select::make('company_owner_gender')
                                        ->options(\App\Enums\ContactGenderEnum::class)
                                        ->requiredWith('company_owner'),

                                    F\TextInput::make('company_owner')
                                        ->label('Owner Name')
                                        ->columnSpan(2)
                                        ->requiredWith('company_owner_gender'),
                                ])
                                    ->label('Owner')
                                    ->columns(['default' => 5]),

                                F\TextInput::make('company_website')
                                    ->url(),

                                F\TextInput::make('company_email')
                                    ->email(),

                                F\TextInput::make('company_phone')
                                    ->tel(),

                            ])
                            ->columns(),

                        S\Tabs\Tab::make('Other Info')
                            ->schema([
                                S\Group::make([
                                    F\FileUpload::make('company_logo')
                                        ->image(),
                                    S\Group::make([
                                        F\ColorPicker::make('company_color'),

                                        F\Select::make('company_currency')
                                            ->label(__('Preferred Currency'))
                                            ->options(fn() => \App\Models\Country::orderBy('is_fav', 'desc')
                                                ->whereNotNull('curr_code')
                                                ->pluck('curr_name', 'curr_code'))
                                            ->searchable()
                                            ->preload(),

                                        F\Select::make('company_language')
                                            ->label(__('Preferred Language'))
                                            ->options(fn() => \App\Models\Country::orderBy('is_fav', 'desc')->pluck('country_name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                    ]),

                                ])
                                    ->columns(),


                                F\Repeater::make('company_bank_accounts')
                                    ->label('Bank Accounts')
                                    ->simple(
                                        F\TextInput::make('company_bank_accounts')
                                            ->label('Account')
                                            ->required(),
                                    )
                                    ->addActionLabel('Add Account'),
                            ]),

                        S\Tabs\Tab::make('Staffs')
                            ->schema([
                                F\CheckboxList::make('user_id')
                                    ->label('Select Staff')
                                    ->relationship(
                                        name: 'staffs',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn($query) => $query->where('status', \App\Enums\UserStatusEnum::Active)->orderBy('name')
                                    )
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columns(),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('company_code')
                    ->label('Code')
                    ->description(fn(Company $record): ?string => $record->country->country_name)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->description(fn(Company $record): ?string => $record->company_tax_id)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('company_email')
                    ->label('Email/Phone')
                    ->description(fn(Company $record): ?string => $record->company_phone)
                    ->toggleable(),

                T\TextColumn::make('company_address')
                    ->description(fn(Company $record): string
                    => ($record->company_owner
                        && $record->company_owner_gender
                        && $record->company_owner_title)
                        ? $record->company_owner_title
                        . ' - ' . $record->company_owner_gender?->getLabel()
                        . ' ' . $record->company_owner
                        : 'N/A')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver(),
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
            'index' => ManageCompanies::route('/'),
        ];
    }
}
