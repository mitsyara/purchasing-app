<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Schemas\Schema;

use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Tabs::make(__('Contact Tabs'))
                    ->schema([
                        S\Tabs\Tab::make(__('Contact Info'))
                            ->schema([
                                ...static::contactFields(),
                            ]),

                        S\Tabs\Tab::make('Warehouse & Other Info')
                            ->schema([
                                ...static::warehouseAndOtherFields(),
                            ])
                            ->columns(),

                        S\Tabs\Tab::make(__('Comments'))
                            ->schema([
                                //
                            ]),
                    ])
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    // Base Contact Fields
    public static function contactFields(): array
    {
        return [
            S\Flex::make([
                S\Section::make(__('Basic Information'))
                    ->schema(static::basicInfoFields())
                    ->compact(),

                S\Section::make(__('Additional Information'))
                    ->schema(static::additionalInfoFields())
                    ->compact()
                    ->grow(false),

            ])
                ->from('lg')
                ->columnSpanFull(),

        ];
    }

    public static function warehouseAndOtherFields(): array
    {
        return [
            S\Fieldset::make(__('Warehouses'))
                ->columns(1)
                ->schema([
                    F\Repeater::make('warehouse_addresses')
                        ->label(__('Warehouse Addresses'))
                        ->hiddenLabel()
                        ->simple(F\TextInput::make('address')
                            ->label(__('Address'))
                            ->columnSpanFull()),
                ])
                ->columnSpanFull(),

            S\Fieldset::make(__('Bank Info'))
                ->columns(1)
                ->schema([
                    F\Repeater::make('bank_infos')
                        ->label(__('Bank Information'))
                        ->simple(F\TextInput::make('info')
                            ->label(__('Information'))
                            ->columnSpanFull()),
                ]),

            S\Fieldset::make(__('Other Info'))
                ->columns(1)
                ->schema([
                    F\Repeater::make('other_infos')
                        ->label(__('Other Information'))
                        ->simple(F\TextInput::make('info')
                            ->label(__('Information'))
                            ->columnSpanFull()),
                ]),

            __notes()
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    // Basic Information Fields
    public static function basicInfoFields(): array
    {
        return [
            F\TextInput::make('contact_name')
                ->label(__('Company Name'))
                ->columnSpanFull()
                ->required(),

            F\TextInput::make('office_address')
                ->label(__('Company Address'))
                ->columnSpanFull(),

            S\Group::make([
                F\TextInput::make('tax_code')
                    ->label(__('Tax Code'))
                    ->requiredWith('is_cus'),

                F\TextInput::make('office_email')
                    ->label(__('Email'))
                    ->email(),

                F\TextInput::make('office_phone')
                    ->label(__('Phone'))
                    ->tel(),
            ])
                ->columns(3),

            S\Group::make([
                F\TextInput::make('rep_title')
                    ->label(__('Rep. Title'))
                    ->requiredWith('rep_name'),

                S\FusedGroup::make([
                    F\Select::make('rep_gender')
                        ->label(__('Rep. Gender'))
                        ->options(\App\Enums\ContactGenderEnum::class)
                        ->selectablePlaceholder(false)
                        ->requiredWith('rep_name'),

                    F\TextInput::make('rep_name')
                        ->label(__('Rep. Name'))
                        ->requiredWith('rep_gender')
                        ->columnSpan(2),
                ])
                    ->label(__('Representative'))
                    ->columns(['default' => 3]),
            ])
                ->columns(['default' => 2]),

            S\Group::make([
                F\TextInput::make('gmp_no')
                    ->label(__('GMP No.')),
                F\DatePicker::make('gmp_expires_at')
                    ->label(__('GMP Expires At')),
            ])
                ->columns(2),

            F\TagsInput::make('certificates')
                ->label(__('Certificates'))
                ->separator()
                ->splitKeys([',', ';', ' '])
                ->columnSpanFull(),
        ];
    }

    // Additional Information Fields
    public static function additionalInfoFields(): array
    {
        return [
            F\Toggle::make('is_mfg')
                ->label(__('Manufacturer')),
            F\Toggle::make('is_cus')
                ->label(__('Customer')),
            F\Toggle::make('is_trader')
                ->label(__('Trader')),

            F\Checkbox::make('is_fav')
                ->label(__('Favorite')),

            F\TextInput::make('contact_code')
                ->label(__('Code'))
                ->unique(),

            F\TextInput::make('contact_short_name')
                ->label(__('Short Name'))
                ->unique(),

            F\Select::make('country_id')
                ->label(__('Country'))
                ->relationship(
                    name: 'country',
                    titleAttribute: 'country_name',
                    modifyQueryUsing: fn($query) => $query->orderBy('is_fav', 'desc')
                )
                ->searchable()
                ->preload()
                ->required(),

            F\Select::make('region')
                ->label(__('Region'))
                ->options(\App\Enums\RegionEnum::class)
                ->default(\App\Enums\RegionEnum::Other),

        ];
    }
}
