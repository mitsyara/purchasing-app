<?php

namespace App\Filament\Resources\PurchaseShipments\Tables;

use App\Models\PurchaseShipment;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class PurchaseShipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ViewAction::make(),
                A\EditAction::make(),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }
}