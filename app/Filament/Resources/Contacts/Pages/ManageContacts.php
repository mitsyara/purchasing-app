<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageContacts extends ManageRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()->slideOver()
                ->modalWidth(Width::FiveExtraLarge),
        ];
    }

    public function getTabs(): array
    {
        return [
            __('All') => Tab::make(),
            __('Traders') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('is_trader', true)),
            __('Customers') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('is_cus', true)),
            __('Manufacturers') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('is_mfg', true)),
        ];
    }
}
