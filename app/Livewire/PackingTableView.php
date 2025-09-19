<?php

namespace App\Livewire;

use App\Filament\Schemas\SettingsClusters\PackingSchema;
use App\Models\Packing;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Support\Enums\Width;

class PackingTableView extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function render()
    {
        return view('livewire.packing-table-view');
    }

    #[\Livewire\Attributes\On('refresh-custom-table')]
    public function updateTable(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Packing::query())
            ->columns([
                __index(),
                T\TextColumn::make('packing_name')
                    ->searchable(),

                T\TextColumn::make('unit_conversion_value')->label('Conversion Value'),
                T\TextColumn::make('unit.unit_name')->label('Unit'),

            ])
            ->filters([
                // ...
            ])
            ->recordActions([
                A\EditAction::make()
                    ->schema(fn($schema) => PackingSchema::configure($schema))
                    ->modalWidth(Width::Small),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([
                A\DeleteBulkAction::make(),
            ]);
    }
}
