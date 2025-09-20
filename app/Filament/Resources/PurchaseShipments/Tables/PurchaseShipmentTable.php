<?php

namespace App\Filament\Resources\PurchaseShipments\Tables;

use App\Models\PurchaseShipment;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class PurchaseShipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                __index(),

                T\TextColumn::make('purchaseOrder.order_number')
                    ->label(__('Order No.'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->searchable()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ViewAction::make(),
                A\EditAction::make()
                    ->modal()->slideOver()
                    ->modalWidth(Width::SevenExtraLarge),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
