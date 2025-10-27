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
            __('Checked') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_checked', true)),
            __('Unchecked') => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_checked', false)),
        ];
    }
}
