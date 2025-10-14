<?php

namespace App\Livewire;

use App\Filament\Schemas\SettingsClusters\UnitSchema;
use App\Models\Unit;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class UnitTableView extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function render()
    {
        return view('livewire.unit-table-view');
    }

    #[\Livewire\Attributes\On('refresh-custom-table')]
    public function updateTable(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Unit::query())
            ->columns([
                __index(),
                T\TextColumn::make('unit_code')->label('Unit Code')
                    ->searchable(),
                T\TextColumn::make('unit_name')->label('Unit Name')
                    ->searchable(),

                T\TextColumn::make('conversion_factor')->label('Conversion Value')
                    ->suffix(fn($record) => ' ' . $record->parent?->unit_code)
                    ->toggleable(),
            ])
            ->filters([
                // ...
            ])
            ->recordActions([
                A\EditAction::make()
                    ->schema(fn($schema) => UnitSchema::configure($schema))
                    ->modalWidth(Width::Small),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([
                A\DeleteBulkAction::make(),
            ])
            ->recordAction('edit')
        ;
    }
}
