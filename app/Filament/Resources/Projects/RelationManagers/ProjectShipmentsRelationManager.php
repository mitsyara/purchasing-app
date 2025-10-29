<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Schemas\POShipmentForm;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions as A;

class ProjectShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectShipments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::title();
    }

    public static function title(): string
    {
        return __('Shipments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            //
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(fn(): string => __('Shipment'))
            ->columns([
                __index(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\CreateAction::make()
                    ->modal()->slideOver(),
            ])
            ->recordActions([
                A\EditAction::make()
                    ->modal()->slideOver(),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
