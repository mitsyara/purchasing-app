<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Models\Contact;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static string|\UnitEnum|null $navigationGroup = 'other';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return Schemas\ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ContactTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContacts::route('/'),
        ];
    }
}
