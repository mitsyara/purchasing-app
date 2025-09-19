<?php

namespace App\Livewire;

use App\Filament\Schemas\SettingsClusters\VatSchema;
use App\Models\Vat;
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

class VatTableView extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function render()
    {
        return view('livewire.vat-table-view');
    }

    #[\Livewire\Attributes\On('refresh-custom-table')]
    public function updateTable(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Vat::query())
            ->columns([
                __index(),
                T\TextColumn::make('vat_name')->label('VAT Name')
                    ->searchable(),

                T\TextColumn::make('vat_value')->label('VAT Value')
                    ->sortable()
                    ->suffix('%'),
            ])
            ->filters([
                // ...
            ])
            ->recordActions([
                A\EditAction::make()
                    ->schema(fn($schema) => VatSchema::configure($schema))
                    ->modalWidth(Width::Small),
                A\DeleteAction::make(),
            ])
            ->toolbarActions([
                A\DeleteBulkAction::make(),
            ]);
    }
}
