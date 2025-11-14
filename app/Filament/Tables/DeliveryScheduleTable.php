<?php

namespace App\Filament\Tables;

use App\Filament\Resources\SalesOrders\RelationManagers\DeliverySchedulesRelationManager;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;
use Filament\Tables\Columns as T;

class DeliveryScheduleTable
{
    public static function configure(Table $table): Table
    {
        $table = (new DeliverySchedulesRelationManager())->table($table);
        return $table
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();
                return $query
                    ->orderBy('from_date')->orderBy('to_date')
                    ->when($arguments['customer_id'] ?? null, function ($query, $cusId) {
                        $query->whereHas('customer', function ($query) use ($cusId) {
                            $query->where('contacts.id', $cusId);
                        });
                    })
                    ->when($arguments['warehouse_id'] ?? null, function ($query, $whId) {
                        $query->whereHas('warehouse', function ($query) use ($whId) {
                            $query->where('warehouses.id', $whId);
                        });
                    });
            });
    }
}
