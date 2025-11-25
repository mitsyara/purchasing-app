<?php

namespace App\Filament\Resources\InventoryTransactions\Pages;

use App\Filament\Resources\InventoryTransactions\InventoryTransactionResource;
use Filament\Resources\Pages\ManageRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;

class ManageInventoryTransactions extends ManageRecords
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    // Tabs
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            __('Import') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Import)),
            __('Export') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('transaction_direction', \App\Enums\InventoryTransactionDirectionEnum::Export)),
        ];
    }
}
